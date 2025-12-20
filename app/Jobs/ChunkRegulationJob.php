<?php

namespace App\Jobs;

use App\Models\Regulation;
use App\Services\DocumentChunkerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChunkRegulationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes for large PDFs

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Regulation $regulation
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DocumentChunkerService $chunker): void
    {
        try {
            Log::info("Starting chunking for regulation: {$this->regulation->title}");

            $chunkCount = $chunker->chunkDocument($this->regulation);

            // Mark as chunked
            $this->regulation->update(['is_chunked' => true]);

            Log::info("Completed chunking for regulation: {$this->regulation->title}, chunks: {$chunkCount}");

        } catch (\Exception $e) {
            Log::error("Failed to chunk regulation {$this->regulation->id}: " . $e->getMessage());
            
            // Mark as not chunked on failure
            $this->regulation->update(['is_chunked' => false]);
            
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ChunkRegulationJob permanently failed for regulation {$this->regulation->id}: " . $exception->getMessage());
    }
}
