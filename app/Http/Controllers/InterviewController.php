<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class InterviewController extends Controller
{
    /**
     * Display a listing of interviews scheduled BY the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request) // Method to get scheduled interviews
    {
        $user = Auth::user();

        // Ensure user is an employer or admin who can schedule
        if (!$user->is_employer && !$user->is_admin) {
            // Or maybe return empty array? Depends on desired behaviour.
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Fetch interviews where employer_id matches the logged-in user
            $interviews = Interview::where('employer_id', $user->id)
                                ->with([
                                    // Eager load necessary details for display
                                    'jobApplication:id,user_id,job_id', // Select specific keys
                                    'jobApplication.user:id,name',      // Applicant name
                                    'jobApplication.job:id,job_title' // Job title
                                ])
                                ->orderBy('scheduled_at', 'asc') // Order chronologically
                                ->get();

            return response()->json($interviews);

        } catch (\Exception $e) {
            Log::error('Error fetching scheduled interviews for User ID: ' . $user->id . ' - ' . $e->getMessage());
            return response()->json(['message' => 'Could not retrieve scheduled interviews.'], 500);
        }
    }
    
    /**
     * Store a newly created interview resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $applicationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, string $applicationId)
    {
        try {
            // Find the associated Job Application (and eager load job for employer ID)
            $application = JobApplication::with('job:id,employer_id')->findOrFail($applicationId);

            // Authorization: Ensure the scheduler is the job owner or an admin
            $scheduler = Auth::user();
            if (!$application->job || ($scheduler->id !== $application->job->employer_id && !$scheduler->is_admin)) {
                Log::warning('Unauthorized interview schedule attempt for Application ID: ' . $applicationId . ' by User ID: ' . $scheduler->id);
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Validate Input Data (Title and Date/Time)
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                // Ensure date is valid, properly formatted, and in the future
                'scheduled_at' => 'required|date|after:now',
            ]);

            // Create the Interview Record
            $interview = Interview::create([
                'job_application_id' => $application->id,
                'employer_id' => $scheduler->id, // Store who scheduled it
                'title' => $validated['title'],
                'scheduled_at' => $validated['scheduled_at'],
                // No 'notes' field included
            ]);

            Log::info('Interview scheduled successfully for Application ID: ' . $applicationId . ' by User ID: ' . $scheduler->id . '. Interview ID: ' . $interview->id);

            // Return Success Response
            return response()->json([
                'message' => 'Interview scheduled successfully!',
                'interview' => $interview->load(['jobApplication.user:id,name', 'jobApplication.job:id,job_title']) // Optionally return details
            ], 201); // 201 Created

        } catch (ModelNotFoundException $e) {
            Log::error('Attempt to schedule interview for non-existent Application ID: ' . $applicationId);
            return response()->json(['message' => 'Job application not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors
            Log::warning('Interview scheduling validation failed for Application ID: ' . $applicationId, ['errors' => $e->errors()]);
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error scheduling interview for Application ID: ' . $applicationId . ' - ' . $e->getMessage());
            return response()->json(['message' => 'Could not schedule interview due to a server error.'], 500);
        }
    }
}