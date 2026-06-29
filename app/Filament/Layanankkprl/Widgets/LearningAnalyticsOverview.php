<?php

namespace App\Filament\Layanankkprl\Widgets;

use App\Models\LearningActivityLog;
use App\Models\LearningMaterialAccess;
use App\Models\LearningSession;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LearningAnalyticsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $top = LearningMaterialAccess::query()->selectRaw('material_title, SUM(visit_count) as visits')->groupBy('material_title')->orderByDesc('visits')->first();

        return [
            Stat::make('Akses Materi', LearningMaterialAccess::count())->description(LearningMaterialAccess::whereDate('created_at', today())->count().' hari ini'),
            Stat::make('Akses Unik', LearningMaterialAccess::distinct('access_uuid')->count('access_uuid'))->description('Berdasarkan akses per materi, bukan orang unik'),
            Stat::make('Sesi', LearningSession::count())->description(LearningSession::whereDate('started_at', today())->count().' hari ini'),
            Stat::make('Aktivitas', LearningActivityLog::count()),
            Stat::make('Materi Terpopuler', $top?->material_title ?? '-')->description($top ? number_format($top->visits).' kunjungan' : 'Belum ada data'),
        ];
    }
}
