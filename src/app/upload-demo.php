<?php

declare(strict_types=1);

// セキュリティ教材専用。受信可否だけを確認し、アップロードファイルは保存しない。
header('Content-Type: text/html; charset=UTF-8');

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$uploadsEnabled = filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOL);
$upload = $_FILES['training_file'] ?? null;

$uploadErrors = [
    UPLOAD_ERR_INI_SIZE => 'php.ini の upload_max_filesize を超えました',
    UPLOAD_ERR_FORM_SIZE => 'フォームの MAX_FILE_SIZE を超えました',
    UPLOAD_ERR_PARTIAL => '一部だけアップロードされました',
    UPLOAD_ERR_NO_FILE => 'ファイルが選択されていません',
    UPLOAD_ERR_NO_TMP_DIR => '一時保存ディレクトリがありません',
    UPLOAD_ERR_CANT_WRITE => '一時ファイルを書き込めませんでした',
    UPLOAD_ERR_EXTENSION => 'PHP拡張機能によって中止されました',
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>file_uploads 動作確認</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 p-6 text-slate-800">
  <main class="mx-auto max-w-2xl space-y-6">
    <a href="/" class="text-sm font-bold text-sky-700 hover:underline">← 環境チェックへ戻る</a>

    <section class="rounded-2xl bg-white p-6 shadow">
      <h1 class="text-2xl font-black">file_uploads 動作確認</h1>
      <p class="mt-2 text-sm leading-relaxed text-slate-600">
        選択したファイルが <code>$_FILES</code> に入るかを確認します。この教材ページはファイルを保存しません。
      </p>

      <div class="mt-5 rounded-xl border p-4 <?= $uploadsEnabled ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>">
        <span class="font-mono font-bold">file_uploads = <?= $uploadsEnabled ? 'On' : 'Off' ?></span>
      </div>

      <form action="/?demo=upload" method="post" enctype="multipart/form-data" class="mt-6 space-y-4">
        <input type="hidden" name="MAX_FILE_SIZE" value="1048576">
        <label class="block">
          <span class="mb-2 block text-sm font-bold">確認するファイル（最大1MB）</span>
          <input type="file" name="training_file" required class="block w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
        </label>
        <button type="submit" class="rounded-lg bg-slate-900 px-5 py-3 text-sm font-bold text-white hover:bg-slate-700">
          アップロード可否を確認
        </button>
      </form>
    </section>

    <?php if ($isPost): ?>
      <section class="rounded-2xl border bg-white p-6 shadow <?= $upload === null ? 'border-amber-300' : 'border-sky-300' ?>">
        <h2 class="text-lg font-black">確認結果</h2>
        <?php if ($upload === null): ?>
          <p class="mt-3 font-bold text-amber-800"><code>$_FILES</code> は空です。</p>
          <p class="mt-2 text-sm text-slate-600">
            <?= $uploadsEnabled
                ? 'ファイルサイズやPOSTサイズなど、ほかの制限も確認してください。'
                : 'file_uploads=Off のため、PHPはHTTPアップロードを受け付けていません。' ?>
          </p>
        <?php else: ?>
          <?php $errorCode = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE); ?>
          <?php if ($errorCode === UPLOAD_ERR_OK): ?>
            <p class="mt-3 font-bold text-rose-700">PHPの一時ファイルとして受信され、<code>$_FILES</code> に入りました。</p>
            <dl class="mt-4 grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
              <dt class="font-bold">元の名前</dt><dd><?= $escape((string) ($upload['name'] ?? '')) ?></dd>
              <dt class="font-bold">サイズ</dt><dd><?= (int) ($upload['size'] ?? 0) ?> bytes</dd>
              <dt class="font-bold">一時アップロード</dt><dd><?= is_uploaded_file((string) ($upload['tmp_name'] ?? '')) ? '確認できました' : '確認できませんでした' ?></dd>
              <dt class="font-bold">保存処理</dt><dd>実施していません</dd>
            </dl>
          <?php else: ?>
            <p class="mt-3 font-bold text-amber-800">
              アップロードエラー <?= $errorCode ?>:
              <?= $escape($uploadErrors[$errorCode] ?? '不明なエラー') ?>
            </p>
          <?php endif; ?>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
