<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

class ImageCompressionService
{
    /**
     * Compress an uploaded image and store it to the public storage disk.
     *
     * @param  UploadedFile  $file
     * @param  string  $directory
     * @param  int  $maxWidth
     * @param  int  $maxHeight
     * @param  int  $quality
     * @return string Stored file path relative to public disk (e.g., 'complaints/uuid.jpg')
     */
    public function compressAndStore(
        UploadedFile $file,
        string $directory = 'complaints',
        int $maxWidth = 1280,
        int $maxHeight = 1280,
        int $quality = 75
    ): string {
        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'jpg'));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }

        // Normalize jpeg to jpg for storage consistency
        $targetExt = in_array($ext, ['png', 'webp'], true) ? $ext : 'jpg';
        $filename = Str::uuid() . '.' . $targetExt;
        $targetPath = trim($directory, '/') . '/' . $filename;

        // 1. Try compression using Intervention Image + GD
        try {
            $manager = Image::getFacadeRoot();
            $image = $manager->decode($file);

            // Auto-orient based on camera EXIF data (fixes rotated smartphone photos)
            try {
                $image->orient();
            } catch (Throwable) {
                // Ignore if EXIF orientation metadata is not available
            }

            // Proportionally scale down if either dimension exceeds maximums
            $image->scaleDown(width: $maxWidth, height: $maxHeight);

            // Encode with target extension & compression quality
            $encoded = $image->encodeUsingFileExtension($targetExt, quality: $quality);

            $saved = Storage::disk('public')->put($targetPath, (string) $encoded);
            if ($saved) {
                return $targetPath;
            }
        } catch (Throwable) {
            // Fallback safely to native storage below if Intervention Image fails
        }

        // 2. Safe Fallback: store original file directly if compression fails
        $fallbackPath = $file->storeAs(trim($directory, '/'), $filename, 'public');
        if ($fallbackPath) {
            return $fallbackPath;
        }

        throw new \RuntimeException('Gagal menyimpan file foto bukti ke penyimpanan publik.');
    }
}

