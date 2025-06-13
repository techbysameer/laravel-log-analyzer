<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private $apiKey;
    private $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function analyzeError($errorMessage, $stackTrace = null, $context = [])
    {
        $prompt = $this->buildAnalysisPrompt($errorMessage, $stackTrace, $context);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a Laravel expert. Analyze errors and provide concise, actionable solutions.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.3
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'No analysis available';
            }

            return 'Error analyzing with AI: ' . $response->body();
        } catch (\Exception $e) {
            Log::error('OpenAI API error: ' . $e->getMessage());
            return 'AI analysis temporarily unavailable';
        }
    }

    private function buildAnalysisPrompt($errorMessage, $stackTrace, $context)
    {
        $prompt = "Analyze this Laravel error and provide a solution:\n\n";
        $prompt .= "Error: {$errorMessage}\n\n";

        if ($stackTrace) {
            $prompt .= "Stack Trace:\n{$stackTrace}\n\n";
        }

        if (!empty($context)) {
            $prompt .= "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n\n";
        }

        $prompt .= "Please provide:\n";
        $prompt .= "1. Root cause analysis\n";
        $prompt .= "2. Specific steps to fix\n";
        $prompt .= "3. Prevention tips\n";
        $prompt .= "Keep response under 300 words.";

        return $prompt;
    }

    public function generatePatternSuggestion($pattern)
    {
        $prompt = "This Laravel error pattern occurs frequently:\n\n";
        $prompt .= "Category: {$pattern->category}\n";
        $prompt .= "Pattern: {$pattern->common_message}\n";
        $prompt .= "Occurrences: {$pattern->occurrence_count}\n\n";
        $prompt .= "Provide a general solution strategy for this type of error. Be concise.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a Laravel expert providing pattern-based solutions.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 200,
                'temperature' => 0.3
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('OpenAI Pattern Analysis error: ' . $e->getMessage());
        }

        return null;
    }
}