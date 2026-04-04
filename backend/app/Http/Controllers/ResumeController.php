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
            'resume' => 'required|file|mimes:pdf|max:5120', // PDF only, max 5MB
        ]);

        if ($request->hasFile('resume')) {
            $file = $request->file('resume');

            // Sanitize filename: remove path info, replace unsafe chars
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalName);
            $fileName = time() . '_' . substr($safeName, 0, 100) . '.pdf';
            $filePath = $file->storeAs('resumes', $fileName, 'public');

            // Parse PDF with error handling
            $extractedText = '';
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile(storage_path('app/public/' . $filePath));
                $extractedText = $pdf->getText();

                // Limit extracted text to prevent DB overflow
                $extractedText = mb_substr($extractedText, 0, 50000);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('PDF parsing failed', ['error' => $e->getMessage()]);
                // Continue with empty text - user can still try evaluation
            }

            $resume = \App\Models\Resume::create([
                'user_id' => 1,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'extracted_text' => $extractedText,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Resume uploaded successfully!',
                'data' => $resume,
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
