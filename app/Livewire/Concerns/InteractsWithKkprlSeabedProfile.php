<?php

namespace App\Livewire\Concerns;

use App\Domain\Kkprl\AttachmentQuotaViolation;
use App\Domain\Kkprl\InvalidAttachmentFile;
use App\Domain\Kkprl\InvalidSeabedProfile;
use App\Domain\Kkprl\ProposalEvidenceCatalog;
use App\Models\KkprlProposal;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlSeabedProfileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait InteractsWithKkprlSeabedProfile
{
    public string $profileStartLat = '';

    public string $profileStartLng = '';

    public string $profileEndLat = '';

    public string $profileEndLng = '';

    public function generateSeabedProfile(KkprlSeabedProfileService $profiles, KkprlProposalAttachmentService $attachments): void
    {
        $proposal = $this->proposal(editable: true);
        abort_unless($this->chapter === 'bag-3' && $this->evidenceField === 'seabed_profile', 403);
        $this->validate($this->previewRules() + [
            'profileStartLat' => ['required', 'numeric', 'between:-85,85'],
            'profileStartLng' => ['required', 'numeric', 'between:-180,180'],
            'profileEndLat' => ['required', 'numeric', 'between:-85,85'],
            'profileEndLng' => ['required', 'numeric', 'between:-180,180'],
        ], ['profileStartLat.required' => 'Pilih titik A pada peta atau isi lintangnya.',
            'profileStartLng.required' => 'Isi bujur titik A.', 'profileEndLat.required' => 'Pilih titik B pada peta atau isi lintangnya.',
            'profileEndLng.required' => 'Isi bujur titik B.',
            'profileStartLat.numeric' => 'Lintang A harus berupa angka desimal.',
            'profileStartLng.numeric' => 'Bujur A harus berupa angka desimal.',
            'profileEndLat.numeric' => 'Lintang B harus berupa angka desimal.',
            'profileEndLng.numeric' => 'Bujur B harus berupa angka desimal.',
            'profileStartLat.between' => 'Lintang A harus antara -85 dan 85 derajat.',
            'profileStartLng.between' => 'Bujur A harus antara -180 dan 180 derajat.',
            'profileEndLat.between' => 'Lintang B harus antara -85 dan 85 derajat.',
            'profileEndLng.between' => 'Bujur B harus antara -180 dan 180 derajat.']);
        $this->errorMessage = null;
        $this->evidenceMessage = null;
        $this->resetValidation('profileTransect');
        $coordinates = $this->evidenceCoordinateIdentity($proposal);
        $checkpoint = $this->evidenceChatCheckpoint();
        $temporaryPath = null;
        $stored = null;
        try {
            $profile = $profiles->fetch((float) $this->profileStartLat, (float) $this->profileStartLng,
                (float) $this->profileEndLat, (float) $this->profileEndLng);
            $png = $profiles->png($profile);
            // Use the same inspected, private PNG attachment path as uploaded Chapter 3 evidence.
            $temporaryPath = tempnam(sys_get_temp_dir(), 'kkprl-profile-');
            if ($temporaryPath === false || file_put_contents($temporaryPath, $png) === false) {
                throw new InvalidSeabedProfile('Grafik belum dapat disiapkan. Coba lagi; narasi sebelumnya tetap tersedia.');
            }
            DB::transaction(function () use ($proposal, $coordinates, $checkpoint, $profile, $temporaryPath, $profiles, $attachments, &$stored): void {
                $current = KkprlProposal::query()->lockForUpdate()->findOrFail($proposal->id);
                $this->proposal(editable: true);
                if ($this->evidenceCoordinateIdentity($current) !== $coordinates) {
                    $this->errorMessage = 'Koordinat Bab 1 berubah saat mengambil profil. Periksa lokasi lalu buat penampang kembali.';

                    return;
                }
                // Lock an existing checkpoint before comparing it with concurrent manual corrections.
                $current->chats()->where('chapter', 'bag-3')->lockForUpdate()->first();
                if ($this->evidenceChatCheckpoint() !== $checkpoint) {
                    $this->loadChatHistory();
                    $this->errorMessage = 'Pratinjau berubah saat mengambil profil. Koreksi terbaru tetap tersedia; tinjau lalu ulangi bila perlu.';

                    return;
                }
                $source = ProposalEvidenceCatalog::sources()['gmrt'];
                $metadata = ['source' => 'gmrt', 'label' => $source['label'], 'url' => $source['url'],
                    'period' => '', 'accessed_at' => now()->toDateString(), 'attribution' => $source['credit'],
                    'notes' => 'Grafik penampang diolah aplikasi dari GMRT ProfileServer; bukan hasil survei.',
                    'coordinates' => $this->evidenceCoordinates($current), 'profile_coordinates_raw' => $coordinates[1],
                    'license' => 'CC BY 4.0', 'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
                    'data_doi' => 'https://doi.org/10.1594/IEDA.100001', 'profile' => $profile];
                $caption = 'Penampang sekunder A–B — '.$this->evidenceCitation($metadata)
                    .'; diolah aplikasi; CC BY 4.0 ('.$metadata['license_url'].'); data: '.$metadata['data_doi']
                    .'. '.$profiles->report($profile);
                $stored = $attachments->store($current, 'bag-3', new UploadedFile($temporaryPath, 'penampang-gmrt.png', 'image/png', null, true), [
                    'field' => 'seabed_profile', 'attachment_role' => 'ai_evidence', 'placement' => 'inline',
                    'anchor_key' => 'seabed_profile', 'caption' => $caption, 'source_metadata' => $metadata,
                ]);
                $this->previewData['seabed_profile'] = $profiles->report($profile)."\n\n".$this->evidenceCitation($metadata)
                    .'; grafik diolah aplikasi; CC BY 4.0 ('.$metadata['license_url'].'); data: '.$metadata['data_doi'];
                $this->messages[] = ['role' => 'user', 'content' => 'Buat penampang dasar laut sepanjang lintasan A–B yang saya pilih.'];
                $this->messages[] = ['role' => 'ai', 'content' => 'Grafik dan narasi awal profil dasar laut sudah tersedia. Silakan periksa apakah lintasan A–B mewakili lokasi usaha Anda. Tinjau Pratinjau Draft, lalu konfirmasi jika sesuai. Anda dapat meminta saya mengulas grafik atau menjelaskan kondisi dasar yang Anda ketahui; jenis sedimen tidak dapat disimpulkan dari grafik kedalaman.'];
                $this->saveChatHistory();
                session()->forget('success');
                $this->evidenceMessage = 'Grafik tersimpan sebagai bukti privat dan narasi awal tersedia tanpa AI. Tinjau Pratinjau Draft sebelum menyimpan.';
            });
        } catch (InvalidSeabedProfile $exception) {
            throw ValidationException::withMessages(['profileTransect' => $exception->getMessage()]);
        } catch (AttachmentQuotaViolation $exception) {
            throw ValidationException::withMessages(['profileTransect' => 'Batas lampiran Bab 3 adalah 10 file dengan total 15 MB. Hapus lampiran yang tidak diperlukan sebelum membuat grafik.']);
        } catch (InvalidAttachmentFile $exception) {
            throw ValidationException::withMessages(['profileTransect' => 'Grafik belum dapat disimpan. Narasi sebelumnya tetap tersedia; coba lagi.']);
        } catch (\Throwable $exception) {
            if ($stored !== null) {
                Storage::disk($stored->storage_disk)->delete($stored->storage_path);
            }
            throw $exception;
        } finally {
            if (is_string($temporaryPath) && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
