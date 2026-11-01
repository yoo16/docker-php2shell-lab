<?php
declare(strict_types=1);

namespace App;

final class Security
{
    /** php.ini では無効化できないストリームラッパーを外す */
    private const BLOCKED_WRAPPERS = ['phar', 'data', 'glob', 'ftp', 'ftps'];

    public static function disableStreamWrappers(): void
    {
        foreach (self::BLOCKED_WRAPPERS as $wrapper) {
            if (in_array($wrapper, stream_get_wrappers(), true)) {
                stream_wrapper_unregister($wrapper);
            }
        }
    }
}
