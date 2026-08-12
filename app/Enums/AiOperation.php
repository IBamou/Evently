<?php

namespace App\Enums;

enum AiOperation: string
{
    case DRAFT = 'generate_draft';
    case TRANSFORM = 'transform_field';
}
