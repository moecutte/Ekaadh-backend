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
     * Production (Coolify): always write to storage/app/public (persistent volume)
     * via the public/storage symlink. public/ is often read-only or wiped on deploy.
     *
     * @return string path relative to the site root, usable with asset()
     */
    public static function store(UploadedFile $file, string $directory, string $filename): string
    {
        $directory = trim($directory, '/');

        if (! app()->environment('production') && self::isWritableDirectory(public_path($directory))) {
            $file->move(public_path($directory), $filename);

            return $directory.'/'.$filename;
        }

        $diskRoot = storage_path('app/public/'.$directory);
        self::ensureWritableDirectory($diskRoot);

        $stored = Storage::disk('public')->putFileAs($directory, $file, $filename);

        if ($stored === false) {
            throw self::writeFailure($diskRoot);
        }

        return 'storage/'.ltrim($stored, '/');
    }

    private static function isWritableDirectory(string $path): bool
    {
        if (! File::isDirectory($path)) {
            try {
                File::ensureDirectoryExists($path, 0775);
            } catch (Throwable) {
                return false;
            }
        }

        return File::isDirectory($path) && File::isWritable($path);
    }

    private static function ensureWritableDirectory(string $path): void
    {
        try {
            File::ensureDirectoryExists($path, 0775);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Cannot create '.$path.'. '.$e->getMessage().' '.self::permissionHint($path)
            );
        }

        @chmod($path, 0775);
        @chmod(dirname($path), 0775);
        @chmod(storage_path('app/public'), 0775);
        @chmod(storage_path('app'), 0775);

        if (! File::isWritable($path)) {
            throw self::writeFailure($path);
        }
    }

    private static function writeFailure(string $path): RuntimeException
    {
        return new RuntimeException(
            'Unable to write to '.$path.' (PHP user: '.self::processUser().'). '.self::permissionHint($path)
        );
    }

    private static function processUser(): string
    {
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $info = posix_getpwuid(posix_geteuid());

            return $info['name'] ?? (string) posix_geteuid();
        }

        return get_current_user() ?: 'unknown';
    }

    private static function permissionHint(string $path): string
    {
        return 'In Coolify Terminal (not Execute Command) run: chown -R '.self::processUser().':'.self::processUser().' /app/storage/app/public && chmod -R 775 /app/storage/app/public';
    }
}
