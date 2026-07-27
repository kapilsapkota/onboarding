<?php


namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageService
{
    /** Target ceiling in bytes (50 KB). */
    const MAX_BYTES = 50 * 1024;

    /** Accepted MIME types. */
    const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];

    /** Storage disk. */
    const DISK = 'public';

    /** Storage directory. */
    const DIR = 'products/logos';

    // -------------------------------------------------------------------------

    /**
     * Process an uploaded logo, compress it to ≤50 KB, store it, and
     * return the public storage path (e.g. "products/logos/abc123.webp").
     *
     * @throws RuntimeException if the image cannot be compressed to ≤50 KB.
     */
    public function store(UploadedFile $file, string $directory = self::DIR): string
    {
        $mime = $file->getMimeType();

        if (!in_array($mime, self::ALLOWED_MIMES)) {
            throw new RuntimeException('Unsupported image type: ' . $mime);
        }

        if ($mime === 'image/svg+xml') {
            return $this->storeSvg($file, $directory);
        }

        return $this->storeRaster($file, $directory);
    }

    /**
     * Delete a previously stored logo by its storage path.
     */
    public function delete(string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    // -------------------------------------------------------------------------

    private function storeSvg(UploadedFile $file, $directory = self::DIR): string
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException(
                'SVG logo is ' . $this->humanSize($file->getSize()) . '. '
                . 'SVGs cannot be auto-compressed — please simplify the file to under 50 KB.'
            );
        }

        $filename = $this->uniqueFilename('svg');
        Storage::disk(self::DISK)->putFileAs($directory, $file, $filename);

        return $directory . '/' . $filename;
    }

    private function storeRaster(UploadedFile $file, $directory = self::DIR): string
    {
        if (!function_exists('imagecreatefromstring')) {
            throw new RuntimeException('GD extension is not available.');
        }

        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if (!$source) {
            throw new RuntimeException('Could not read image — the file may be corrupt.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $hasAlpha = $this->hasAlpha($file->getMimeType(), $source);

        // Scale pass: try at 100%, 75%, 60%, 50%, 40%, 30%, 20% of original dimensions.
        foreach ([1.0, 0.75, 0.60, 0.50, 0.40, 0.30, 0.20] as $scale) {
            $w = (int)round($width * $scale);
            $h = (int)round($height * $scale);

            $canvas = $this->createCanvas($w, $h, $hasAlpha);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $w, $h, $width, $height);

            // Quality pass within this scale.
            $result = $this->compressToTarget($canvas, $hasAlpha);

            imagedestroy($canvas);

            if ($result !== null) {
                imagedestroy($source);

                $ext = $result['ext'];
                $filename = $this->uniqueFilename($ext);
                $path = $directory . '/' . $filename;

                Storage::disk(self::DISK)->put($path, $result['data']);

                return $path;
            }
        }

        imagedestroy($source);

        throw new RuntimeException(
            'Could not compress the logo to under 50 KB even at minimum dimensions. '
            . 'Please provide a simpler image (fewer colours, smaller canvas).'
        );
    }

    /**
     * Try to compress a GD image to ≤ MAX_BYTES.
     * Tries WebP first (best ratio + transparency), then falls back
     * to PNG (for alpha) or JPEG (for opaque).
     *
     * Returns ['data' => string, 'ext' => string] or null if impossible.
     */
    private function compressToTarget(\GdImage $image, bool $hasAlpha): ?array
    {
        // --- WebP (supports transparency, excellent ratio) ---
        if (function_exists('imagewebp')) {
            foreach ([85, 75, 65, 55, 45, 35, 25] as $quality) {
                ob_start();
                imagewebp($image, null, $quality);
                $data = ob_get_clean();

                if (strlen($data) <= self::MAX_BYTES) {
                    return ['data' => $data, 'ext' => 'webp'];
                }
            }
        }

        if ($hasAlpha) {
            // --- PNG (lossless, but compression level 0–9) ---
            // PNG compression is lossless, so we can only try levels.
            // Higher number = more compressed (slower, not lossy).
            foreach ([9, 8, 7] as $level) {
                ob_start();
                imagepng($image, null, $level);
                $data = ob_get_clean();

                if (strlen($data) <= self::MAX_BYTES) {
                    return ['data' => $data, 'ext' => 'png'];
                }
            }
        } else {
            // --- JPEG (lossy, good for photos/opaque logos) ---
            foreach ([85, 75, 65, 55, 45, 35, 25] as $quality) {
                ob_start();
                imagejpeg($image, null, $quality);
                $data = ob_get_clean();

                if (strlen($data) <= self::MAX_BYTES) {
                    return ['data' => $data, 'ext' => 'jpg'];
                }
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------

    private function createCanvas(int $w, int $h, bool $alpha): \GdImage
    {
        $canvas = imagecreatetruecolor($w, $h);

        if ($alpha) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $w, $h, $transparent);
            imagealphablending($canvas, true);
        }

        return $canvas;
    }

    private function hasAlpha(string $mime, \GdImage $image): bool
    {
        if (in_array($mime, ['image/png', 'image/webp', 'image/gif'])) {
            // Check if any pixel is actually transparent.
            $w = imagesx($image);
            $h = imagesy($image);

            // Sample a grid of pixels rather than every single one.
            $step = max(1, (int)floor(min($w, $h) / 20));

            for ($x = 0; $x < $w; $x += $step) {
                for ($y = 0; $y < $h; $y += $step) {
                    $rgba = imagecolorat($image, $x, $y);
                    $alpha = ($rgba >> 24) & 0x7F;
                    if ($alpha > 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function uniqueFilename(string $ext): string
    {
        return Str::uuid() . '.' . $ext;
    }

    private function humanSize(int $bytes): string
    {
        return round($bytes / 1024, 1) . ' KB';
    }
}
