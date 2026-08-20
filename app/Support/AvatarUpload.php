<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AvatarUpload
{
    public static function store(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$ext;

        return PublicUpload::store($file, 'images/avatars', $filename);
    }

    public static function delete(?string $path): void
    {
        PublicUpload::delete($path);
    }
}
