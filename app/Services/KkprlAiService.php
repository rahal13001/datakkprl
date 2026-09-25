<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KkprlAiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    private string $model = 'gemini-3.8-flash';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY', '');
    }

    /**
     * Send a conversation to Gemini API and get the response.
     *
     * @param array $messages Array of messages [['role' => 'user|model', 'content' => 'text'], ...]
     * @param string $systemPrompt Instructions for the AI
     * @return string|null The AI's response or null on failure
     */
    public function chat(array $messages, string $systemPrompt): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('KkprlAiService: GEMINI_API_KEY is not set.');
            return null;
        }

        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        // Convert our message format to Gemini's expected format
        $contents = [];
        foreach ($messages as $msg) {
            if (isset($msg['role']) && $msg['role'] === 'system_preview') {
                continue;
            }
            
            $contents[] = [
                'role' => $msg['role'] === 'ai' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.4, // Lower temp for more deterministic/factual responses
            ]
        ];

        try {
            $response = Http::timeout(30)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('KkprlAiService Gemini Error: ' . $response->body());
            return null;
        } catch (\Throwable $th) {
            Log::error('KkprlAiService Exception: ' . $th->getMessage());
            return null;
        }
    }

    /**
     * Use Gemini Vision to describe an uploaded non-sensitive image
     */
    public function analyzeImage(string $base64Image, string $mimeType, string $prompt): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = Http::timeout(30)->post($url, $payload);
            if ($response->successful()) {
                return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }
            Log::error('KkprlAiService Vision Error: ' . $response->body());
            return null;
        } catch (\Throwable $th) {
            Log::error('KkprlAiService Vision Exception: ' . $th->getMessage());
            return null;
        }
    }
}
