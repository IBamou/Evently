<?php

namespace App\Enums;

enum AiGenerationStatus: string
{
    case Processing = 'processing';
    case Success = 'success';
    case Error = 'error';
}
