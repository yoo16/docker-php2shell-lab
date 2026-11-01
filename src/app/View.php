<?php
declare(strict_types=1);

namespace App;

final class View
{
    /** app/views/{name}.php を読み込み、$data を変数として渡して出力する */
    public static function render(string $name, array $data = []): void
    {
        $file = __DIR__ . '/views/' . $name . '.php';
        (static function () use ($file, $data): void {
            extract($data, EXTR_SKIP);
            require $file;
        })();
    }

    /** HTML エスケープ(テンプレートからは必ずこれを通す) */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
