<fieldset class="min-w-0 rounded-xl border border-slate-500 p-4" @disabled($locked)>
    <legend class="px-2 text-sm font-semibold text-slate-100">{{ $field['label'] }}</legend>
    <div class="space-y-4">
        @forelse(($previewData['reclamation_schedule_rows'] ?? []) as $index => $row)
            <div wire:key="preview-schedule-{{ $index }}" class="rounded-lg border border-slate-600 p-3">
                <p class="mb-3 text-sm font-semibold">Kegiatan {{ $index + 1 }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach(['activity' => 'Kegiatan', 'start_date' => 'Tanggal mulai', 'end_date' => 'Tanggal selesai', 'notes' => 'Catatan'] as $column => $label)
                        <div class="min-w-0">
                            <label for="preview-schedule-{{ $index }}-{{ $column }}" class="mb-1 block text-sm text-slate-200">{{ $label }}</label>
                            <input
                                id="preview-schedule-{{ $index }}-{{ $column }}"
                                name="preview_schedule_{{ $index }}_{{ $column }}"
                                type="{{ in_array($column, ['start_date', 'end_date']) ? 'date' : 'text' }}"
                                wire:model.live.blur="previewData.reclamation_schedule_rows.{{ $index }}.{{ $column }}"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage,confirmAndSave"
                                @if($errors->has('previewData.reclamation_schedule_rows.'.$index.'.'.$column)) aria-invalid="true" @endif
                                class="block w-full min-w-0 rounded-lg border border-slate-500 bg-slate-900 p-3 text-sm text-white focus:ring-2 focus:ring-blue-400 outline-none"
                            >
                            @error('previewData.reclamation_schedule_rows.'.$index.'.'.$column)
                                <p role="alert" class="mt-1 text-sm text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
                <button type="button" wire:click="removeScheduleRow({{ $index }})" wire:loading.attr="disabled" class="mt-3 rounded-lg px-3 py-2 text-sm text-red-200 underline focus:ring-2 focus:ring-blue-400">Hapus kegiatan {{ $index + 1 }}</button>
            </div>
        @empty
            <p class="text-sm text-slate-300">Jadwal belum diisi. Tambahkan kegiatan atau jelaskan jadwal melalui chat.</p>
        @endforelse
        <button type="button" wire:click="addScheduleRow" wire:loading.attr="disabled" class="rounded-lg border border-slate-400 px-4 py-3 text-sm font-semibold text-white focus:ring-2 focus:ring-blue-400">Tambah kegiatan</button>
    </div>
</fieldset>
