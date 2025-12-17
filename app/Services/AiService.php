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
    protected $groqApiKey;
    protected $parser;

    public function __construct()
    {
        $this->groqApiKey = config('services.groq.key');
        $this->parser = new Parser();
    }

    /**
     * Ask LLM a question based on RAG context.
     */
    public function askGemini($sessionId, $question, $ipAddress = null)
    {
        // 1. Context Search (Simple LIKE for now)
        $context = $this->searchContext($question);

        // 2. System Prompt
        $systemPrompt = "Kamu adalah Kawan Ruang Laut AI, asisten virtual untuk layanan KKPRL (Kesesuaian Kegiatan Pemanfaatan Ruang Laut) di LPSPL Sorong, KKP Indonesia. " .
                        "Jawab pertanyaan pengguna berdasarkan Context yang diberikan. " .
                        "Jika jawaban tidak ada di context, sarankan untuk booking konsultasi via website kami. " .
                        "Jangan membuat aturan sendiri. Gunakan bahasa Indonesia yang formal tapi ramah.";

        // 3. API Call to Groq (OpenAI-compatible format)
        $response = 'Maaf, layanan AI sedang tidak tersedia (API Key missing).';

        if ($this->groqApiKey) {
            try {
                $apiResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post("https://api.groq.com/openai/v1/chat/completions", [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Context:\n" . $context . "\n\nPertanyaan: " . $question]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                ]);

                if ($apiResponse->successful()) {
                    $response = $apiResponse->json()['choices'][0]['message']['content'] ?? 'Maaf, saya tidak dapat memproses jawaban.';
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
