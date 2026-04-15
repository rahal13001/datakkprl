<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles secure storage and retrieval of signature images.
 * Signatures are stored encrypted on the local (private) disk.
 */
class SignatureService
{
    protected string $disk = 'local';
    protected string $directory = 'signatures';

    /**
     * Store a base64-encoded signature image securely.
     *
     * @param string $base64Data The base64 data URI (e.g., "data:image/png;base64,iVBOR...")
     * @return string The relative file path on the private disk
     */
    public function store(string $base64Data): string
    {
        // Strip the data URI prefix
        $imageData = $this->decodeBase64($base64Data);

        // Encrypt the raw image data
        $encrypted = Crypt::encryptString(base64_encode($imageData));

        // Generate unique filename
        $filename = $this->directory . '/' . Str::uuid() . '.sig';

        // Store encrypted content on private disk
        Storage::disk($this->disk)->put($filename, $encrypted);

        return $filename;
    }

    /**
     * Retrieve and decrypt a signature as raw PNG image data.
     *
     * @param string $path The file path returned by store()
     * @return string|null Raw PNG binary data, or null if not found
     */
    public function retrieve(string $path): ?string
    {
        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        $encrypted = Storage::disk($this->disk)->get($path);

        try {
            $base64 = Crypt::decryptString($encrypted);
            return base64_decode($base64);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            report($e);
            return null;
        }
    }

    /**
     * Retrieve a signature as a base64 data URI (for embedding in HTML/PDF).
     *
     * @param string $path The file path returned by store()
     * @return string|null Data URI string, or null if not found
     */
    public function retrieveAsDataUri(string $path): ?string
    {
        $data = $this->retrieve($path);
        if (!$data) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($data);
    }

    /**
     * Delete a stored signature.
     *
     * @param string $path The file path to delete
     * @return bool
     */
    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Decode a base64 data URI into raw binary data.
     */
    protected function decodeBase64(string $dataUri): string
    {
        // Remove data URI prefix if present
        if (str_contains($dataUri, ',')) {
            $dataUri = explode(',', $dataUri, 2)[1];
        }

        return base64_decode($dataUri);
    }
}
