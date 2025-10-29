<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $jobs = job::all();
        return response()->json(Job::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_title' => 'required|string|max:255',
            'job_description' => 'required|string',
            'job_location' => 'required|string|max:255',
            'job_type' => 'required|string|in:Full-time,Part-time,Contract,Internship',
            'company_name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'employer_id' => 'required|integer|exists:users,id',
            'salary' => 'nullable|numeric|min:0',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($request->user()->id != $validated['employer_id']) {
            return response()->json(['message' => 'Unauthorized. You can only post jobs for yourself.'], 403);
        }

        // handle logo upload if present
        if ($request->hasFile('company_logo')) {
            $logo = $request->file('company_logo');
            
            // Generate a unique filename and store it
            $filename = 'company-logos/' . Str::uuid() . '.' . $logo->getClientOriginalExtension();
            $logo->storeAs('public', $filename);
            
            // Add the file path to our validated data
            $validated['company_logo'] = $filename;
        }

        // 4. Create the job
        $job = Job::create($validated);

        // 5. Return a success response
        return response()->json($job, 201); // 201 = "Created"
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id  
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id) 
    {
        // Now $id is defined and can be used
        $job = Job::findOrFail($id);
        return response()->json($job);
    }

    /**
     * Update the specified resource in storage.
     * Use POST route, manually find Job by ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  // <-- USE string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id) // <-- USE string $id
    {
        // --- Manually find the job ---
        $job = Job::findOrFail($id);
        // --- End Change ---

        // Authorization Check
        if ($request->user()->id != $job->employer_id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized...'], 403);
        }

        // Validation
        $validated = $request->validate([
            'job_title' => 'required|string|max:255',
            'job_description' => 'required|string',
            'job_location' => 'required|string|max:255',
            'job_type' => 'required|string|in:Full-time,Part-time,Contract,Internship',
            'company_name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'complete' => 'required|boolean',
        ]);

        // Handle Logo Update
        if ($request->hasFile('company_logo')) {
            if ($job->company_logo) { Storage::disk('public')->delete($job->company_logo); }
            $logo = $request->file('company_logo');
            $filename = 'company-logos/' . Str::uuid() . '.' . $logo->getClientOriginalExtension();
            $logo->storeAs('public', $filename);
            $validated['company_logo'] = $filename;
        }

        // Update the job
        $updated = $job->update($validated); // This will now update the correct record

        // Log result (optional but helpful)
        if ($updated) {
            Log::info('Job ID: ' . $job->id . ' updated successfully in DB.');
        } else {
            Log::error('Job ID: ' . $job->id . ' FAILED to update in DB.');
        }

        // Return the *correctly* loaded and updated job
        // Eager load employer details for the response
        return response()->json($job->load('employer'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Job  $job
     * @return \Illuminate\Http\Response
     */
    public function destroy(Job $job)
    {
        $job->delete();
        return response()->json(['message' => 'Job deleted successfully']);
    }
}
