<?php
declare(strict_types=1);

namespace App;

final class Hello
{
    public static function message(string $appName): string
    {
        return sprintf('%s / PHP %s', $appName, PHP_VERSION);
    }
}
