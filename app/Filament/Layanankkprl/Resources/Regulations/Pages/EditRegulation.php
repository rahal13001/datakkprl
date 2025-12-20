<?php

namespace App\Filament\Layanankkprl\Resources\Regulations\Pages;

use App\Filament\Layanankkprl\Resources\Regulations\RegulationResource;
use App\Jobs\ChunkRegulationJob;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRegulation extends EditRecord
{
    protected static string $resource = RegulationResource::class;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Only re-chunk if file changed
        if ($record->wasChanged('file_path') && $record->file_path) {
            // Reset chunked status
            $record->update([
                'is_chunked' => false,
                'extracted_text' => null,
            ]);
            
            // Dispatch background job for re-chunking
            ChunkRegulationJob::dispatch($record);
            
            Notification::make()
                ->title('Dokumen sedang diproses ulang')
                ->body('PDF akan di-chunking ulang untuk pencarian AI.')
                ->info()
                ->send();
        }
    }
}
