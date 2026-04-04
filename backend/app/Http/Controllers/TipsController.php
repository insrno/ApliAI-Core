<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Services\AIService;
use Illuminate\Http\Request;

class TipsController extends Controller
{
    public function __construct(private AIService $ai) {}

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'resume_id' => 'required|integer|exists:resumes,id',
            'provider' => 'nullable|string|in:gemini,openai,groq',
        ]);

        $resume = Resume::findOrFail($validated['resume_id']);

        if (empty($resume->extracted_text)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resume has no extracted text.',
            ], 422);
        }

        try {
            $result = $this->ai->generateTips(
                $resume->extracted_text,
                $validated['provider'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'data' => $result['tips'] ?? [],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
