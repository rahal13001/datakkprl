<?php

namespace App\Livewire\Concerns;

use App\Domain\Kkprl\AttachmentQuotaViolation;
use App\Domain\Kkprl\InvalidAttachmentFile;
use App\Domain\Kkprl\InvalidTideData;
use App\Models\KkprlProposal;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlTideChartRenderer;
use App\Services\KkprlTideDataReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait InteractsWithKkprlTideCharts
{
    public function generateTideChart(int $attachmentId, KkprlTideChartRenderer $renderer, KkprlProposalAttachmentService $attachments): void
    {
        abort_unless($this->chapter === 'bag-3', 403);
        $this->resetValidation('tideChart');
        $checkpoint = $this->evidenceChatCheckpoint();
        $context = $this->prepareTideCalculation($attachmentId);
        if ($context === null || ! $this->tideCalculationCheckpointMatches($checkpoint)) {
            return;
        }
        [$data, $metadata, , $coordinates] = $context;
        $disk = Storage::disk($data->storage_disk);
        $fingerprint = hash('sha256', json_encode([KkprlTideChartRenderer::METHOD, $data->id, $data->checksum, $data->original_name, $metadata], JSON_THROW_ON_ERROR));
        $temporaryPath = null;
        $stored = null;
        try {
            $table = app(KkprlTideDataReader::class)->read($disk->path($data->storage_path), $metadata['tide_format'] ?? '');
            $result = $renderer->build($table, $metadata['tide_options'] ?? [], $metadata + [
                'file_name' => $data->original_name, 'file_checksum' => $data->checksum,
            ]);
            unset($table);
            $temporaryPath = tempnam(sys_get_temp_dir(), 'kkprl-tide-chart-');
            if ($temporaryPath === false || file_put_contents($temporaryPath, $result['png']) === false) {
                throw new InvalidTideData('Grafik belum dapat disiapkan. Data dan narasi sebelumnya tetap tersedia.');
            }
            unset($result['png']);
            DB::transaction(function () use ($data, $metadata, $coordinates, $checkpoint, $fingerprint, $result, $temporaryPath, $attachments, $disk, &$stored): void {
                $current = KkprlProposal::query()->lockForUpdate()->findOrFail($data->proposal_id);
                $this->proposal(editable: true);
                abort_if($current->isLocked(), 403);
                $fresh = $current->attachments()->whereKey($data->id)->lockForUpdate()->firstOrFail();
                if ($fresh->checksum !== $data->checksum || $fresh->source_metadata !== $metadata
                    || $fresh->storage_disk !== $data->storage_disk || $fresh->storage_path !== $data->storage_path
                    || $fresh->original_name !== $data->original_name || $fresh->mime_type !== $data->mime_type
                    || $this->evidenceCoordinates($current) !== $coordinates || $this->latestTideData($current)?->id !== $data->id
                    || ! $disk->exists($fresh->storage_path) || $disk->size($fresh->storage_path) > KkprlTideDataReader::MAX_BYTES
                    || ! hash_equals($fresh->checksum, hash('sha256', $disk->get($fresh->storage_path)))) {
                    $this->errorMessage = 'Data pasut berubah saat membuat grafik. Periksa sumber terbaru lalu buat grafik kembali.';

                    return;
                }
                $current->chats()->where('chapter', 'bag-3')->lockForUpdate()->first();
                if (! $this->tideCalculationCheckpointMatches($checkpoint)) {
                    return;
                }
                $existing = $current->attachments()->where('chapter', 'bag-3')->where('field', 'tide')
                    ->where('attachment_role', 'ai_evidence')->lockForUpdate()->get()
                    ->first(fn ($attachment) => ($attachment->source_metadata['tide_chart']['fingerprint'] ?? '') === $fingerprint);
                if ($existing !== null) {
                    if ($existing->storage_disk !== $data->storage_disk || $existing->mime_type !== 'image/png'
                        || ! $disk->exists($existing->storage_path) || $disk->size($existing->storage_path) !== $existing->size
                        || ! hash_equals($existing->checksum, hash('sha256', $disk->get($existing->storage_path)))) {
                        $this->errorMessage = 'Grafik sebelumnya tidak tersedia atau berubah. Hapus gambar grafik tersebut, lalu buat kembali dari data pasut.';

                        return;
                    }
                    $this->evidenceMessage = 'Grafik dari data ini sudah tersedia di daftar bukti. Anda dapat melihat atau mengunduhnya; narasi tetap tersedia.';

                    return;
                }
                $chart = $result['chart'] + ['source_attachment_id' => $fresh->id, 'source_checksum' => $fresh->checksum,
                    'source_file_name' => $fresh->original_name, 'fingerprint' => $fingerprint, 'created_at' => now()->toIso8601String()];
                $source = $metadata + ['tide_chart' => $chart];
                $summary = $chart['summary'];
                $caption = 'Grafik pasang surut — '.($summary['data_kind'] === 'prediction' ? 'prediksi, bukan pengukuran langsung; ' : 'jenis data belum diverifikasi; ')
                    .$summary['count'].' sampel; '.$summary['start'].' sampai '.$summary['end'].' ('.$summary['timezone'].'); tinggi dalam meter; datum: '.$summary['datum'].'. '
                    .'Garis penghubung visual tanpa pemulusan; jeda > '.$chart['gap_threshold_seconds'].' detik diputus ('.$chart['gap_count'].' jeda). '
                    .'Ekstrem hanya untuk periode file, bukan HAT/LAT. Diolah backend dari '.$fresh->original_name.'. '.$this->evidenceCitation($metadata);
                $stored = $attachments->store($current, 'bag-3', new UploadedFile($temporaryPath, 'grafik-pasang-surut.png', 'image/png', null, true), [
                    'field' => 'tide', 'attachment_role' => 'ai_evidence', 'placement' => 'inline', 'anchor_key' => 'tide',
                    'caption' => $caption, 'source_metadata' => $source,
                ]);
                $this->messages[] = ['role' => 'user', 'content' => 'Buat grafik pasang surut dari '.$fresh->original_name.'.'];
                $this->messages[] = ['role' => 'ai', 'content' => 'Grafik pasang surut sudah tersedia di daftar bukti dan dapat diunduh. Periksa periode, zona waktu, acuan tinggi dan sumbernya. Narasi yang Anda koreksi tetap tersedia. Grafik ikut dalam ekspor Bab 3 setelah draft bab lengkap.'];
                $this->saveChatHistory();
                $this->evidenceMessage = 'Grafik tersimpan sebagai bukti privat tanpa AI. Lihat atau unduh grafik di daftar bukti; narasi Anda tetap tersedia.';
            });
        } catch (InvalidTideData $exception) {
            throw ValidationException::withMessages(['tideChart' => $exception->getMessage()]);
        } catch (AttachmentQuotaViolation $exception) {
            throw ValidationException::withMessages(['tideChart' => 'Batas lampiran Bab 3 adalah 10 file dengan total 15 MB. Grafik memakai satu lampiran; hapus gambar yang tidak diperlukan lalu coba kembali.']);
        } catch (InvalidAttachmentFile $exception) {
            throw ValidationException::withMessages(['tideChart' => 'Grafik belum dapat disimpan. Data dan narasi sebelumnya tetap tersedia; coba lagi.']);
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
