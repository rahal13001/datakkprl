<?php

namespace App\Filament\Layanankkprl\Widgets;

use App\Models\LearningActivityLog;
use App\Models\LearningMaterialAccess;
use App\Models\LearningMaterialOpen;
use App\Models\LearningSession;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LearningAnalyticsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $topGroup = LearningMaterialAccess::query()->whereNotNull('group_title')->selectRaw('group_title, SUM(visit_count) as visits')->groupBy('group_title')->orderByDesc('visits')->first();
        $topMaterial = LearningMaterialOpen::query()->selectRaw('material_title, SUM(open_count) as opens')->groupBy('material_title')->orderByDesc('opens')->first();

        $stats = [
            Stat::make('Akses Grup', LearningMaterialAccess::count())->description(LearningMaterialAccess::whereDate('created_at', today())->count().' hari ini'),
            Stat::make('Akses Unik', LearningMaterialAccess::distinct('access_uuid')->count('access_uuid'))->description('Berdasarkan akses per grup, bukan orang unik'),
            Stat::make('Grup Terpopuler', $topGroup?->group_title ?? '-')->description($topGroup ? number_format($topGroup->visits).' kunjungan' : 'Belum ada data'),
            Stat::make('Materi Terpopuler', $topMaterial?->material_title ?? '-')->description($topMaterial ? number_format($topMaterial->opens).' kali dibuka' : 'Belum ada data'),
        ];

        if (! config('learning.detailed_tracking_enabled')) {
            return $stats;
        }

        return [
            ...$stats,
            Stat::make('Sesi', LearningSession::count())->description(LearningSession::whereDate('started_at', today())->count().' hari ini'),
            Stat::make('Aktivitas', LearningActivityLog::count()),
        ];
    }
}
