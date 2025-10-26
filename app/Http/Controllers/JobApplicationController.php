<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JobApplicationController extends Controller
{
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
}
