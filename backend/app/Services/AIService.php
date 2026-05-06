<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * Evaluate a resume against a job description using the selected AI provider.
     *
     * @return array{score: int, feedback: string, strengths: array, weaknesses: array}
     * @throws \Exception
     */
    public function evaluate(string $resumeText, string $jobDescription, string $provider = null): array
    {
        $provider = $provider ?? config('services.ai.default_provider', 'groq');
        $prompt = $this->buildPrompt($resumeText, $jobDescription);

        return match ($provider) {
            'gemini' => $this->callGemini($prompt, 'evaluation'),
            'openai' => $this->callOpenAI($prompt, 'evaluation'),
            'groq' => $this->callGroq($prompt, 'evaluation'),
            default => $this->callGroq($prompt, 'evaluation'),
        };
    }

    /**
     * Generate resume improvement tips.
     */
    public function generateTips(string $resumeText, string $provider = null): array
    {
        $provider = $provider ?? config('services.ai.default_provider', 'groq');
        $prompt = $this->buildTipsPrompt($resumeText);

        return match ($provider) {
            'gemini' => $this->callGemini($prompt, 'tips'),
            'openai' => $this->callOpenAI($prompt, 'tips'),
            'groq' => $this->callGroq($prompt, 'tips'),
            default => $this->callGroq($prompt, 'tips'),
        };
    }

    // ==================== PROVIDERS ====================

    private function callGroq(string $prompt, string $task): array
    {
        $apiKey = config('services.groq.api_key');

        if (empty($apiKey)) {
            throw new \Exception('Groq API key not configured. Add GROQ_API_KEY to .env');
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert HR recruiter. Always respond with valid JSON only, no markdown.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'],
            ]);

        return $this->parseResponse($response, 'Groq', $task);
    }

    private function callGemini(string $prompt, string $task): array
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            throw new \Exception('Gemini API key not configured. Add GEMINI_API_KEY to .env');
        }

        $response = Http::timeout(30)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['responseMimeType' => 'application/json'],
            ]);

        if ($response->status() === 429) {
            throw new \Exception('Gemini rate limit exceeded. Try Groq instead or wait 30 seconds.');
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            throw new \Exception('Gemini returned empty response');
        }

        $result = $this->decodeJsonFromText($text);
        if (!$this->isExpectedResult($result, $task)) {
            throw new \Exception('Could not parse Gemini response');
        }

        return $result;
    }

    private function callOpenAI(string $prompt, string $task): array
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured. Add OPENAI_API_KEY to .env');
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert HR recruiter. Always respond with valid JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'],
            ]);

        return $this->parseResponse($response, 'OpenAI', $task);
    }

    // ==================== HELPERS ====================

    private function parseResponse($response, string $provider, string $task): array
    {
        if ($response->failed()) {
            Log::error("{$provider} API error", [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            throw new \Exception("{$provider} API error (HTTP {$response->status()})");
        }

        $data = $response->json();

        // OpenAI/Groq format: choices[0].message.content
        $text = $data['choices'][0]['message']['content'] ?? null;

        if (!$text) {
            throw new \Exception("{$provider} returned empty response");
        }

        $result = $this->decodeJsonFromText($text);
        if (!$this->isExpectedResult($result, $task)) {
            Log::error("{$provider} parse error", ['raw' => $text]);
            throw new \Exception("Could not parse {$provider} response");
        }

        return $result;
    }

    private function decodeJsonFromText(?string $text): ?array
    {
        if (!$text) {
            return null;
        }

        $trimmed = trim($text);
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $withoutFence = preg_replace('/^```(?:json)?\\s*|\\s*```$/i', '', $trimmed);
        $decoded = json_decode(trim((string) $withoutFence), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $trimmed, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function isExpectedResult(?array $result, string $task): bool
    {
        if (!is_array($result)) {
            return false;
        }

        return match ($task) {
            'tips' => isset($result['tips']) && is_array($result['tips']),
            default => isset($result['score']),
        };
    }

    private function buildPrompt(string $resumeText, string $jobDescription): string
    {
        return <<<PROMPT
Analyze the following resume against the job description and provide an evaluation.

**RESUME:**
{$resumeText}

**JOB DESCRIPTION:**
{$jobDescription}

Respond with this exact JSON structure:
{
    "score": <number 0-100>,
    "feedback": "<2-3 paragraph detailed evaluation>",
    "strengths": ["<strength 1>", "<strength 2>", "<strength 3>"],
    "weaknesses": ["<area to improve 1>", "<area to improve 2>", "<area to improve 3>"]
}

Scoring: 90-100 perfect match, 70-89 strong, 50-69 moderate, 30-49 weak, 0-29 poor.
Be specific. Reference actual resume content and job requirements.
PROMPT;
    }

    private function buildTipsPrompt(string $resumeText): string
    {
        return <<<PROMPT
Analyze this resume and provide actionable improvement tips.

**RESUME:**
{$resumeText}

Respond with this exact JSON structure:
{
    "tips": [
        {"title": "<short title>", "description": "<specific actionable advice>", "priority": "high|medium|low"},
        ...
    ]
}

Provide 5-8 specific, actionable tips. Focus on formatting, content gaps, and how to make the resume more impactful.
PROMPT;
    }
}
