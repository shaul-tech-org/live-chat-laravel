<?php

namespace App\Exceptions;

class NotFoundException extends AppException
{
    public function __construct(string $message = '리소스를 찾을 수 없습니다.')
    {
        parent::__construct('NOT_FOUND', $message, 404);
    }
}
