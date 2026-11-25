<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Uploaded images on the public disk (ADR-010). Used by categories, brands and products.
 */
final class PublicImageStorage
{
    private const DISK = 'public';

    public function store(UploadedFile $image, string $directory): string
    {
        return $image->store($directory, self::DISK);
    }

    /**
     * The path to save after an edit: the new upload, null when removed, otherwise the current path.
     * Call deleteReplaced() after the database update succeeded.
     */
    public function resolveEditedImage(?string $currentPath, ?UploadedFile $newImage, bool $shouldRemove, string $directory): ?string
    {
        if ($newImage !== null) {
            return $this->store($newImage, $directory);
        }

        return $shouldRemove ? null : $currentPath;
    }

    public function deleteReplaced(?string $oldPath, ?string $newPath): void
    {
        if ($oldPath !== null && $oldPath !== $newPath) {
            $this->delete($oldPath);
        }
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public static function url(?string $path): ?string
    {
        return $path === null ? null : Storage::disk(self::DISK)->url($path);
    }
}
