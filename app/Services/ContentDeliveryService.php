<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\Regulation;
use Illuminate\Support\Facades\Cache;

class ContentDeliveryService
{
    /**
     * Get Published FAQs. Cache 24 hours.
     */
    public function getFaqs()
    {
        return Cache::remember('faqs_published', 60 * 60 * 24, function () {
            return Faq::where('is_published', true)
                ->orderBy('sort_order', 'asc')
                ->get();
        });
    }

    /**
     * Get Published Regulations. Cache 24 hours.
     */
    public function getRegulations()
    {
        return Cache::remember('regulations_published', 60 * 60 * 24, function () {
            return Regulation::where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Increment download count without clearing cache.
     * We update the DB directly. The cached collection data (title, file_path) remains valid.
     * The download_count in the cache might be stale, but that's acceptable for performance.
     */
    public function incrementDownloadCount($regulationId)
    {
        Regulation::where('id', $regulationId)->increment('download_count');
    }
}
