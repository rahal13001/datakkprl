<?php

namespace App\Services;

use App\Models\AiChatLog;
use App\Models\Faq;
use App\Models\Regulation;
use App\Models\RegulationChunk;
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
     * Ask LLM a question based on RAG context with multi-turn support.
     */
    public function askGemini($sessionId, $question, $ipAddress = null, array $conversationHistory = [])
    {
        // 1. Context Search
        $context = $this->searchContext($question);

        // 2. System Prompt with markdown formatting instruction
        $systemPrompt = "Kamu adalah Kawan Ruang Laut AI, asisten virtual untuk layanan KKPRL (Kesesuaian Kegiatan Pemanfaatan Ruang Laut) di LPSPL Sorong, KKP Indonesia. " .
                        "Jawab pertanyaan pengguna berdasarkan Context yang diberikan. " .
                        "Jika jawaban tidak ada di context, sarankan untuk booking konsultasi via website kami. " .
                        "Jangan membuat aturan sendiri. Gunakan bahasa Indonesia yang formal tapi ramah. " .
                        "Format jawaban dengan markdown jika diperlukan (gunakan **bold**, *italic*, dan daftar).";

        // 3. API Call to Groq (OpenAI-compatible format)
        $response = 'Maaf, layanan AI sedang tidak tersedia (API Key missing).';

        if ($this->groqApiKey) {
            try {
                // Build messages array with conversation history
                $messages = [
                    ['role' => 'system', 'content' => $systemPrompt],
                ];

                // Add conversation history for multi-turn context
                foreach ($conversationHistory as $msg) {
                    if (isset($msg['role']) && isset($msg['content'])) {
                        $messages[] = [
                            'role' => $msg['role'] === 'assistant' ? 'assistant' : 'user',
                            'content' => $msg['content']
                        ];
                    }
                }

                // Add current question with context
                $messages[] = [
                    'role' => 'user', 
                    'content' => "Context:\n" . $context . "\n\nPertanyaan: " . $question
                ];

                $apiResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post("https://api.groq.com/openai/v1/chat/completions", [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => $messages,
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
     * Search relevant context from DB using FULLTEXT search.
     */
    protected function searchContext($query)
    {
        $context = "";

        // Search FAQs (keep LIKE for now, FAQs are short)
        $faqs = Faq::where('is_published', true)
            ->where(function($q) use ($query) {
                $q->where('question', 'LIKE', "%{$query}%")
                  ->orWhere('answer', 'LIKE', "%{$query}%");
            })
            ->take(3)
            ->get();

        foreach ($faqs as $faq) {
            $context .= "FAQ Q: {$faq->question}\nFAQ A: {$faq->answer}\n---\n";
        }

        // Search Regulation Chunks using FULLTEXT
        $chunks = RegulationChunk::fromPublishedRegulations()
            ->search($query)
            ->limit(5)
            ->get();

        foreach ($chunks as $chunk) {
            $context .= "Regulasi: {$chunk->regulation->title}\n";
            $context .= "Isi: {$chunk->chunk_text}\n---\n";
        }

        // Fallback: If no chunks found, try searching old extracted_text
        if ($chunks->isEmpty()) {
            $regulations = Regulation::where('is_published', true)
                ->where('is_chunked', false)
                ->where(function($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%")
                      ->orWhere('extracted_text', 'LIKE', "%{$query}%");
                })
                ->take(3)
                ->get();

            foreach ($regulations as $reg) {
                $context .= "Regulasi: {$reg->title}\nDetails: " . Str::limit($reg->extracted_text ?? $reg->description, 1000) . "\n---\n";
            }
        }

        return $context ?: "Tidak ada konteks spesifik ditemukan.";
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
