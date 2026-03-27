<?php

namespace App\Enums;

enum SenderType: string
{
    case Visitor = 'visitor';
    case Agent = 'agent';
    case System = 'system';
}
