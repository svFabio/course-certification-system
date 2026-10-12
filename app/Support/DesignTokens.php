<?php

declare(strict_types=1);

namespace App\Support;

final class DesignTokens
{
    public const NAVY = '#0E2E5F';

    public const NAVY_DARK = '#0A2247';

    public const RED = '#E01D2E';

    public const RED_DARK = '#8B0000';

    public const WHITE = '#FFFFFF';

    public const GRAY_100 = '#F5F5F5';

    public const GRAY_700 = '#4A4A4A';

    public const BLACK = '#121212';

    public const GRAY_200 = '#E5E5E5';

    public const GRAY_300 = '#D6D6D6';

    public const GREEN = '#16A34A';

    public const AMBER = '#D97706';

    public const SKY = '#0284C7';

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
