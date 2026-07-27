<?php

namespace App\Actions\Mobile;

use App\Models\Assignment;
use App\Models\Client;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateAssignments
{
    public function handle(Client $client, array $scheduleIds, array $userIds, string $status): array
    {
        $schedules = $client->schedules()->whereIn('id', $scheduleIds)->get();
        abort_if($schedules->count() !== count(array_unique($scheduleIds)), 422, 'One or more schedules do not belong to this request.');

        $users = User::query()->where('status', true)->whereIn('id', $userIds)->get();
        abort_if($users->count() !== count(array_unique($userIds)), 422, 'One or more officers are unavailable.');

        $warnings = $this->conflicts($schedules, $users);

        $assignments = DB::transaction(function () use ($schedules, $users, $status): Collection {
            return $schedules->flatMap(function (Schedule $schedule) use ($users, $status): Collection {
                return $users->map(fn (User $user): Assignment => Assignment::firstOrCreate(
                    ['schedule_id' => $schedule->id, 'user_id' => $user->id],
                    ['status' => $status, 'score' => null],
                ));
            });
        });

        return [$assignments, $warnings];
    }

    private function conflicts(Collection $schedules, Collection $users): array
    {
        $warnings = [];

        foreach ($users as $user) {
            foreach ($schedules as $schedule) {
                $hasConflict = Assignment::query()
                    ->where('user_id', $user->id)
                    ->whereHas('schedule', fn ($query) => $query
                        ->whereDate('date', $schedule->date)
                        ->where('start_time', '<', $schedule->end_time)
                        ->where('end_time', '>', $schedule->start_time))
                    ->exists();

                if ($hasConflict) {
                    $warnings[] = [
                        'user_id' => $user->id,
                        'schedule_id' => $schedule->id,
                        'message' => "{$user->name} has an overlapping assignment.",
                    ];
                }
            }
        }

        return $warnings;
    }
}
