<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ResumeController extends Controller
{
    /**
     * Create a new ResumeController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.verify');
    }

    /**
     * Display a listing of the user's resumes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = Auth::user();
        $resumes = $user->resumes;
        
        return response()->json($resumes);
    }

    /**
     * Upload a new resume.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resume' => 'required|file|mimes:pdf,docx|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $file = $request->file('resume');
        $fileName = time() . '_' . Str::slug($user->name) . '.' . $file->getClientOriginalExtension();
        
        // Store file in the storage system
        $path = $file->storeAs(
            'resumes/' . $user->id,
            $fileName,
            'public'
        );

        // Create resume record
        $resume = Resume::create([
            'user_id' => $user->id,
            'file_name' => $fileName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
        ]);

        return response()->json([
            'message' => 'Resume uploaded successfully',
            'resume' => $resume
        ], 201);
    }

    /**
     * Display the specified resume.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        $resume = Resume::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$resume) {
            return response()->json(['message' => 'Resume not found'], 404);
        }
        
        return response()->json($resume);
    }

    /**
     * Remove the specified resume from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $resume = Resume::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$resume) {
            return response()->json(['message' => 'Resume not found'], 404);
        }
        
        // Delete file from storage
        Storage::disk('public')->delete($resume->file_path);
        
        // Delete resume record
        $resume->delete();

        return response()->json([
            'message' => 'Resume deleted successfully'
        ]);
    }

    /**
     * Download the specified resume.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function download($id)
    {
        $user = Auth::user();
        $resume = Resume::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$resume) {
            return response()->json(['message' => 'Resume not found'], 404);
        }
        
        if (!Storage::disk('public')->exists($resume->file_path)) {
            return response()->json(['message' => 'Resume file not found'], 404);
        }
        
        return Storage::disk('public')->download($resume->file_path, $resume->file_name);
    }
}
