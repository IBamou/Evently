<?php

namespace App\Enums;

enum AiGenerationStatus: string
{
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case ERROR = 'error';
    case BLOCKED = 'blocked';
}
