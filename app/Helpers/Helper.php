<?php

namespace App\Helpers;

/**
 * Small shared display helpers used across Blade views.
 *
 * PSR-4 autoloaded, so no composer.json "files" entry is required.
 */
final class Helper
{
    /**
     * Category slug → CSS gradient used for event art/card headers.
     *
     * Returns null for unknown/empty slugs so callers keep their own
     * fallback (they differ slightly per view).
     */
    public static function categoryGradient(?string $slug): ?string
    {
        $gradients = [
            'music' => 'linear-gradient(135deg,#1565D8,#0EA5E9)',
            'business' => 'linear-gradient(135deg,#D97706,#F59E0B)',
            'tech' => 'linear-gradient(135deg,#7C3AED,#0EA5E9)',
            'art' => 'linear-gradient(135deg,#14B8A6,#0EA5E9)',
            'sports' => 'linear-gradient(135deg,#0EA5E9,#14B8A6)',
            'food-drinks' => 'linear-gradient(135deg,#DC2626,#F59E0B)',
        ];

        return $gradients[$slug ?? ''] ?? null;
    }
}
