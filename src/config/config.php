<?php
declare(strict_types=1);

// .env は public/index.php で読み込み済み
// public ディレクトリの外に置くことが重要
return [
    'app_name' => 'Security Lab',
    'debug'    => false,
    'api_key'  => $_ENV['API_KEY'] ?? null,
];