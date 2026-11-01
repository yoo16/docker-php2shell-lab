<?php

declare(strict_types=1);

// セキュリティ教材専用:
// 未定義関数を呼び出して意図的に Fatal error を発生させる。
// display_errors=On では、このファイルの絶対パスやスタック情報がHTTPレスポンスへ露出する。
intentional_undefined_function_for_security_training();
