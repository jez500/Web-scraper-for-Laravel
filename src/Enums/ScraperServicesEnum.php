<?php

namespace Jez500\WebScraperForLaravel\Enums;

enum ScraperServicesEnum: string
{
    case Http = 'http';

    case Api = 'api';

    case Fake = 'fake';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
