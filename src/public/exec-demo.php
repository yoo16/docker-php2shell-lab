<?php

declare(strict_types=1);

// ============================================================
// 教材専用: public/ に置かれた .php が GET パラメータでコマンドを実行してしまう例
//
// - 127.0.0.1 で動く教材用環境でのみ使うこと。
// - 安全のため、実行できるのは ls だけ。cmd と dir は許可リストの値のみ受け付ける。
// - php.ini の disable_functions に exec が含まれている間は、実行されない（防御が効いている）。
// ============================================================

// 許可リスト: 実行できるコマンドと、ls に渡せるオプション
$commands = [
    'ls'     => '',
    'ls -l'  => '-l',
    'ls -la' => '-la',
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
    $message = '許可されていない値です。cmd は ls / ls -l / ls -la、dir は . / .. / /tmp のみ指定できます。';
} elseif (!function_exists('exec')) {
    // disable_functions で無効化された関数は function_exists() が false を返す
    $message = 'exec() は無効化されています（php.ini の disable_functions）。この設定が防御として効いています。';
} else {
    $line = 'ls ' . $commands[$cmd] . ' ' . escapeshellarg($dirs[$dir]) . ' 2>&1';
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
  <style>
    body { font-family: system-ui, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1rem; line-height: 1.6; }
    .warn { background: #fef3c7; border: 1px solid #f59e0b; padding: .75rem 1rem; border-radius: .5rem; }
    pre { background: #0f172a; color: #e2e8f0; padding: 1rem; border-radius: .5rem; overflow: auto; }
    code { background: #e2e8f0; padding: 0 .3rem; border-radius: .25rem; }
  </style>
</head>

<body>
  <h1>exec デモ（教材専用）</h1>
  <p class="warn">
    教材用の環境でのみ使用してください。実行できるのは <code>ls</code> だけです。実習後は <code>src/public/exec-demo.php</code> を必ず削除してください。
  </p>

  <form method="get">
    <p>
      <label>cmd:
        <select name="cmd">
          <?php foreach (array_keys($commands) as $c): ?>
            <option value="<?= $h($c) ?>" <?= $c === $cmd ? 'selected' : '' ?>><?= $h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>dir:
        <select name="dir">
          <?php foreach (array_keys($dirs) as $d): ?>
            <option value="<?= $h($d) ?>" <?= $d === $dir ? 'selected' : '' ?>><?= $h($d) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit">実行</button>
    </p>
  </form>

  <p>URL で直接指定することもできます: <code>/exec-demo.php?cmd=ls&amp;dir=..</code></p>

  <p><strong><?= $h($message) ?></strong></p>
  <?php if ($output !== []): ?>
    <pre><?= $h(implode("\n", $output)) ?></pre>
  <?php endif; ?>
</body>

</html>
