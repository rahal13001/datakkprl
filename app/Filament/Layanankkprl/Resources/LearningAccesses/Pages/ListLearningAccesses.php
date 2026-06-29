<?php

namespace App\Filament\Layanankkprl\Resources\LearningAccesses\Pages;

use App\Filament\Layanankkprl\Resources\LearningAccesses\LearningAccessResource;
use App\Filament\Layanankkprl\Widgets\LearningAnalyticsOverview;
use Filament\Resources\Pages\ListRecords;

class ListLearningAccesses extends ListRecords
{
    protected static string $resource = LearningAccessResource::class;

    protected function getHeaderWidgets(): array
    {
        return [LearningAnalyticsOverview::class];
    }
}
