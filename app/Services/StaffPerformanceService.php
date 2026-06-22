<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StaffPerformanceService
{
    public function summaryQuery(?string $from = null, ?string $until = null, bool $activeOnly = true): Builder
    {
        $ratedAssignmentsQuery = $this->applyDateRange(
            Assignment::query()
                ->join('schedules', 'schedules.id', '=', 'assignments.schedule_id')
                ->whereColumn('assignments.user_id', 'users.id')
                ->whereNull('assignments.deleted_at')
                ->whereNull('schedules.deleted_at')
                ->whereNotNull('assignments.score'),
            $from,
            $until,
        );

        $serviceActivitiesQuery = $this->applyDateRange(
            Client::query()
                ->join('schedules', 'schedules.client_id', '=', 'clients.id')
                ->join('assignments', 'assignments.schedule_id', '=', 'schedules.id')
                ->whereColumn('assignments.user_id', 'users.id')
                ->whereNull('clients.deleted_at')
                ->whereNull('schedules.deleted_at')
                ->whereNull('assignments.deleted_at'),
            $from,
            $until,
        );

        return User::query()
            ->select('users.id', 'users.name', 'users.jabatan')
            ->when($activeOnly, fn (Builder $query) => $query->where('users.status', true))
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'Pegawai'))
                    ->orWhereHas('assignments', fn (Builder $assignmentQuery) => $assignmentQuery->whereNull('assignments.deleted_at'));
            })
            ->selectSub(
                (clone $ratedAssignmentsQuery)->selectRaw('COUNT(assignments.id)'),
                'rated_sessions',
            )
            ->selectSub(
                (clone $serviceActivitiesQuery)->selectRaw('COUNT(DISTINCT clients.id)'),
                'service_activities_count',
            )
            ->selectSub(
                (clone $ratedAssignmentsQuery)->selectRaw('COALESCE(SUM(assignments.score), 0)'),
                'total_score',
            )
            ->selectSub(
                (clone $ratedAssignmentsQuery)->selectRaw('COALESCE(ROUND(AVG(assignments.score), 2), 0)'),
                'average_score',
            )
            ->selectSub(
                (clone $ratedAssignmentsQuery)->selectRaw('COALESCE(ROUND(AVG(assignments.score) / 2, 2), 0)'),
                'average_stars',
            )
            ->selectSub(
                (clone $ratedAssignmentsQuery)->selectRaw('COALESCE(MAX(assignments.score), 0)'),
                'highest_score',
            );
    }

    public function serviceBreakdown(User|int $user, ?string $from = null, ?string $until = null): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        $countsByService = $this->applyDateRange(
            Client::query()
                ->join('schedules', 'schedules.client_id', '=', 'clients.id')
                ->join('assignments', 'assignments.schedule_id', '=', 'schedules.id')
                ->where('assignments.user_id', $userId)
                ->whereNull('clients.deleted_at')
                ->whereNull('schedules.deleted_at')
                ->whereNull('assignments.deleted_at'),
            $from,
            $until,
        )
            ->selectRaw('clients.service_id, COUNT(DISTINCT clients.id) as total')
            ->groupBy('clients.service_id')
            ->pluck('total', 'clients.service_id');

        return Service::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Service $service): array => [
                'name' => $service->name,
                'count' => (int) ($countsByService[$service->id] ?? 0),
            ]);
    }

    public function initials(string $name): string
    {
        $parts = Str::of($name)
            ->squish()
            ->explode(' ')
            ->filter()
            ->take(3)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)).'.');

        return $parts->isNotEmpty() ? $parts->implode(' ') : '-';
    }

    protected function applyDateRange(Builder $query, ?string $from, ?string $until): Builder
    {
        return $query
            ->when(
                $from,
                fn (Builder $query) => $query->whereDate('schedules.date', '>=', $from),
            )
            ->when(
                $until,
                fn (Builder $query) => $query->whereDate('schedules.date', '<=', $until),
            );
    }
}
