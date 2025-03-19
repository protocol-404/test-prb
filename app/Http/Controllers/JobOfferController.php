<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class JobOfferController extends Controller
{
    /**
     * Create a new JobOfferController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.verify');
    }

    /**
     * Display a listing of the job offers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = JobOffer::query()->active();
        
        // Apply filters
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }
        
        if ($request->has('location')) {
            $query->byLocation($request->location);
        }
        
        if ($request->has('contract_type')) {
            $query->byContractType($request->contract_type);
        }
        
        $jobOffers = $query->with('recruiter:id,name,email')->paginate(15);
        
        return response()->json($jobOffers);
    }

    /**
     * Store a newly created job offer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Check if user is a recruiter
        if (!$user->isRecruiter()) {
            return response()->json(['message' => 'Unauthorized. Only recruiters can create job offers.'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'location' => 'required|string|max:100',
            'contract_type' => 'required|string|max:100',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $jobOffer = JobOffer::create([
            'recruiter_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'location' => $request->location,
            'contract_type' => $request->contract_type,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'Job offer created successfully',
            'job_offer' => $jobOffer
        ], 201);
    }

    /**
     * Display the specified job offer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $jobOffer = JobOffer::with('recruiter:id,name,email')->find($id);
        
        if (!$jobOffer) {
            return response()->json(['message' => 'Job offer not found'], 404);
        }
        
        return response()->json($jobOffer);
    }

    /**
     * Update the specified job offer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $jobOffer = JobOffer::find($id);
        
        if (!$jobOffer) {
            return response()->json(['message' => 'Job offer not found'], 404);
        }
        
        // Check if user is the recruiter who created this job offer or an admin
        if ($jobOffer->recruiter_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You can only update your own job offers.'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category' => 'sometimes|string|max:100',
            'location' => 'sometimes|string|max:100',
            'contract_type' => 'sometimes|string|max:100',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $jobOffer->update($validator->validated());

        return response()->json([
            'message' => 'Job offer updated successfully',
            'job_offer' => $jobOffer
        ]);
    }

    /**
     * Remove the specified job offer from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $jobOffer = JobOffer::find($id);
        
        if (!$jobOffer) {
            return response()->json(['message' => 'Job offer not found'], 404);
        }
        
        // Check if user is the recruiter who created this job offer or an admin
        if ($jobOffer->recruiter_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You can only delete your own job offers.'], 403);
        }
        
        $jobOffer->delete();

        return response()->json([
            'message' => 'Job offer deleted successfully'
        ]);
    }

    /**
     * Display a listing of the job offers created by the authenticated recruiter.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function myJobOffers()
    {
        $user = Auth::user();
        
        // Check if user is a recruiter
        if (!$user->isRecruiter()) {
            return response()->json(['message' => 'Unauthorized. Only recruiters can access this endpoint.'], 403);
        }
        
        $jobOffers = JobOffer::where('recruiter_id', $user->id)->paginate(15);
        
        return response()->json($jobOffers);
    }
}
