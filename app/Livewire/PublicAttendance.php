<?php

namespace App\Livewire;

use App\Models\BeritaAcara;
use App\Models\BeritaAcaraAttendee;
use App\Services\SignatureService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
class PublicAttendance extends Component
{
    public BeritaAcara $beritaAcara;

    // Form fields
    public string $nama = '';
    public string $jabatan = '';
    public string $instansi = '';
    public string $email = '';
    public string $no_hp = '';
    public ?string $tanda_tangan = null;

    // State
    public bool $successfullySubmitted = false;
    public bool $autoApproved = false;

    public function mount(string $token)
    {
        $tokenHash = app(\App\Services\DataHashService::class)->token($token);

        $this->beritaAcara = BeritaAcara::where(function ($query) use ($token, $tokenHash) {
                $query->where('attendance_url_token_hash', $tokenHash)
                    ->orWhere(function ($legacyQuery) use ($token) {
                        $legacyQuery->whereNull('attendance_url_token_hash')
                            ->where('attendance_url_token', $token);
                    });
            })
            ->with('client.service', 'client.consultationLocation')
            ->firstOrFail();

        // Check if BA is auto-approved
        $this->autoApproved = $this->beritaAcara->isAutoApproved();
    }

    public function submit()
    {
        // Don't allow submission if manually closed by admin
        if (!$this->beritaAcara->attendance_is_open) {
            return;
        }

        $this->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'instansi' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_hp' => 'nullable|string|max:50',
            'tanda_tangan' => 'required',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'tanda_tangan.required' => 'Tanda tangan wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $signatureService = app(SignatureService::class);

        // Process signature
        $signaturePath = null;
        if ($this->tanda_tangan && str_starts_with($this->tanda_tangan, 'data:image')) {
            $signaturePath = $signatureService->store($this->tanda_tangan);
        }

        // Create new attendee record
        BeritaAcaraAttendee::create([
            'berita_acara_id' => $this->beritaAcara->id,
            'token' => (string) Str::uuid(), // Generate unique token
            'nama' => $this->nama,
            'jabatan' => $this->jabatan,
            'instansi' => $this->instansi,
            'email' => $this->email,
            'no_hp' => $this->no_hp,
            'tanda_tangan' => $signaturePath,
            'is_officer' => false,
            'is_signatory' => false, // Per user request, this defaults to false
            'confirmed_at' => now(), // Marked as attended
        ]);

        $this->successfullySubmitted = true;
        
        $this->dispatch('signed');
    }

    public function render()
    {
        return view('livewire.public-attendance', [
            'client' => $this->beritaAcara->client,
        ]);
    }
}
