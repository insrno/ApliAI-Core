<?php

namespace App\Http\Controllers;

use App\Models\JobDescription;
use Illuminate\Http\Request;

class JobDescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $jobDescriptions = JobDescription::latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $jobDescriptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'description' => 'required|string',
        ]);

        $jobDescription = JobDescription::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Job description saved successfully!',
            'data' => $jobDescription,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $jobDescription = JobDescription::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $jobDescription,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $jobDescription = JobDescription::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'company' => 'nullable|string|max:255',
            'description' => 'sometimes|required|string',
        ]);

        $jobDescription->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Job description updated successfully!',
            'data' => $jobDescription,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $jobDescription = JobDescription::findOrFail($id);
        $jobDescription->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Job description deleted successfully!',
        ]);
    }
}
