<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class JobApplicationController extends Controller
{   
    /**
     * Display a listing of applications received for the authenticated user's jobs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request) // Renamed for clarity, can be named differently
    {
        $user = Auth::user();

        // Ensure user is an employer or admin
        if (!$user->is_employer && !$user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get IDs of jobs posted by this user
        $postedJobIds = $user->postedJobs()->pluck('id');

        // Fetch applications for those jobs, eager loading applicant and job details
        $applications = JobApplication::whereIn('job_id', $postedJobIds)
                                      ->with(['user:id,name,email', 'job:id,job_title']) // Select specific columns for efficiency
                                      ->latest() // Order by most recent first
                                      ->get();

        return response()->json($applications);
    }

    /**
     * Store a newly created job application in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $jobId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, string $jobId)
    {
        // 1. Validate Job Exists
        $job = Job::findOrFail($jobId);

        // 2. Get Authenticated User
        $user = Auth::user();

        // 3. Validate Request Data (including files)
        // Adjust file validation rules as needed (mimes, max size in KB)
        $validated = $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120', // Required, PDF/Word, max 5MB
            'cover_letter' => 'nullable|file|mimes:pdf,doc,docx,txt|max:2048', // Optional, PDF/Word/Text, max 2MB
        ]);

        // 4. Check for Duplicate Application (using the unique constraint we set)
        $existingApplication = JobApplication::where('user_id', $user->id)
                                             ->where('job_id', $job->id)
                                             ->first();

        if ($existingApplication) {
            // Return a specific error if user already applied
            return response()->json(['message' => 'You have already applied for this job.'], 409); // 409 Conflict
        }

        // 5. Store Files & Get Paths
        $resumePath = null;
        $coverLetterPath = null;

        try {
            // Store resume
            $resumeFile = $request->file('resume');
            $resumeFilename = 'resumes/' . $user->id . '_' . $job->id . '_' . Str::random(10) . '.' . $resumeFile->getClientOriginalExtension();
            $resumePath = $resumeFile->storeAs('private/applications', $resumeFilename); // Store in a private directory

            // Store cover letter if present
            if ($request->hasFile('cover_letter')) {
                $coverLetterFile = $request->file('cover_letter');
                $coverLetterFilename = 'cover_letters/' . $user->id . '_' . $job->id . '_' . Str::random(10) . '.' . $coverLetterFile->getClientOriginalExtension();
                $coverLetterPath = $coverLetterFile->storeAs('private/applications', $coverLetterFilename); // Store in a private directory
            }

            // 6. Create Application Record
            $application = JobApplication::create([
                'user_id' => $user->id,
                'job_id' => $job->id,
                'resume_path' => $resumePath,
                'cover_letter_path' => $coverLetterPath,
            ]);

            // 7. Return Success Response
            return response()->json([
                'message' => 'Application submitted successfully!',
                'application' => $application // Optionally return the created application data
            ], 201); // 201 Created

        } catch (\Exception $e) {
            // Clean up stored files if database creation fails
            if ($resumePath) { Storage::delete($resumePath); }
            if ($coverLetterPath) { Storage::delete($coverLetterPath); }

            \Log::error('Job Application Error: ' . $e->getMessage()); // Log the error
            return response()->json(['message' => 'Failed to submit application. Please try again.'], 500);
        }
    }

    /**
     * Display the specified job application.
     * Includes authorization check.
     *
     * @param  string $applicationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $applicationId)
    {
         try {
            // Eager load related user (applicant) and job details
            $application = JobApplication::with(['user:id,name,email', 'job:id,job_title,employer_id'])->findOrFail($applicationId);

            // --- AUTHORIZATION CHECK ---
            $user = Auth::user();
            $isApplicant = $user->id === $application->user_id;
            $isJobOwner = $user->id === $application->job->employer_id;
            $isAdmin = $user->is_admin;

            // Allow access if user is the applicant OR the job owner OR an admin
            if (!$isApplicant && !$isJobOwner && !$isAdmin) {
                 Log::warning('Unauthorized access attempt to Application ID: ' . $applicationId . ' by User ID: ' . $user->id);
                return response()->json(['message' => 'You do not have permission to view this application.'], 403);
            }
            // --- END AUTHORIZATION ---

            // Return the application details
            return response()->json($application);

         } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Application not found.'], 404);
         } catch (\Exception $e) {
             Log::error('Error fetching application ID: ' . $applicationId . ' - ' . $e->getMessage());
             return response()->json(['message' => 'An error occurred while fetching application details.'], 500);
         }
    }

    // --- NEW METHOD for downloading resume ---
    /**
     * Download the resume file for a specific application.
     *
     * @param string $applicationId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadResume(string $applicationId)
    {
        try {
            $application = JobApplication::with('job:id,employer_id')->findOrFail($applicationId); // Need job for employer check
            $user = Auth::user();
            $isApplicant = $user->id === $application->user_id;
            $isJobOwner = $application->job && $user->id === $application->job->employer_id;
            $isAdmin = $user->is_admin;

            // Authorization: Allow applicant, job owner, or admin
            if (!$isApplicant && !$isJobOwner && !$isAdmin) {
                Log::warning('Unauthorized resume download attempt for Application ID: ' . $applicationId . ' by User ID: '  . $user->id);
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check if file exists in storage (using the path stored in DB)
            if (!$application->resume_path || !Storage::exists($application->resume_path)) {
                 Log::error('Resume file not found for Application ID: ' . $applicationId . '. Path: ' . $application->resume_path);
                return response()->json(['message' => 'Resume file not found.'], 404);
            }

            // Generate a user-friendly filename (optional)
            $applicant = $application->user()->first(['name']); // Get applicant name
            $jobTitle = $application->job()->first(['job_title']); // Get job title
            $extension = pathinfo(storage_path('app/' . $application->resume_path), PATHINFO_EXTENSION);
            $downloadFilename = Str::slug($applicant->name ?? 'applicant' . '-' . $jobTitle->job_title ?? 'job') . '-resume.' . $extension;

            // Return file download response
            return Storage::download($application->resume_path, $downloadFilename);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Application not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Error downloading resume for Application ID: ' . $applicationId . ' - ' . $e->getMessage());
            return response()->json(['message' => 'Could not download resume.'], 500);
        }
    }

     // --- NEW METHOD for downloading cover letter ---
    /**
     * Download the cover letter file for a specific application.
     *
     * @param string $applicationId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadCoverLetter(string $applicationId)
    {
         try {
            $application = JobApplication::with('job:id,employer_id')->findOrFail($applicationId);
            $user = Auth::user();
            $isApplicant = $user->id === $application->user_id;
            $isJobOwner = $application->job && $user->id === $application->job->employer_id;
            $isAdmin = $user->is_admin;

            // Authorization: Allow applicant, job owner, or admin
            if (!$isApplicant && !$isJobOwner && !$isAdmin) {
                 Log::warning('Unauthorized cover letter download attempt for Application ID: ' . $applicationId . ' by User ID: ' . $user->id);
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!$application->cover_letter_path || !Storage::exists($application->cover_letter_path)) {
                 Log::error('Cover letter file not found for Application ID: ' . $applicationId . '. Path: ' . $application->cover_letter_path);
                return response()->json(['message' => 'Cover letter file not found.'], 404);
            }

            // Generate filename
            $applicant = $application->user()->first(['name']);
            $jobTitle = $application->job()->first(['job_title']);
            $extension = pathinfo(storage_path('app/' . $application->cover_letter_path), PATHINFO_EXTENSION);
            $downloadFilename = Str::slug($applicant->name ?? 'applicant' . '-' . $jobTitle->job_title ?? 'job') . '-cover-letter.' . $extension;

            return Storage::download($application->cover_letter_path, $downloadFilename);

         } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Application not found.'], 404);
         } catch (\Exception $e) {
             Log::error('Error downloading cover letter for Application ID: ' . $applicationId . ' - ' . $e->getMessage());
             return response()->json(['message' => 'Could not download cover letter.'], 500);
         }
    }

    /**
     * Remove the specified job application from storage.
     *
     * @param  string $applicationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $applicationId)
    {
        try {
            // Find the application, including the job relationship for authorization
            $application = JobApplication::with('job:id,employer_id')->findOrFail($applicationId);
            $user = Auth::user();

            $isApplicant = $user->id === $application->user_id;
            $isJobOwner = $application->job && $user->id === $application->job->employer_id;
            $isAdmin = $user->is_admin;

            // Allow delete if user is the applicant OR the job owner OR an admin
            if (!$isApplicant && !$isJobOwner && !$isAdmin) {
                    Log::warning('Unauthorized delete attempt for Application ID: ' . $applicationId . ' by User ID: ' . $user->id);
                    return response()->json(['message' => 'Unauthorized'], 403);
                }

            // Delete files from storage if they exist
            if ($application->resume_path && Storage::exists($application->resume_path)) {
                Storage::delete($application->resume_path);
                 Log::info('Deleted resume file: ' . $application->resume_path); // Log call works
            }
            if ($application->cover_letter_path && Storage::exists($application->cover_letter_path)) {
                Storage::delete($application->cover_letter_path);
                 Log::info('Deleted cover letter file: ' . $application->cover_letter_path); // Log call works
            }

            // Delete the application record from the database
            $application->delete();

            Log::info('Application ID: ' . $applicationId . ' deleted successfully by User ID: ' . $user->id); // Log call works
            return response()->json(['message' => 'Application deleted successfully']);

        } catch (ModelNotFoundException $e) {
             Log::error('Attempt to delete non-existent Application ID: ' . $applicationId); // Log call works
            return response()->json(['message' => 'Application not found.'], 404);
        } catch (\Exception $e) {
            // Log call works
            Log::error('Error deleting Application ID: ' . $applicationId . ' - ' . $e->getMessage());
            return response()->json(['message' => 'Could not delete application.'], 500);
        }
    }

    /**
     * NEW METHOD
     * Display a listing of applications submitted BY the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubmittedApplications(Request $request)
    {
        $user = Auth::user();

        // Fetch applications submitted by this user
        // Eager load job details, including company_name for the list
        $applications = JobApplication::where('user_id', $user->id)
                                      ->with(['job:id,job_title,company_name']) 
                                      ->latest() // Order by most recent first
                                      ->get();

        return response()->json($applications);
    }
}
