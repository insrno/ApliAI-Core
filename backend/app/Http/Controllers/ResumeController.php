<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'resume' => 'required|mimes:pdf|max:2048',
            'user_id' => 'required|exists:users,id',
        ]);

        if (! $request->hasFile('resume')) {
            return response()->json(['error' => 'No file was uploaded.'], 400);
        }

        $file = $request->file('resume');
        $fileName = time().'_'.$file->getClientOriginalName();
        $filePath = $file->storeAs('resumes', $fileName, 'public');

        $resume = Resume::create([
            'user_id' => (int) $request->input('user_id'),
            'file_name' => $fileName,
            'file_path' => $filePath,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Resume uploaded successfully!',
            'data' => $resume,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
