<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

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
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            
            $photo = $request->file('profile_photo');
            
            // Generate a unique filename
            $filename = 'profile-photos/' . Str::uuid() . '.' . $photo->getClientOriginalExtension();
            
            // Store the image in the storage/app/public directory
            $path = $photo->storeAs('public', $filename);
            
            // Store the relative path in the database
            $validated['profile_photo'] = $filename;
        }

        $user->update($validated);
        
        return response()->json($user);
    }

    /**
     * Remove the profile photo
     */
    public function removeProfilePhoto($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->profile_photo = null;
            $user->save();
        }
        
        return response()->json(['message' => 'Profile photo removed successfully']);
    }

    /**
     * Save a job for the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveJob(Request $request, string $userId)
    {
        // Authorization: Ensure the logged-in user matches the userId in the URL
        if (Auth::id() != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'job_id' => 'required|integer|exists:jobs,id',
        ]);

        $user = User::findOrFail($userId);
        $jobId = $validated['job_id'];

        // Use syncWithoutDetaching to add the relationship if it doesn't exist
        // This prevents errors if the job is already saved
        $user->savedJobs()->syncWithoutDetaching([$jobId]);

        return response()->json(['message' => 'Job saved successfully']);
    }

    /**
     * Remove a saved job for the specified user.
     *
     * @param  \Illuminate\Http\Request  $request  // Request needed to get job_id
     * @param  string  $userId
     * @param  string  $jobId // Get job ID directly from route
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsaveJob(string $userId, string $jobId) // Changed signature
    {
         // Authorization: Ensure the logged-in user matches the userId in the URL
        if (Auth::id() != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate job ID exists (optional but good practice)
        Job::findOrFail($jobId);

        $user = User::findOrFail($userId);

        // Use detach to remove the relationship
        $user->savedJobs()->detach($jobId);

        return response()->json(['message' => 'Job unsaved successfully']);
    }

    /**
     * Get IDs of jobs saved by the specified user.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSavedJobIds(string $userId)
    {
         // Authorization: Ensure the logged-in user matches the userId in the URL
        if (Auth::id() != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($userId);

        // Fetch only the IDs of the saved jobs
        $savedJobIds = $user->savedJobs()->pluck('jobs.id'); // Use pluck for efficiency

        return response()->json($savedJobIds);
    }

    /**
     * Get the jobs posted by the specified user.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostedJobs(string $userId)
    {
        // Authorization: Ensure the logged-in user is requesting their own jobs
        if (Auth::id() != $userId && !Auth::user()->is_admin) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($userId);

        // Fetch jobs where employer_id matches the user's ID
        // Order by latest first
        $postedJobs = $user->postedJobs()->latest()->get();

        return response()->json($postedJobs);
    }

    /**
     * Get the job applications submitted by the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubmittedApplications(Request $request)
    {
        $user = Auth::user();

        // Use the relationship we defined on the User model
        // Eager load the 'job' relationship to get job details
        $applications = $user->jobApplications()
                              ->with('job:id,job_title,company_name') // Load job details, select only needed columns
                              ->latest() // Show newest applications first
                              ->get();

        return response()->json($applications);
    }
}