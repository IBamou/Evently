<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Valid = 'valid';
    case Used = 'used';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid',
            self::Used => 'Used',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isValid(): bool
    {
        return $this === self::Valid;
    }

    public function isUsed(): bool
    {
        return $this === self::Used;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }
}
