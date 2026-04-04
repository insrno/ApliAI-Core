<?php

namespace App\Http\Controllers;

use App\Models\JobDescription;
use App\Models\Resume;
use Illuminate\Http\Request;

class KeywordsController extends Controller
{
    public function check(Request $request)
    {
        $validated = $request->validate([
            'resume_id' => 'required|integer|exists:resumes,id',
            'job_description_id' => 'required|integer|exists:job_descriptions,id',
        ]);

        $resume = Resume::findOrFail($validated['resume_id']);
        $jobDescription = JobDescription::findOrFail($validated['job_description_id']);

        // Stop words to ignore when extracting keywords
        $stopWords = ['the', 'and', 'a', 'to', 'of', 'in', 'i', 'is', 'that', 'it', 'on', 'you', 'this', 'for', 'but', 'with', 'are', 'have', 'be', 'at', 'or', 'as', 'was', 'so', 'if', 'out', 'not', 'we', 'my', 'can', 'from', 'we', 'they', 'team', 'work', 'years', 'experience', 'ability', 'skills', 'knowledge', 'understanding', 'required', 'preferred', 'must', 'will', 'an', 'your'];

        // Very basic keyword extraction: words >= 4 chars, remove punctuation
        $jdText = strtolower(preg_replace('/[^a-zA-Z\s]/', ' ', $jobDescription->description));
        $words = str_word_count($jdText, 1);
        
        $keywords = [];
        foreach ($words as $word) {
            if (strlen($word) >= 4 && !in_array($word, $stopWords)) {
                if (!isset($keywords[$word])) {
                    $keywords[$word] = 0;
                }
                $keywords[$word]++;
            }
        }

        // Sort by frequency and take top 15 words
        arsort($keywords);
        $topKeywords = array_slice(array_keys($keywords), 0, 15);

        // Check against resume
        $resumeText = strtolower($resume->extracted_text);
        
        $matched = [];
        $missing = [];

        foreach ($topKeywords as $kw) {
            if (str_contains($resumeText, $kw)) {
                $matched[] = $kw;
            } else {
                $missing[] = $kw;
            }
        }

        $matchRate = count($topKeywords) > 0 ? round((count($matched) / count($topKeywords)) * 100) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'matched' => $matched,
                'missing' => $missing,
                'match_rate' => $matchRate,
            ]
        ]);
    }
}
