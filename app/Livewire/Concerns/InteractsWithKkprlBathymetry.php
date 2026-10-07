<?php

namespace App\Livewire\Concerns;

use App\Domain\Kkprl\AttachmentQuotaViolation;
use App\Domain\Kkprl\InvalidAttachmentFile;
use App\Domain\Kkprl\InvalidBathymetry;
use App\Domain\Kkprl\ProposalEvidenceCatalog;
use App\Models\KkprlProposal;
use App\Services\KkprlBathymetryMapRenderer;
use App\Services\KkprlBathymetryService;
use App\Services\KkprlProposalAttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait InteractsWithKkprlBathymetry
{
    public string $bathymetryBufferKm = '1';

    public function generateBathymetry(KkprlBathymetryService $bathymetry, KkprlBathymetryMapRenderer $renderer, KkprlProposalAttachmentService $attachments): void
    {
        $proposal = $this->proposal(editable: true);
        abort_unless($this->chapter === 'bag-3' && $this->evidenceField === 'bathymetry', 403);
        $this->validate($this->previewRules() + ['bathymetryBufferKm' => ['required', Rule::in(['0.5', '1', '2', '5'])]],
            ['bathymetryBufferKm.in' => 'Pilih jarak tambahan 0,5, 1, 2 atau 5 km.']);
        $this->errorMessage = null;
        $this->evidenceMessage = null;
        $this->resetValidation('bathymetryArea');
        $identity = $this->bathymetryCoordinateIdentity($proposal);
        $points = $this->evidenceReferencePoints($proposal, complete: true);
        $checkpoint = $this->evidenceChatCheckpoint();
        $temporaryPath = null;
        $stored = null;
        try {
            if (! in_array($identity[2], ['point', 'line', 'polygon'], true)) {
                throw new InvalidBathymetry('Periksa bentuk lokasi pada Bab 1 sebelum membuat peta.');
            }
            $result = $bathymetry->fetch($points, (float) $this->bathymetryBufferKm);
            $png = $renderer->png($result, $points, $identity[2]);
            $temporaryPath = tempnam(sys_get_temp_dir(), 'kkprl-bathymetry-');
            if ($temporaryPath === false || file_put_contents($temporaryPath, $png) === false) {
                throw new InvalidBathymetry('Peta belum dapat disiapkan. Coba lagi; narasi sebelumnya tetap tersedia.');
            }
            DB::transaction(function () use ($proposal, $identity, $checkpoint, $result, $temporaryPath, $bathymetry, $attachments, &$stored): void {
                $current = KkprlProposal::query()->lockForUpdate()->findOrFail($proposal->id);
                $this->proposal(editable: true);
                if ($this->bathymetryCoordinateIdentity($current) !== $identity) {
                    $this->errorMessage = 'Koordinat atau bentuk lokasi Bab 1 berubah saat mengambil peta. Periksa lokasi lalu buat peta kembali.';

                    return;
                }
                $current->chats()->where('chapter', 'bag-3')->lockForUpdate()->first();
                if ($this->evidenceChatCheckpoint() !== $checkpoint) {
                    $this->loadChatHistory();
                    $this->errorMessage = 'Pratinjau berubah saat mengambil peta. Koreksi terbaru tetap tersedia; tinjau lalu ulangi bila perlu.';

                    return;
                }
                $source = ProposalEvidenceCatalog::sources()['gmrt'];
                $metadata = ['source' => 'gmrt', 'label' => $source['label'], 'url' => $source['url'], 'period' => '',
                    'accessed_at' => now()->toDateString(), 'attribution' => $source['credit'],
                    'notes' => 'Peta dan kontur diolah aplikasi dari GMRT GridServer; bukan hasil survei.',
                    'coordinates' => $this->evidenceCoordinates($current), 'bathymetry_coordinates_raw' => $identity[1], 'bathymetry_shape_type' => $identity[2],
                    'license' => 'CC BY 4.0', 'license_url' => 'https://creativecommons.org/licenses/by/4.0/', 'data_doi' => 'https://doi.org/10.1594/IEDA.100001',
                    'bathymetry' => $result['summary'], 'bathymetry_grid_gzip' => $result['archive']];
                $citation = $this->evidenceCitation($metadata).'; peta diolah aplikasi; CC BY 4.0 ('.$metadata['license_url'].'); data: '.$metadata['data_doi'];
                $report = $bathymetry->report($result['summary']);
                $stored = $attachments->store($current, 'bag-3', new UploadedFile($temporaryPath, 'batimetri-gmrt.png', 'image/png', null, true), [
                    'field' => 'bathymetry', 'attachment_role' => 'ai_evidence', 'placement' => 'inline', 'anchor_key' => 'bathymetry',
                    'caption' => 'Peta batimetri sekunder — '.$citation.'. '.$report, 'source_metadata' => $metadata,
                ]);
                $this->previewData['bathymetry'] = $report."\n\n".$citation;
                $this->messages[] = ['role' => 'user', 'content' => 'Buat peta batimetri dari koordinat lokasi Bab 1.'];
                $this->messages[] = ['role' => 'ai', 'content' => 'Peta dan narasi awal batimetri sudah tersedia. Silakan periksa penanda lokasi dan tinjau Pratinjau Draft; konfirmasi jika sesuai. Anda dapat meminta saya mengulas peta. Untuk melanjutkan penampang dasar laut, pilih Profil dasar laut lalu tentukan lintasan A–B.'];
                $this->saveChatHistory();
                session()->forget('success');
                $this->evidenceMessage = 'Peta tersimpan sebagai bukti privat dan narasi awal tersedia tanpa AI. Tinjau Pratinjau Draft sebelum menyimpan.';
            });
        } catch (InvalidBathymetry $exception) {
            throw ValidationException::withMessages(['bathymetryArea' => $exception->getMessage()]);
        } catch (AttachmentQuotaViolation $exception) {
            throw ValidationException::withMessages(['bathymetryArea' => 'Batas lampiran Bab 3 adalah 10 file dengan total 15 MB. Hapus lampiran yang tidak diperlukan sebelum membuat peta.']);
        } catch (InvalidAttachmentFile $exception) {
            throw ValidationException::withMessages(['bathymetryArea' => 'Peta belum dapat disimpan. Narasi sebelumnya tetap tersedia; coba lagi.']);
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

    private function bathymetryCoordinateIdentity(KkprlProposal $proposal): array
    {
        $bag = $proposal->payload['bag-1'] ?? $proposal->payload ?? [];

        return [...$this->evidenceCoordinateIdentity($proposal), $bag['shape_type'] ?? 'point'];
    }
}
