<?php

namespace App\Services;

use App\Models\AiChatLog;
use App\Models\Faq;
use App\Models\Regulation;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;
use Exception;

class AiService
{
    protected $geminiApiKey;
    protected $parser;

    public function __construct()
    {
        $this->geminiApiKey = config('services.gemini.key'); // Needs explicit config
        $this->parser = new Parser();
    }

    /**
     * Ask Gemini a question based on RAG context.
     */
    public function askGemini($sessionId, $question, $ipAddress = null)
    {
        // 1. Context Search (Simple LIKE for now)
        $context = $this->searchContext($question);

        // 2. Prompt Construction
        $systemPrompt = "You are an AI Assistant for KKPRL (Kesesuaian Kegiatan Pemanfaatan Ruang Laut) in Sorong, under KKP Indonesia. " .
                        "Answer the user's question based strictly on the provided Context. " .
                        "If the answer is not in the context, politely suggest them to book a consultation via our website. " .
                        "Do not hallucinate rules. Speak in formal but helpful Indonesian.";
        
        $userPrompt = "Context:\n" . $context . "\n\nQuestion: " . $question;

        // 3. API Call to Gemini (v1beta models)
        // Using direct REST API for dependency minimization if SDK not present
        $response = 'Maaf, layanan AI sedang tidak tersedia (API Key missing).';

        if ($this->geminiApiKey) {
            try {
                $apiResponse = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiApiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt . "\n\n" . $userPrompt]
                            ]
                        ]
                    ]
                ]);

                if ($apiResponse->successful()) {
                    $response = $apiResponse->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, saya tidak dapat memproses jawaban.';
                } else {
                    $response = 'Error: ' . $apiResponse->status() . ' - ' . $apiResponse->body();
                }

            } catch (Exception $e) {
                $response = 'System Error: ' . $e->getMessage();
            }
        }

        // 4. Logging
        AiChatLog::create([
            'session_id' => $sessionId,
            'question' => $question,
            'response' => $response,
            'ip_address' => $ipAddress,
        ]);

        return $response;
    }

    /**
     * Search relevant context from DB.
     */
    protected function searchContext($query)
    {
        $limit = 3;
        $context = "";

        // Search FAQs
        $faqs = Faq::where('is_published', true)
            ->where(function($q) use ($query) {
                $q->where('question', 'LIKE', "%{$query}%")
                  ->orWhere('answer', 'LIKE', "%{$query}%");
            })
            ->take($limit)
            ->get();

        foreach ($faqs as $faq) {
            $context .= "FAQ Q: {$faq->question}\nFAQ A: {$faq->answer}\n---\n";
        }

        // Search Regulations
        $regulations = Regulation::where('is_published', true)
            ->where(function($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('extracted_text', 'LIKE', "%{$query}%");
            })
            ->take($limit)
            ->get();

        foreach ($regulations as $reg) {
            $context .= "Regulation: {$reg->title}\nDetails: " . Str::limit($reg->extracted_text ?? $reg->description, 500) . "\n---\n";
        }

        return $context ?: "No specific context found.";
    }

    /**
     * Parse PDF to text (Helper method)
     */
    public function parsePdf($filePath)
    {
        try {
            $pdf = $this->parser->parseFile($filePath);
            return $pdf->getText();
        } catch (Exception $e) {
            return null;
        }
    }
}
