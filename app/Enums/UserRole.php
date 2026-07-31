<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Organizer = 'organizer';
    case Admin = 'admin';

    /**
     * Get a human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Organizer => 'Organizer',
            self::Admin => 'Admin',
        };
    }

    /**
     * Check if this role is the admin role.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Check if this role is the organizer role.
     */
    public function isOrganizer(): bool
    {
        return $this === self::Organizer;
    }
}
