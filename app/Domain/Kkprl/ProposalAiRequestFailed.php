<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class ProposalAiRequestFailed extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        public readonly ?int $httpStatus = null,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($this->userMessage());
    }

    public function userMessage(): string
    {
        $message = match ($this->reason) {
            'daily_quota' => 'Kuota harian layanan AI sudah habis.',
            'rate_limit' => 'Layanan AI sedang mencapai batas penggunaan sementara.',
            'quota_unavailable' => 'Kuota model AI belum tersedia pada proyek API. Pengelola perlu memeriksa pengaturan kuota Gemini.',
            'configuration', 'access' => 'Layanan AI belum siap digunakan. Pengelola perlu memeriksa konfigurasi akses Gemini.',
            'model' => 'Model AI yang digunakan belum tersedia. Hubungi pengelola layanan.',
            'busy' => 'Layanan AI sedang sibuk. Coba lagi beberapa saat.',
            'connection' => 'Koneksi ke layanan AI belum berhasil. Coba lagi beberapa saat.',
            'empty_response' => 'AI belum memberikan narasi. Coba gambar yang lebih jelas atau lengkapi informasinya.',
            default => 'Permintaan AI belum dapat diproses. Periksa isian lalu coba lagi.',
        };
        if (in_array($this->reason, ['daily_quota', 'rate_limit'], true) && $this->retryAfterSeconds !== null) {
            $message .= ' Coba kembali dalam sekitar '.$this->waitDescription().'.';
        }

        return $message;
    }

    private function waitDescription(): string
    {
        $seconds = max(1, $this->retryAfterSeconds ?? 1);
        if ($seconds < 60) {
            return $seconds.' detik';
        }
        $minutes = (int) ceil($seconds / 60);
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return $hours === 0 ? $minutes.' menit' : $hours.' jam'.($remaining > 0 ? ' '.$remaining.' menit' : '');
    }
}
