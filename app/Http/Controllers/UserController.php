<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with(['savedJobs', 'postedJobs'])->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|nullable',
            'is_admin' => 'sometimes|boolean',
            'is_employer' => 'sometimes|boolean',
            'profile_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old profile photo if exists
            $user->deleteProfilePhoto();
            
            $photo = $request->file('profile_photo');
            
            // Generate a unique filename
            $filename = 'profile-photos/' . Str::uuid() . '.' . $photo->getClientOriginalExtension();
            
            // Store the image in the storage/app/public directory
            $path = $photo->storeAs('public', $filename);
            
            // Store the relative path in the database
            $validated['profile_photo_path'] = $filename;
            
            // Remove the profile_photo from validated data as it's not a database field
            unset($validated['profile_photo']);
        }

        $user->update($validated);
        
        return response()->json([
            'user' => $user,
            'profile_photo_url' => $user->profile_photo_url
        ]);
    }

    /**
     * Remove the profile photo
     */
    public function removeProfilePhoto($id)
    {
        $user = User::findOrFail($id);
        $user->deleteProfilePhoto();
        
        return response()->json(['message' => 'Profile photo removed successfully']);
    }

    /**
     * Save a job for the user
     */
    public function saveJob(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $jobId = $request->input('job_id');
        
        if (!$user->hasSavedJob($jobId)) {
            $user->savedJobs()->attach($jobId);
            return response()->json(['message' => 'Job saved successfully']);
        }
        
        return response()->json(['message' => 'Job already saved'], 400);
    }

    /**
     * Remove a saved job for the user
     */
    public function unsaveJob(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $jobId = $request->input('job_id');
        
        $user->savedJobs()->detach($jobId);
        
        return response()->json(['message' => 'Job removed from saved jobs']);
    }

    /**
     * Get saved jobs for the user
     */
    public function getSavedJobs($userId)
    {
        $user = User::with('savedJobs')->findOrFail($userId);
        return response()->json($user->savedJobs);
    }
}