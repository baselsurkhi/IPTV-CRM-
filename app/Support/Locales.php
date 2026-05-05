<?php

namespace App\Support;

final class Locales
{
    /** @var list<string> */
    public const SUPPORTED = ['ar', 'en', 'he'];

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::SUPPORTED, true);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'ar' => 'العربية',
            'en' => 'English',
            'he' => 'עברית',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function filamentLabels(): array
    {
        return [
            'ar' => 'العربية (RTL)',
            'en' => 'English (LTR)',
            'he' => 'עברית (RTL)',
        ];
    }
}
