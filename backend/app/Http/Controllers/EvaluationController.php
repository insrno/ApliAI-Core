<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\JobDescription;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    /**
     * Evaluate a resume against a job description using Gemini AI.
     */
    public function evaluate(Request $request)
    {
        // TODO: Implement Gemini AI evaluation logic
        // This will be filled in once the GEMINI_API_KEY is set up in .env

        return response()->json([
            'status' => 'pending',
            'message' => 'Evaluation endpoint is ready. Gemini AI integration coming next!',
        ]);
    }
}
