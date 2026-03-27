<?php

namespace App\Exceptions;

class UnauthorizedException extends AppException
{
    public function __construct(string $message = '인증이 필요합니다.')
    {
        parent::__construct('UNAUTHORIZED', $message, 401);
    }
}
