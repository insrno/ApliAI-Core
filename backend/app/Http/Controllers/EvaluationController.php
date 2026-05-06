<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Resume;
use App\Models\JobDescription;
use App\Services\AIService;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function __construct(private AIService $ai) {}

    public function evaluate(Request $request)
    {
        $validated = $request->validate([
            'resume_id' => 'required|integer|exists:resumes,id',
            'job_description_id' => 'required|integer|exists:job_descriptions,id',
            'provider' => 'nullable|string|in:gemini,openai,groq',
        ]);

        if (in_array(($validated['provider'] ?? null), ['gemini', 'openai'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Google Gemini and OpenAI are temporarily locked. Please use Groq.',
            ], 423);
        }

        $resume = Resume::findOrFail($validated['resume_id']);
        $jobDescription = JobDescription::findOrFail($validated['job_description_id']);

        if (empty($resume->extracted_text)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resume has no extracted text. Please re-upload.',
            ], 422);
        }

        try {
            $result = $this->ai->evaluate(
                $resume->extracted_text,
                $jobDescription->description,
                $validated['provider'] ?? null
            );

            $evaluation = Evaluation::create([
                'resume_id' => $resume->id,
                'job_description_id' => $jobDescription->id,
                'score' => $result['score'] ?? 0,
                'feedback' => $result['feedback'] ?? '',
                'strengths' => $result['strengths'] ?? [],
                'weaknesses' => $result['weaknesses'] ?? [],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Resume evaluated successfully!',
                'data' => $evaluation,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available AI providers and which ones have keys configured.
     */
    public function providers()
    {
        return response()->json([
            'providers' => [
                ['id' => 'groq', 'name' => 'Groq (Llama 3.3)', 'available' => !empty(config('services.groq.api_key'))],
                ['id' => 'gemini', 'name' => 'Google Gemini', 'available' => false],
                ['id' => 'openai', 'name' => 'OpenAI (GPT-4o)', 'available' => false],
            ],
            'default' => config('services.ai.default_provider', 'groq'),
        ]);
    }
}
