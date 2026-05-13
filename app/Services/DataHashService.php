<?php

namespace App\Services;

class DataHashService
{
    public function email(?string $value): ?string
    {
        return $this->hash($this->blankToNull($value ? mb_strtolower(trim($value)) : null));
    }

    public function phone(?string $value): ?string
    {
        $normalized = $value ? preg_replace('/\D+/', '', trim($value)) : null;

        return $this->hash($this->blankToNull($normalized));
    }

    public function token(?string $value): ?string
    {
        return $this->hash($this->blankToNull($value ? trim($value) : null));
    }

    protected function hash(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $key = (string) config('data-protection.hash_key');

        if ($key === '') {
            throw new \RuntimeException('DATA_HASH_KEY is not configured.');
        }

        return hash_hmac('sha256', $value, $key);
    }

    protected function blankToNull(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value;
    }
}
