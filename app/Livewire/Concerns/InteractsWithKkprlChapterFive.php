<?php

namespace App\Livewire\Concerns;

use App\Domain\Kkprl\ProposalFieldCatalog;
use App\Models\KkprlProposal;
use Livewire\Attributes\Locked;

trait InteractsWithKkprlChapterFive
{
    public string $existingPermitsChoice = '';

    #[Locked]
    public string $savedExistingPermitsChoice = '';

    public function updatedExistingPermitsChoice(): void
    {
        $this->proposal(editable: true);
        abort_unless($this->chapter === 'bag-5', 403);
        $this->validate($this->previewRules());
        session()->forget('success');
        $this->saveChatHistory();
    }

    protected function messages(): array
    {
        return ['existingPermitsChoice.required' => 'Pilih apakah Anda sudah memiliki izin pendukung.',
            'existingPermitsChoice.in' => 'Pilih sudah atau belum memiliki izin pendukung.'];
    }

    private function permitChoice(array $payload): string
    {
        return match (true) {
            in_array($payload['has_existing_permits'] ?? null, [true, 1, '1', 'true'], true) => '1',
            in_array($payload['has_existing_permits'] ?? null, [false, 0, '0', 'false'], true) => '0',
            default => '',
        };
    }

    private function initializeChapterFiveChoice(KkprlProposal $proposal): void
    {
        if ($this->chapter === 'bag-5') {
            $this->existingPermitsChoice = $this->savedExistingPermitsChoice = $this->permitChoice($proposal->payload ?? []);
        }
    }

    private function restoreChapterFiveChoice(array $message): void
    {
        $choice = $message['conditions']['has_existing_permits'] ?? null;
        if ($this->chapter === 'bag-5' && in_array($choice, ['0', '1'], true)
            && ($message['conditions']['saved_has_existing_permits'] ?? null) === $this->savedExistingPermitsChoice) {
            $this->existingPermitsChoice = $choice;
        }
    }

    private function previewContext(KkprlProposal $proposal): array
    {
        $payload = $proposal->payload ?? [];
        if ($this->chapter === 'bag-5' && in_array($this->existingPermitsChoice, ['0', '1'], true)) {
            $payload['has_existing_permits'] = $this->existingPermitsChoice === '1';
        }

        return $payload;
    }

    // Retain hidden permit corrections while the user reviews the availability choice.
    private function editablePreviewFields(KkprlProposal $proposal): array
    {
        if ($this->chapter !== 'bag-5') {
            return ProposalFieldCatalog::fieldsForPayload($this->chapter, $proposal->payload ?? []);
        }

        return array_merge(($proposal->payload['land_relation'] ?? null) === 'adjacent'
            ? ProposalFieldCatalog::fields('bag-5-land') : [], ProposalFieldCatalog::fields('bag-5-permits'));
    }

    private function applyChapterFiveChoice(array $payload): array
    {
        if ($this->chapter === 'bag-5' && $this->existingPermitsChoice !== $this->savedExistingPermitsChoice) {
            $payload['has_existing_permits'] = $this->existingPermitsChoice === '1';
        }

        return $payload;
    }

    private function chapterFivePrompt(): string
    {
        if ($this->chapter !== 'bag-5') {
            return '';
        }

        return ' Bab 5 mencatat informasi yang benar-benar dimiliki, bukan memutuskan kelayakan perizinan. '
            .'Jenis izin, tahun perolehan dan status izin dilengkapi jika izin ada. Nomor izin, instansi penerbit, masa berlaku, dokumen dan catatan diisi jika tersedia; jangan memaksa atau mengarangnya. '
            .'Untuk lahan yang berhimpitan, lengkapi status lahan, jenis bukti dan tahun perolehan; dokumen dan catatan bersifat opsional. '
            .($this->existingPermitsChoice === '0'
                ? 'Pengguna memilih belum memiliki izin pendukung. Jangan meminta nomor izin, penerbit, tahun atau dokumen izin; jangan mengisi detail izin lama. Lanjutkan hanya bukti lahan jika aktif, lalu arahkan konfirmasi draft. '
                : 'Jika pengguna menyatakan belum memiliki izin, arahkan memilih Belum memiliki izin pendukung di bagian atas. Jangan mengisi nomor, tahun atau status seolah izin telah terbit. ')
            .'Jangan menyimpulkan bahwa izin lain tidak diperlukan atau pengguna sudah memenuhi kewajiban perizinan. ';
    }
}
