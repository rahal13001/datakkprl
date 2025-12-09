<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Client;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingAvailabilityService
{
    // Sorong Timezone
    const TIMEZONE = 'Asia/Jayapura';

    /**
     * Check if a specific officer is available at a specific time.
     * Iron Dome Logic: Strict anti-clash with buffer time.
     */
    public function checkOfficerAvailability($userId, $date, $startTime, $endTime): bool
    {
        $start = Carbon::parse("$date $startTime", self::TIMEZONE);
        $end = Carbon::parse("$date $endTime", self::TIMEZONE);
        
        // 1. Check if User exists and is active (logic to be refined based on User structure)
        // For now assume all users are potential officers if passed here.

        // 2. Overlap Check with Buffer (15 mins)
        // NewStart < ExistingEnd + 15mins  AND  NewEnd > ExistingStart - 15mins
        $allAssignments = Assignment::where('user_id', $userId)
            ->with(['schedule' => function($q) {
                // Preload all, filter in PHP for robustness
            }])
            ->get();
            
        // Filter only relevant date in PHP to assume date casting correctness
        $conflicts = $allAssignments->filter(function ($assignment) use ($date) {
            return $assignment->schedule && $assignment->schedule->date->format('Y-m-d') === $date;
        });

        foreach ($conflicts as $assignment) {
            $existingStart = Carbon::parse($date . ' ' . $assignment->schedule->start_time, self::TIMEZONE);
            $existingEnd = Carbon::parse($date . ' ' . $assignment->schedule->end_time, self::TIMEZONE);

            // Add buffer
            $bufferedExistingStart = $existingStart->copy()->subMinutes(15);
            $bufferedExistingEnd = $existingEnd->copy()->addMinutes(15);

            // Check overlap
            if ($start->lt($bufferedExistingEnd) && $end->gt($bufferedExistingStart)) {
                return false; // Conflict found
            }
        }

        return true;
    }

    /**
     * Get available slots count for a date.
     * Formula: RemainingQuota = (TotalOfficers - OfficersOnLeave) - ExistingBookings
     */
    public function getAvailableSlots($date): int
    {
        // 1. Total Officers (Hardcoded or Role based?)
        // Assuming 'officer' role or similar. For now count all users.
        $totalOfficers = User::count(); 

        // 2. Check Holidays (Global unavailability)
        if (Holiday::where('date', $date)->exists()) {
            return 0;
        }

        // 3. Check Weekend/Friday Limit check
        $carbonDate = Carbon::parse($date, self::TIMEZONE);
        if ($carbonDate->isWeekend()) {
            return 0;
        }
        
        // 4. Officers On Leave (Not implemented in schema yet, assume 0)
        $officersOnLeave = 0;

        // 5. Existing Bookings (Schedules count)
        // This logic is tricky. Slots are time-based. 
        // "Available Slots" usually means "How many more bookings can be made today?".
        // If 10 officers, and we have 5 bookings at 09:00, we have 5 slots left at 09:00.
        // But the question asks for a general number? "RemainingQuota". 
        // Let's assume daily capacity = TotalOfficers * HoursPerDay.
        // Or simpler: How many concurrent sessions can happen right now?
        // Let's implement a simpler generic quota for standard "tickets" per day.
        
        // Let's count total schedules for that date.
        $existingBookings = Schedule::where('date', $date)->count();

        // Assume Max Daily Capacity = Total Officers * 5 slots/day
        $maxDailyCapacity = $totalOfficers * 5; 

        return max(0, ($maxDailyCapacity - $existingBookings));
    }

    /**
     * Validate operational hours.
     */
    public function validateOperationalHours($date, $startTime): bool
    {
        $carbonDate = Carbon::parse($date, self::TIMEZONE);
        
        // Holidays
        if (Holiday::where('date', $date)->exists()) {
             return false;
        }

        // Weekends
        if ($carbonDate->isWeekend()) {
            return false;
        }

        $time = Carbon::parse($startTime, self::TIMEZONE);
        $hour = $time->hour;

        // Friday: 08:00 - 11:00 (Close early)
        if ($carbonDate->isFriday()) {
            return ($hour >= 8 && $hour < 11);
        }

        // Mon-Thu: 08:00 - 15:00
        return ($hour >= 8 && $hour < 15);
    }
}
