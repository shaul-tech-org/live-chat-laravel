<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    public function store(UploadedFile $file): array
    {
        $path = $file->store('uploads/' . date('Y/m'), 'public');
        $url = Storage::disk('public')->url($path);

        return [
            'file_url' => $url,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ];
    }
}
