<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;

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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string',
            'job_type' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'complete' => 'boolean',
            'company_name' => 'required|string|max:255',
            'company_logo' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
            'category' => 'nullable|string|max:255',
        ]);

        // handle logo upload if present
        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('logos', 'public');
            $validated['company_logo'] = $path;
        }

        $job = Job::create($validated);
        return response()->json($job, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Job  $job
     * @return \Illuminate\Http\Response
     */
    public function show(Job $job)
    {
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
