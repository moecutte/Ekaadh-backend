<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PublicUpload
{
    /**
     * Store an uploaded file so it is reachable from the site root.
     *
     * On deployments where public/ ships read-only (or is wiped on redeploy)
     * the file goes to the public disk instead, which is exposed through the
     * public/storage symlink.
     *
     * @return string path relative to the site root, usable with asset()
     */
    public static function store(UploadedFile $file, string $directory, string $filename): string
    {
        $directory = trim($directory, '/');

        if (self::isWritableDirectory(public_path($directory))) {
            $file->move(public_path($directory), $filename);

            return $directory.'/'.$filename;
        }

        $stored = Storage::disk('public')->putFileAs($directory, $file, $filename);

        if ($stored === false) {
            throw new RuntimeException(
                'Unable to write uploads to public/'.$directory.' or storage/app/public/'.$directory.'. Check directory permissions and that "php artisan storage:link" has been run.'
            );
        }

        return 'storage/'.ltrim($stored, '/');
    }

    private static function isWritableDirectory(string $path): bool
    {
        if (! File::isDirectory($path)) {
            try {
                File::ensureDirectoryExists($path);
            } catch (Throwable) {
                return false;
            }
        }

        return File::isDirectory($path) && File::isWritable($path);
    }
}
