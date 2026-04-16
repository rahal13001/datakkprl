<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

/**
 * Calculates business days, skipping weekends and holidays
 * from the holidays table.
 */
class BusinessDayService
{
    /**
     * Add N business days to a given date.
     *
     * @param Carbon $from Start date
     * @param int $days Number of business days to add
     * @return Carbon The resulting date
     */
    public function addBusinessDays(Carbon $from, int $days): Carbon
    {
        $date = $from->copy();
        $added = 0;

        // Pre-load holidays in the rough range to avoid N+1 queries
        $maxCalendarDays = $days * 3; // generous buffer
        $holidays = Holiday::whereBetween('date', [
            $date->copy()->toDateString(),
            $date->copy()->addDays($maxCalendarDays)->toDateString(),
        ])->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->toArray();

        while ($added < $days) {
            $date->addDay();

            // Skip weekends (Saturday=6, Sunday=0)
            if ($date->isSaturday() || $date->isSunday()) {
                continue;
            }

            // Skip holidays
            if (in_array($date->toDateString(), $holidays)) {
                continue;
            }

            $added++;
        }

        return $date;
    }

    /**
     * Check if the deadline (N business days from a date) has passed.
     *
     * @param Carbon $from Start date
     * @param int $days Number of business days
     * @return bool
     */
    public function isDeadlinePassed(Carbon $from, int $days): bool
    {
        $deadline = $this->addBusinessDays($from, $days);
        return Carbon::now()->greaterThan($deadline->endOfDay());
    }

    /**
     * Calculate the deadline date (N business days from a date).
     *
     * @param Carbon $from Start date
     * @param int $days Number of business days
     * @return Carbon
     */
    public function getDeadline(Carbon $from, int $days): Carbon
    {
        return $this->addBusinessDays($from, $days);
    }
}
