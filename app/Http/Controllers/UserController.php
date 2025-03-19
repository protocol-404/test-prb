<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Create a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.verify');
    }

    /**
     * Get the authenticated user profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        $user = Auth::user();
        $user->load('skills');
        
        return response()->json($user);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'sometimes|nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($validator->validated());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Add skills to the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addSkills(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skills' => 'required|array',
            'skills.*' => 'exists:skills,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $user->skills()->attach($request->skills);
        $user->load('skills');

        return response()->json([
            'message' => 'Skills added successfully',
            'user' => $user
        ]);
    }

    /**
     * Remove a skill from the authenticated user.
     *
     * @param  int  $skillId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeSkill($skillId)
    {
        $skill = Skill::find($skillId);
        
        if (!$skill) {
            return response()->json(['message' => 'Skill not found'], 404);
        }

        $user = Auth::user();
        $user->skills()->detach($skillId);
        $user->load('skills');

        return response()->json([
            'message' => 'Skill removed successfully',
            'user' => $user
        ]);
    }

    /**
     * Get all users (admin only).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $users = User::with('skills')->get();
        
        return response()->json($users);
    }
}
