<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Validates image uploads via magic bytes (ADR 040).
 */
final class ImageFileValidator
{
    /** @var array<string, list<list<int>>> */
    private const SIGNATURES = [
        'image/jpeg' => [[0xFF, 0xD8, 0xFF]],
        'image/png' => [[0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A]],
        'image/gif' => [[0x47, 0x49, 0x46, 0x38, 0x37, 0x61], [0x47, 0x49, 0x46, 0x38, 0x39, 0x61]],
        'image/webp' => [[0x52, 0x49, 0x46, 0x46]],
    ];

    /**
     * @return list<string>
     */
    public static function allowedMimes(): array
    {
        /** @var list<string> $mimes */
        $mimes = config('agent.attachment.allowed_mimes', []);

        return $mimes;
    }

    public static function maxSizeBytes(): int
    {
        return (int) config('agent.attachment.max_size_bytes', 5 * 1024 * 1024);
    }

    public function validate(UploadedFile $file): string
    {
        $size = $file->getSize();

        if ($size === false || $size <= 0) {
            throw new \InvalidArgumentException('Attachment file is empty.');
        }

        if ($size > self::maxSizeBytes()) {
            throw new \InvalidArgumentException('Attachment exceeds the maximum file size.');
        }

        $detectedMime = $this->detectMime($file);

        if ($detectedMime === null) {
            throw new \InvalidArgumentException('Attachment type is not allowed.');
        }

        if (! in_array($detectedMime, self::allowedMimes(), true)) {
            throw new \InvalidArgumentException('Attachment type is not allowed.');
        }

        return $detectedMime;
    }

    public function detectMime(UploadedFile $file): ?string
    {
        $header = $this->readFileHeader($file);

        if ($header === null) {
            return null;
        }

        return $this->matchMimeFromHeader($header);
    }

    private function readFileHeader(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return null;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        $header = fread($handle, 32);
        fclose($handle);

        if ($header === false || $header === '') {
            return null;
        }

        return $header;
    }

    private function matchMimeFromHeader(string $header): ?string
    {
        $bytes = array_values(unpack('C*', $header) ?: []);

        foreach (self::SIGNATURES as $mime => $signatures) {
            foreach ($signatures as $signature) {
                if (! $this->matchesSignature($bytes, $signature)) {
                    continue;
                }

                if ($mime === 'image/webp' && ! $this->isWebp($header)) {
                    continue;
                }

                return $mime;
            }
        }

        return null;
    }

    /**
     * @param  list<int>  $bytes
     * @param  list<int>  $signature
     */
    private function matchesSignature(array $bytes, array $signature): bool
    {
        if (count($bytes) < count($signature)) {
            return false;
        }

        foreach ($signature as $index => $byte) {
            if (($bytes[$index] ?? null) !== $byte) {
                return false;
            }
        }

        return true;
    }

    private function isWebp(string $header): bool
    {
        return strlen($header) >= 12 && substr($header, 8, 4) === 'WEBP';
    }
}
