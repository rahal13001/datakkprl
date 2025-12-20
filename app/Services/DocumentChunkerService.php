<?php

namespace App\Services;

use App\Models\Regulation;
use App\Models\RegulationChunk;
use Illuminate\Support\Str;

class DocumentChunkerService
{
    protected AiService $aiService;
    
    /**
     * Target words per chunk.
     */
    protected int $targetWordsPerChunk = 500;
    
    /**
     * Overlap words between chunks for context continuity.
     */
    protected int $overlapWords = 50;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Chunk a regulation document.
     */
    public function chunkDocument(Regulation $regulation): int
    {
        // Clear existing chunks
        $this->clearChunks($regulation);

        // Get PDF text
        $text = $this->extractText($regulation);
        
        if (empty($text)) {
            return 0;
        }

        // Split into chunks
        $chunks = $this->splitIntoChunks($text);

        // Save chunks
        foreach ($chunks as $index => $chunkText) {
            RegulationChunk::create([
                'regulation_id' => $regulation->id,
                'chunk_index' => $index,
                'chunk_text' => $chunkText,
                'word_count' => str_word_count($chunkText),
            ]);
        }

        return count($chunks);
    }

    /**
     * Extract text from regulation PDF.
     */
    protected function extractText(Regulation $regulation): ?string
    {
        if (!$regulation->file_path) {
            return null;
        }

        // First try to use existing extracted_text
        if (!empty($regulation->extracted_text)) {
            return $regulation->extracted_text;
        }

        // Parse PDF using AiService
        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($regulation->file_path);
        $text = $this->aiService->parsePdf($path);

        // Save extracted text to regulation
        if ($text) {
            $regulation->update(['extracted_text' => Str::limit($text, 65000)]); // MySQL TEXT limit
        }

        return $text;
    }

    /**
     * Split text into chunks with overlap.
     */
    public function splitIntoChunks(string $text): array
    {
        $chunks = [];
        
        // Clean text
        $text = $this->cleanText($text);
        
        // Split into paragraphs first
        $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $currentChunk = '';
        $currentWordCount = 0;

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            $paragraphWordCount = str_word_count($paragraph);

            // If adding this paragraph exceeds target, start new chunk
            if ($currentWordCount + $paragraphWordCount > $this->targetWordsPerChunk && $currentWordCount > 0) {
                $chunks[] = trim($currentChunk);
                
                // Start new chunk with overlap from previous
                $words = explode(' ', $currentChunk);
                $overlapText = implode(' ', array_slice($words, -$this->overlapWords));
                $currentChunk = $overlapText . "\n\n" . $paragraph;
                $currentWordCount = str_word_count($currentChunk);
            } else {
                $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
                $currentWordCount += $paragraphWordCount;
            }
        }

        // Don't forget the last chunk
        if (!empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Clean text for better chunking.
     */
    protected function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        // Normalize line breaks
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        
        // Remove excessive newlines (more than 2)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Clear all chunks for a regulation.
     */
    public function clearChunks(Regulation $regulation): void
    {
        $regulation->chunks()->delete();
    }

    /**
     * Re-chunk a regulation (clear and chunk again).
     */
    public function rechunk(Regulation $regulation): int
    {
        return $this->chunkDocument($regulation);
    }
}
