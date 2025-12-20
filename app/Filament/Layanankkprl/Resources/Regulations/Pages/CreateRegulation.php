<?php

namespace App\Filament\Layanankkprl\Resources\Regulations\Pages;

use App\Filament\Layanankkprl\Resources\Regulations\RegulationResource;
use App\Jobs\ChunkRegulationJob;
use Filament\Notifications\Notification;
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
            // Dispatch background job for chunking
            ChunkRegulationJob::dispatch($record);
            
            Notification::make()
                ->title('Dokumen sedang diproses')
                ->body('PDF akan di-chunking untuk pencarian AI. Proses selesai dalam beberapa detik.')
                ->info()
                ->send();
        }
    }
}
