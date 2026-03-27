<?php

namespace App\Exceptions;

class ForbiddenException extends AppException
{
    public function __construct(string $message = '접근이 거부되었습니다.')
    {
        parent::__construct('FORBIDDEN', $message, 403);
    }
}
