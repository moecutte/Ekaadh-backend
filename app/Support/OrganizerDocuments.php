<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OrganizerDocuments
{
    /**
     * @param  array{id_type: string, id_front?: UploadedFile|null, id_back?: UploadedFile|null, business_license?: UploadedFile|null}  $files
     * @param  array<string, mixed>|null  $existing
     * @return array{id_type: string, id_front: ?string, id_back: ?string, business_license: ?string}
     */
    public static function store(int $userId, array $files, ?array $existing = null): array
    {
        $docs = [
            'id_type' => $files['id_type'],
            'id_front' => is_string($existing['id_front'] ?? null) ? $existing['id_front'] : null,
            'id_back' => is_string($existing['id_back'] ?? null) ? $existing['id_back'] : null,
            'business_license' => is_string($existing['business_license'] ?? null) ? $existing['business_license'] : null,
        ];

        $disk = Storage::disk('public');
        $dir = "organizer-docs/{$userId}";

        foreach (['id_front' => 'id-front', 'id_back' => 'id-back', 'business_license' => 'business-license'] as $key => $prefix) {
            $upload = $files[$key] ?? null;
            if (! $upload instanceof UploadedFile) {
                continue;
            }

            if (! empty($docs[$key]) && $disk->exists($docs[$key])) {
                $disk->delete($docs[$key]);
            }

            $ext = strtolower($upload->getClientOriginalExtension() ?: 'bin');
            $docs[$key] = $upload->storeAs($dir, "{$prefix}.{$ext}", 'public');
        }

        return $docs;
    }
}
