<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobOffer;
use App\Models\Resume;
use App\Jobs\SendApplicationConfirmationEmail;
use App\Jobs\ProcessBatchApplications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    /**
     * Create a new ApplicationController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.verify');
    }

    /**
     * Display a listing of the applications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = null;
        
        if ($user->isRecruiter()) {
            // Recruiters see applications for their job offers
            $jobOfferIds = JobOffer::where('recruiter_id', $user->id)->pluck('id');
            $query = Application::whereIn('job_offer_id', $jobOfferIds);
        } else {
            // Candidates see their own applications
            $query = Application::where('user_id', $user->id);
        }
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        $applications = $query->with(['jobOffer', 'resume'])->paginate(15);
        
        return response()->json($applications);
    }

    /**
     * Store a newly created application in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_offer_id' => 'required|exists:job_offers,id',
            'resume_id' => 'required|exists:resumes,id,user_id,' . Auth::id(),
            'cover_letter' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $jobOffer = JobOffer::find($request->job_offer_id);
        
        // Check if job offer is active
        if ($jobOffer->status !== 'active') {
            return response()->json([
                'message' => 'This job offer is no longer active'
            ], 400);
        }
        
        // Check if user has already applied to this job offer
        $existingApplication = Application::where('user_id', $user->id)
            ->where('job_offer_id', $request->job_offer_id)
            ->first();
            
        if ($existingApplication) {
            return response()->json([
                'message' => 'You have already applied to this job offer'
            ], 400);
        }

        $application = Application::create([
            'user_id' => $user->id,
            'job_offer_id' => $request->job_offer_id,
            'resume_id' => $request->resume_id,
            'status' => 'pending',
            'cover_letter' => $request->cover_letter,
        ]);

        // Dispatch job to send confirmation email
        SendApplicationConfirmationEmail::dispatch($application);

        return response()->json([
            'message' => 'Application submitted successfully',
            'application' => $application
        ], 201);
    }

    /**
     * Apply to multiple job offers in a single request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchApply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_offer_ids' => 'required|array',
            'job_offer_ids.*' => 'exists:job_offers,id',
            'resume_id' => 'required|exists:resumes,id,user_id,' . Auth::id(),
            'cover_letter' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        
        // Verify all job offers exist and are active
        $jobOffers = JobOffer::whereIn('id', $request->job_offer_ids)
            ->where('status', 'active')
            ->get();

        if (count($jobOffers) !== count($request->job_offer_ids)) {
            return response()->json([
                'message' => 'One or more job offers are not available'
            ], 400);
        }

        // Get already applied job offers
        $appliedJobOfferIds = Application::where('user_id', $user->id)
            ->whereIn('job_offer_id', $request->job_offer_ids)
            ->pluck('job_offer_id')
            ->toArray();
            
        // Filter out already applied job offers
        $newJobOfferIds = array_diff($request->job_offer_ids, $appliedJobOfferIds);
        
        if (empty($newJobOfferIds)) {
            return response()->json([
                'message' => 'You have already applied to all these job offers'
            ], 400);
        }

        // Process applications in the background
        $applications = [];
        foreach ($newJobOfferIds as $jobOfferId) {
            $application = Application::create([
                'user_id' => $user->id,
                'job_offer_id' => $jobOfferId,
                'resume_id' => $request->resume_id,
                'status' => 'pending',
                'cover_letter' => $request->cover_letter,
            ]);
            
            $applications[] = $application;
            
            // Dispatch job to send confirmation email
            SendApplicationConfirmationEmail::dispatch($application);
        }

        return response()->json([
            'message' => 'Applications submitted successfully',
            'applications_count' => count($applications),
            'applications' => $applications
        ], 201);
    }

    /**
     * Display the specified application.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        $application = Application::with(['jobOffer', 'resume', 'user'])->find($id);
        
        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }
        
        // Check if user is authorized to view this application
        if ($application->user_id !== $user->id && 
            !($user->isRecruiter() && $application->jobOffer->recruiter_id === $user->id) &&
            !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($application);
    }

    /**
     * Update the status of the specified application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,reviewed,accepted,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $application = Application::with('jobOffer')->find($id);
        
        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }
        
        // Check if user is authorized to update this application
        if (!$user->isRecruiter() || $application->jobOffer->recruiter_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $application->status = $request->status;
        $application->save();

        return response()->json([
            'message' => 'Application status updated successfully',
            'application' => $application
        ]);
    }
}
