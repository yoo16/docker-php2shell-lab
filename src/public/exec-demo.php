<?php

declare(strict_types=1);

// ============================================================
// 教材専用: public/ に置かれた .php が GET パラメータでコマンドを実行してしまう例
//
// - 127.0.0.1 で動く教材用環境でのみ使うこと。
// - 安全のため、実行できるのは ls / id / whoami だけ。cmd と dir は許可リストの値のみ受け付ける。
// - php.ini の disable_functions に exec が含まれている間は、実行されない（防御が効いている）。
// ============================================================

// 許可リスト: 実行できるコマンド（ラベル => 実行コマンドを組み立てる関数）
// 利用者の入力文字列はコマンドに渡さず、ここで決めた値だけを使う。
// $dir は escapeshellarg でエスケープ済みの値が渡る（ls 系のみ使用）。
$commands = [
    'ls'     => static fn (string $dir): string => 'ls ' . $dir,
    'ls -l'  => static fn (string $dir): string => 'ls -l ' . $dir,
    'ls -la' => static fn (string $dir): string => 'ls -la ' . $dir,
    'id'     => static fn (string $dir): string => 'id',      // 実行ユーザー（www-data）を確認
    'whoami' => static fn (string $dir): string => 'whoami',  // 実行ユーザー名だけを確認
];

// 許可リスト: 一覧を表示できるディレクトリ（利用者の入力文字列は ls に渡さない）
$dirs = [
    '.'    => __DIR__,           // public/（公開領域）
    '..'   => dirname(__DIR__),  // src/（非公開のはずの領域）
    '/tmp' => '/tmp',
];

$cmd = (string) ($_GET['cmd'] ?? 'ls');
$dir = (string) ($_GET['dir'] ?? '.');

$message = '';
$output = [];

if (!isset($commands[$cmd]) || !isset($dirs[$dir])) {
    $message = '許可されていない値です。cmd は ls / ls -l / ls -la / id / whoami、dir は . / .. / /tmp のみ指定できます。';
} elseif (!function_exists('exec')) {
    // disable_functions で無効化された関数は function_exists() が false を返す
    $message = 'exec() は無効化されています（php.ini の disable_functions）。この設定が防御として効いています。';
} else {
    $line = $commands[$cmd](escapeshellarg($dirs[$dir])) . ' 2>&1';
    exec($line, $output, $status);
    $message = '実行しました: ' . $line . '（終了コード ' . $status . '）';
}

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>exec デモ（教材専用）</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
    .code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: rgba(15,23,42,.06); padding: .05em .4em; border-radius: .3em; font-size: .9em; }
  </style>
</head>

<body class="bg-slate-100 text-slate-800">
  <header class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 text-white">
    <div class="mx-auto max-w-3xl px-6 py-10">
      <p class="text-xs font-semibold uppercase tracking-[.28em] text-rose-300">Teaching Demo</p>
      <h1 class="mt-2 text-3xl font-black">exec デモ（教材専用）</h1>
      <p class="mt-3 text-sm leading-relaxed text-slate-200">
        <span class="code bg-white/15 text-rose-100"><?= $h('/exec-demo.php') ?></span>
        に GET パラメータを渡すと、公開領域に置かれた PHP が OS コマンドを実行してしまう例です。
      </p>
    </div>
  </header>

  <main class="mx-auto max-w-3xl space-y-6 px-6 py-8">

    <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm leading-relaxed text-amber-950">
      <strong class="font-bold">⚠️ 注意：</strong>
      教材用の環境（127.0.0.1）でのみ使用してください。実行できるのは
      <span class="code">ls</span> / <span class="code">id</span> / <span class="code">whoami</span> だけです。
      実習後は <span class="code">src/public/exec-demo.php</span> を必ず削除してください。
    </div>

    <form method="get" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
          <span class="mb-1 block text-sm font-bold text-slate-700">cmd（コマンド）</span>
          <select name="cmd"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
            <?php foreach (array_keys($commands) as $c): ?>
              <option value="<?= $h($c) ?>" <?= $c === $cmd ? 'selected' : '' ?>><?= $h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="block">
          <span class="mb-1 block text-sm font-bold text-slate-700">dir（ディレクトリ）</span>
          <select name="dir"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
            <?php foreach (array_keys($dirs) as $d): ?>
              <option value="<?= $h($d) ?>" <?= $d === $dir ? 'selected' : '' ?>><?= $h($d) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="mt-4 flex items-center gap-3">
        <button type="submit"
          class="rounded-lg bg-rose-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-300">実行する</button>
        <span class="text-xs text-slate-500">
          URL で直接指定も可能：<span class="code">/exec-demo.php?cmd=id&amp;dir=..</span>
        </span>
      </div>
    </form>

    <?php if ($message !== ''): ?>
      <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-2 text-sm font-bold text-slate-700">実行結果</h2>
        <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700"><?= $h($message) ?></p>
        <?php if ($output !== []): ?>
          <pre class="mt-3 overflow-auto rounded-lg bg-slate-900 p-4 text-xs leading-6 text-slate-100"><?= $h(implode("\n", $output)) ?></pre>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <div class="pt-2">
      <a href="/lab.html"
        class="inline-block rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-400">実習ページへ戻る</a>
    </div>

  </main>
</body>

</html>
