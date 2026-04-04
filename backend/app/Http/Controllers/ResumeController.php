<?php

namespace App\Http\Controllers;

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
            // 'user_id' => 'required|exists:users,id'
        ]);

        if ($request->hasFile('resume')) {
            $file = $request->file('resume');
            
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('resumes', $fileName, 'public');

            // --- NEW: PARSE THE PDF ---
            // 1. Initialize the parser
            $parser = new \Smalot\PdfParser\Parser();
            
            // 2. Point it to the exact file we just saved on the server
            $pdf = $parser->parseFile(storage_path('app/public/' . $filePath));
            
            // 3. Extract the raw text
            $extractedText = $pdf->getText();
            // --------------------------

            $resume = \App\Models\Resume::create([
                'user_id' => 1, 
                'file_name' => $fileName,
                'file_path' => $filePath,
                'extracted_text' => $extractedText,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Resume uploaded and parsed successfully!',
                'data' => $resume,
                'extracted_text' => $extractedText // Let's output it to Thunder Client to prove it works!
            ], 201);
        }

        return response()->json(['error' => 'No file was uploaded.'], 400);
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
