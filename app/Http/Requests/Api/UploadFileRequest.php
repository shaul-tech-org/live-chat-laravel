<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UploadFileRequest extends FormRequest
{
    /**
     * 허용된 MIME 타입 화이트리스트
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
        'text/plain',
        'text/csv',
    ];

    /**
     * 차단 확장자 블랙리스트 (실행 파일, 아카이브)
     */
    private const BLOCKED_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'sh', 'php', 'js', 'py',
        'zip', 'tar', 'gz', 'rar', '7z',
        'com', 'msi', 'scr', 'pif', 'hta', 'cpl', 'jar', 'vbs', 'wsf',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpeg,png,gif,webp,svg,pdf,txt,csv',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $file = $this->file('file');

                if (! $file || ! $file->isValid()) {
                    return;
                }

                $originalName = $file->getClientOriginalName();

                // 이중 확장자 차단 (예: image.jpg.exe)
                if ($this->hasDoubleExtension($originalName)) {
                    $validator->errors()->add(
                        'file',
                        '이중 확장자를 가진 파일은 업로드할 수 없습니다.'
                    );

                    return;
                }

                // 차단 확장자 검사
                $extension = strtolower($file->getClientOriginalExtension());
                if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
                    $validator->errors()->add(
                        'file',
                        "'{$extension}' 확장자를 가진 파일은 업로드할 수 없습니다."
                    );

                    return;
                }

                // 실제 MIME 타입 검증 (확장자 위조 방지)
                $actualMimeType = $file->getMimeType();
                if (! in_array($actualMimeType, self::ALLOWED_MIME_TYPES, true)) {
                    $validator->errors()->add(
                        'file',
                        "허용되지 않는 파일 형식입니다. (감지된 타입: {$actualMimeType})"
                    );
                }
            },
        ];
    }

    /**
     * 이중 확장자 여부 확인 (예: file.jpg.exe, file.pdf.php)
     */
    private function hasDoubleExtension(string $filename): bool
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        return str_contains($basename, '.');
    }

    public function messages(): array
    {
        return [
            'file.required' => '파일을 첨부해주세요.',
            'file.file' => '유효한 파일을 업로드해주세요.',
            'file.max' => '파일 크기는 10MB를 초과할 수 없습니다.',
            'file.mimes' => '허용된 파일 형식: jpeg, png, gif, webp, svg, pdf, txt, csv',
        ];
    }
}
