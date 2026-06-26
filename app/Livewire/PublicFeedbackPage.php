<?php

namespace App\Livewire;

use App\Models\PublicFeedback;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PublicFeedbackPage extends Component
{
    public $is_anonymous = true;

    public ?string $submitter_name = null;

    public string $feedback = '';

    public ?string $suggestion = null;

    public array $selectedOfficerIds = [];

    public bool $hasSubmitted = false;

    public function updatedIsAnonymous($value): void
    {
        if ((bool) $value) {
            $this->submitter_name = null;
        }
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'is_anonymous' => ['required', 'boolean'],
            'submitter_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn (): bool => ! $this->is_anonymous),
            ],
            'feedback' => ['required', 'string', 'min:10'],
            'suggestion' => ['nullable', 'string', 'max:3000'],
            'selectedOfficerIds' => ['nullable', 'array'],
            'selectedOfficerIds.*' => ['integer', 'exists:users,id'],
        ], [
            'submitter_name.required' => 'Nama pengirim wajib diisi jika tidak memilih anonim.',
            'feedback.required' => 'Masukan utama wajib diisi.',
            'feedback.min' => 'Masukan utama minimal 10 karakter.',
            'selectedOfficerIds.*.exists' => 'Petugas yang dipilih tidak valid.',
        ]);

        DB::transaction(function () use ($validated): void {
            $feedback = PublicFeedback::create([
                'is_anonymous' => (bool) $validated['is_anonymous'],
                'submitter_name' => $validated['is_anonymous']
                    ? null
                    : $validated['submitter_name'],
                'feedback' => $validated['feedback'],
                'suggestion' => $validated['suggestion'] ?: null,
            ]);

            $feedback->users()->sync($validated['selectedOfficerIds'] ?? []);
        });

        $this->reset([
            'submitter_name',
            'feedback',
            'suggestion',
            'selectedOfficerIds',
        ]);

        $this->is_anonymous = true;
        $this->hasSubmitted = true;

        Notification::make()
            ->title('Masukan publik berhasil dikirim.')
            ->success()
            ->send();
    }

    public function availableOfficers(): Collection
    {
        return User::query()
            ->select('users.id', 'users.name', 'users.jabatan')
            ->where('users.status', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'Pegawai'))
                    ->orWhereHas('assignments', fn (Builder $assignmentQuery) => $assignmentQuery->whereNull('assignments.deleted_at'));
            })
            ->orderBy('users.name')
            ->get()
            ->unique('id')
            ->values();
    }

    public function render()
    {
        return view('livewire.public-feedback-page', [
            'availableOfficers' => $this->availableOfficers(),
        ]);
    }
}
