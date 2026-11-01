<?php

declare(strict_types=1);

// 公開されるのはこの public/ だけ。app/ config/ vendor/ .env は DocumentRoot の外にある。
$baseDir = dirname(__DIR__);

require $baseDir . '/vendor/autoload.php';

// セキュリティ上の理由から、PHP のストリームラッパーを無効化。file_get_contents() などで外部 URL を読み込むことができなくなる。
App\Security::disableStreamWrappers();
// .env ファイルが存在する場合は読み込む。存在しない場合は無視する。
Dotenv\Dotenv::createImmutable($baseDir)->safeLoad();
// config/config.php を読み込み
$config = require $baseDir . '/config/config.php';

// セキュリティ教材用のエラー表示確認。
// http://127.0.0.1:8099/?demo=fatal へアクセスすると、意図的に Fatal error を発生させる。
if (($_GET['demo'] ?? '') === 'fatal') {
    require $baseDir . '/app/fatal-demo.php';
}

// file_uploads と $_FILES の動作を確認する教材ページ。
if (($_GET['demo'] ?? '') === 'upload') {
    require $baseDir . '/app/upload-demo.php';
    exit;
}

// 文字コードを UTF-8 に設定
header('Content-Type: text/html; charset=UTF-8');

// View を使って home テンプレートをレンダリング
App\View::render('home', [
    'appName'          => $config['app_name'],
    'phpVersion'       => PHP_VERSION,
    'apiKeyConfigured' => !empty($config['api_key']),
    'baseDir'          => $baseDir,
]);
