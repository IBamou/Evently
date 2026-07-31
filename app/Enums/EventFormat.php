<?php

namespace App\Enums;

enum EventFormat: string
{
    case InPerson = 'in_person';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'In person',
            self::Online => 'Online',
        };
    }
}
