<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Resume;
use App\Models\JobDescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvaluationController extends Controller
{
    /**
     * Evaluate a resume against a job description using Gemini AI.
     */
    public function evaluate(Request $request)
    {
        $validated = $request->validate([
            'resume_id' => 'required|integer|exists:resumes,id',
            'job_description_id' => 'required|integer|exists:job_descriptions,id',
        ]);

        $resume = Resume::findOrFail($validated['resume_id']);
        $jobDescription = JobDescription::findOrFail($validated['job_description_id']);

        // Check if we have extracted text
        if (empty($resume->extracted_text)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resume has no extracted text. Please re-upload the resume.',
            ], 422);
        }

        // Build the prompt for Gemini
        $prompt = $this->buildPrompt($resume->extracted_text, $jobDescription->description);

        // Call Gemini API
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gemini API key is not configured.',
            ], 500);
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->failed()) {
                Log::error('Gemini API Error', ['response' => $response->body()]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get response from Gemini AI.',
                    'details' => $response->json(),
                ], 500);
            }

            // Parse Gemini response
            $geminiData = $response->json();
            $aiText = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$aiText) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gemini returned an empty response.',
                ], 500);
            }

            $aiResult = json_decode($aiText, true);

            if (!$aiResult) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to parse Gemini AI response.',
                    'raw_response' => $aiText,
                ], 500);
            }

            // Save evaluation to database
            $evaluation = Evaluation::create([
                'resume_id' => $resume->id,
                'job_description_id' => $jobDescription->id,
                'score' => $aiResult['score'] ?? 0,
                'feedback' => $aiResult['feedback'] ?? '',
                'strengths' => $aiResult['strengths'] ?? [],
                'weaknesses' => $aiResult['weaknesses'] ?? [],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Resume evaluated successfully!',
                'data' => $evaluation,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Evaluation Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during evaluation.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build the AI prompt for resume evaluation.
     */
    private function buildPrompt(string $resumeText, string $jobDescription): string
    {
        return <<<PROMPT
You are an expert HR recruiter and resume evaluator. Analyze the following resume against the job description and provide a detailed evaluation.

**RESUME:**
{$resumeText}

**JOB DESCRIPTION:**
{$jobDescription}

Respond in the following JSON format only:
{
    "score": <number from 0 to 100>,
    "feedback": "<a detailed 2-3 paragraph summary of how well this resume matches the job description>",
    "strengths": ["<strength 1>", "<strength 2>", "<strength 3>"],
    "weaknesses": ["<weakness 1>", "<weakness 2>", "<weakness 3>"]
}

Scoring guide:
- 90-100: Perfect match, highly qualified
- 70-89: Strong match, most requirements met
- 50-69: Moderate match, some gaps
- 30-49: Weak match, significant gaps
- 0-29: Poor match, not suitable

Be specific and constructive in your feedback. Reference actual content from the resume and job description.
PROMPT;
    }
}
