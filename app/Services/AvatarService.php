<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class AvatarService
{
    private ImageManager $manager;

    public function __construct(?ImageManager $manager = null)
    {
        $this->manager = $manager ?? new ImageManager(new Driver);
    }

    /**
     * Resize to ~400x400, convert to WebP and store. Returns relative path on public disk.
     */
    public function store(UploadedFile $file, string $directory = 'avatars'): string
    {
        $image = $this->manager->decode($file->getRealPath() ?: $file->getPathname());

        $image->cover(400, 400);

        $encoded = $image->encode(new WebpEncoder(quality: 82));

        $filename = uniqid('avatar_', true).'.webp';
        $path = trim($directory, '/').'/'.$filename;

        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (str_starts_with($path, 'http')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
