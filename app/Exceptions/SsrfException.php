<?php

namespace App\Exceptions;

class SsrfException extends AppException
{
    public function __construct(string $message = '차단된 URL입니다.')
    {
        parent::__construct('SSRF_BLOCKED', $message, 403);
    }
}
