<?php

/** @var string $appName */
/** @var string $phpVersion */
/** @var bool $apiKeyConfigured */
/** @var string $baseDir */

use App\View;

$publicDir = realpath($baseDir . '/public');
$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$documentRootIsPublic = $publicDir !== false && $documentRoot === $publicDir;
$requiredDisabledFunctions = ['exec', 'passthru', 'shell_exec', 'system', 'proc_open', 'popen'];
$disabledFunctions = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
$dangerousFunctionsDisabled = array_diff($requiredDisabledFunctions, $disabledFunctions) === [];
$blockedWrappers = ['phar', 'data', 'glob', 'ftp', 'ftps'];
$enabledBlockedWrappers = array_intersect($blockedWrappers, stream_get_wrappers());

$checks = [
  ['DocumentRoot', $documentRootIsPublic ? 'public/ のみ公開' : 'public/ 以外を公開', $documentRootIsPublic],
  ['API_KEY', $apiKeyConfigured ? '.env から設定済み' : '未設定（.env を確認）', $apiKeyConfigured],
  ['display_errors', ini_get('display_errors') ? 'On(画面に出る)' : 'Off', !ini_get('display_errors')],
  ['display_startup_errors', ini_get('display_startup_errors') ? 'On（画面に出る）' : 'Off', !ini_get('display_startup_errors')],
  ['log_errors', ini_get('log_errors') ? 'On' : 'Off', (bool) ini_get('log_errors')],
  ['expose_php', ini_get('expose_php') ? 'On' : 'Off', !ini_get('expose_php')],
  ['allow_url_fopen', ini_get('allow_url_fopen') ? 'On' : 'Off', !ini_get('allow_url_fopen')],
  ['allow_url_include', ini_get('allow_url_include') ? 'On' : 'Off', !ini_get('allow_url_include')],
  ['open_basedir', ini_get('open_basedir') ?: '未設定', (bool) ini_get('open_basedir')],
  ['危険な関数', $dangerousFunctionsDisabled ? '主要な関数を無効化' : '有効な関数あり', $dangerousFunctionsDisabled],
  ['ストリームラッパー', $enabledBlockedWrappers === [] ? '危険なラッパーを無効化' : implode(', ', $enabledBlockedWrappers) . ' が有効', $enabledBlockedWrappers === []],
  ['file_uploads', ini_get('file_uploads') ? 'On' : 'Off', !ini_get('file_uploads')],
  ['session.upload_progress.enabled', ini_get('session.upload_progress.enabled') ? 'On' : 'Off', !ini_get('session.upload_progress.enabled')],
  ['session.use_strict_mode', ini_get('session.use_strict_mode') ? 'On' : 'Off', (bool) ini_get('session.use_strict_mode')],
  ['session.use_only_cookies', ini_get('session.use_only_cookies') ? 'On' : 'Off', (bool) ini_get('session.use_only_cookies')],
  ['session.cookie_httponly', ini_get('session.cookie_httponly') ? 'On' : 'Off', (bool) ini_get('session.cookie_httponly')],
  ['session.cookie_samesite', ini_get('session.cookie_samesite') ?: '未設定', in_array(ini_get('session.cookie_samesite'), ['Lax', 'Strict'], true)],
];
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= View::e($appName) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 text-slate-800">
  <header class="bg-gradient-to-br from-slate-900 to-slate-700 text-white">
    <div class="mx-auto max-w-4xl px-6 py-12">
      <p class="text-xs font-semibold uppercase tracking-[.28em] text-sky-300">Docker + Nginx + PHP-FPM</p>
      <h1 class="mt-3 text-4xl font-black"><?= View::e($appName) ?></h1>
      <p class="mt-4 inline-block rounded-full border border-white/20 bg-white/10 px-4 py-1 text-sm">
        PHP <?= View::e($phpVersion) ?>
      </p>
      <div class="mt-6 flex flex-wrap gap-3">
        <a href="/preview.html"
          class="px-5 py-3 rounded-xl bg-sky-500 hover:bg-sky-400 transition font-semibold shadow-soft">解説ページ</a>
        <a href="/lab.html"
          class="px-5 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 transition font-semibold shadow-soft">実習ページ</a>
        <a href="/glossary.html"
          class="px-5 py-3 rounded-xl bg-violet-500 hover:bg-violet-400 transition font-semibold shadow-soft">用語集ページ</a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-4xl space-y-8 px-6 py-10">
    <section class="rounded-2xl bg-white p-6 shadow">
      <h2 class="mb-4 text-xl font-bold">環境チェック</h2>
      <ul class="divide-y divide-slate-200">
        <?php foreach ($checks as [$label, $value, $ok]): ?>
          <li class="flex items-center justify-between gap-4 py-3">
            <span class="font-mono text-sm"><?= View::e($label) ?></span>
            <span class="flex items-center gap-2 text-sm">
              <span class="text-slate-600"><?= View::e($value) ?></span>
              <?php if ($label === 'API_KEY'): ?>
                <a
                  href="/.env"
                  download=".env"
                  class="rounded-md border border-amber-300 bg-amber-50 px-2 py-1 text-xs font-bold text-amber-800 hover:bg-amber-100"
                >.env取得を確認</a>
              <?php endif; ?>
              <?php if ($label === 'display_errors'): ?>
                <a
                  href="/?demo=fatal"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="rounded-md border border-rose-300 bg-rose-50 px-2 py-1 text-xs font-bold text-rose-700 hover:bg-rose-100"
                >Fatal表示を確認</a>
              <?php endif; ?>
              <?php if ($label === 'file_uploads'): ?>
                <a
                  href="/?demo=upload"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="rounded-md border border-violet-300 bg-violet-50 px-2 py-1 text-xs font-bold text-violet-700 hover:bg-violet-100"
                >アップロード可否を確認</a>
              <?php endif; ?>
              <span class="rounded-full px-2 py-0.5 text-xs font-bold <?= $ok ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                <?= $ok ? 'OK' : 'NG' ?>
              </span>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm leading-relaxed text-rose-900">
        <p class="font-bold">⚠️ PHP 8.2 は 2026年12月31日 にセキュリティサポートが終了します</p>
        <p class="mt-1">
          終了後は脆弱性が見つかっても修正版が出ません。8.2 を使っている環境は、早めに 8.3 以降へ移行してください。
        </p>
        <table class="mt-3 w-full text-left text-xs">
          <thead class="text-rose-700">
            <tr>
              <th class="py-1">バージョン</th>
              <th>バグ修正の終了</th>
              <th>セキュリティ修正の終了</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-rose-200 font-mono">
            <tr>
              <td class="py-1">8.2</td>
              <td>2024-12-31(終了)</td>
              <td class="font-bold">2026-12-31</td>
            </tr>
            <tr>
              <td class="py-1">8.3</td>
              <td>2025-12-31(終了)</td>
              <td>2027-12-31</td>
            </tr>
            <tr>
              <td class="py-1">8.4(この環境)</td>
              <td>2026-12-31</td>
              <td>2028-12-31</td>
            </tr>
          </tbody>
        </table>
        <p class="mt-2 text-rose-700">日付は php.net の Supported Versions に基づきます。最新情報は公式サイトで確認してください。</p>
      </div>
    </section>

    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm leading-relaxed text-amber-900">
      <h2 class="mb-2 text-base font-bold">公開しているのは public/ だけ</h2>
      <p>
        <code class="font-mono">app/</code>、<code class="font-mono">config/</code>、<code class="font-mono">vendor/</code>、
        <code class="font-mono">.env</code> は DocumentRoot の外にあります。
        <code class="font-mono">public/</code> には機密ファイルを置かないでください。
      </p>
    </section>
  </main>
</body>

</html>
