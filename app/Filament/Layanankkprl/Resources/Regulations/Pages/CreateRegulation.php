<?php

namespace App\Filament\Layanankkprl\Resources\Regulations\Pages;

use App\Filament\Layanankkprl\Resources\Regulations\RegulationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRegulation extends CreateRecord
{
    protected static string $resource = RegulationResource::class;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        
        if ($record->file_path) {
            try {
                $path = \Illuminate\Support\Facades\Storage::disk('public')->path($record->file_path);
                $text = app(\App\Services\AiService::class)->parsePdf($path);
                
                if ($text) {
                    $record->update(['extracted_text' => \Illuminate\Support\Str::limit($text, 10000)]);
                }
            } catch (\Exception $e) {
                // Log or ignore if parser fails
            }
        }
    }
}
