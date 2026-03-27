<?php

namespace App\Enums;

enum ContentType: string
{
    case Text = 'text';
    case Image = 'image';
    case File = 'file';
}
