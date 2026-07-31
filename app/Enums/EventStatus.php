<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Published = 'published';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::UnderReview => 'Under review',
            self::Published => 'Published',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isUnderReview(): bool
    {
        return $this === self::UnderReview;
    }

    public function isPublished(): bool
    {
        return $this === self::Published;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }
}
