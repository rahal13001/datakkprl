<?php

namespace App\Livewire;

use App\Models\BeritaAcaraAttendee;
use App\Services\SignatureService;
use Livewire\Component;
use Carbon\Carbon;

class AttendeeSign extends Component
{
    public BeritaAcaraAttendee $attendee;

    // Form fields
    public string $nama = '';
    public string $jabatan = '';
    public string $instansi = '';
    public string $email = '';
    public string $no_hp = '';
    public ?string $tanda_tangan = null;

    // State
    public bool $alreadySigned = false;
    public bool $autoApproved = false;
    public ?string $existingSignatureDataUri = null;

    public function mount(string $token)
    {
        $this->attendee = BeritaAcaraAttendee::where('token', $token)
            ->with('beritaAcara.client.service')
            ->firstOrFail();

        // Pre-fill form with existing data
        $this->nama = $this->attendee->nama ?? '';
        $this->jabatan = $this->attendee->jabatan ?? '';
        $this->instansi = $this->attendee->instansi ?? '';
        $this->email = $this->attendee->email ?? '';
        $this->no_hp = $this->attendee->no_hp ?? '';

        // Check if already signed
        if ($this->attendee->hasConfirmed()) {
            $this->alreadySigned = true;

            // Load existing signature for display
            if ($this->attendee->tanda_tangan) {
                $signatureService = app(SignatureService::class);
                $this->existingSignatureDataUri = $signatureService->retrieveAsDataUri($this->attendee->tanda_tangan);
            }
        }

        // Check if BA is auto-approved
        $this->autoApproved = $this->attendee->beritaAcara->isAutoApproved();
    }

    public function submit()
    {
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

        // Update attendee record
        $this->attendee->update([
            'nama' => $this->nama,
            'jabatan' => $this->jabatan,
            'instansi' => $this->instansi,
            'email' => $this->email,
            'no_hp' => $this->no_hp,
            'tanda_tangan' => $signaturePath ?? $this->attendee->tanda_tangan,
            'confirmed_at' => Carbon::now(),
        ]);

        $this->alreadySigned = true;
        $this->existingSignatureDataUri = $signaturePath
            ? $signatureService->retrieveAsDataUri($signaturePath)
            : null;

        $this->dispatch('signed');
    }

    public function render()
    {
        $beritaAcara = $this->attendee->beritaAcara;
        $client = $beritaAcara->client;

        return view('livewire.attendee-sign', [
            'beritaAcara' => $beritaAcara,
            'client' => $client,
        ]);
    }
}
