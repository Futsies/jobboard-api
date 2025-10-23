<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * @param  \App\Models\Job  $job
     * @return \Illuminate\Http\Response
     */
    public function show(Job $job)
    {
        $job = Job::with('employer')->findOrFail($id);
        return response()->json($job);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Job  $job
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Job $job)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'location' => 'sometimes|required|string',
            'job_type' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'complete' => 'boolean',
            'company_name' => 'sometimes|required|string|max:255',
            'company_logo' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
            'category' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('logos', 'public');
            $validated['company_logo'] = $path;
        }

        $job->update($validated);
        return response()->json($job);
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
