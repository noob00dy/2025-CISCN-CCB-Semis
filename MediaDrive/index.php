<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/User.php";
require_once __DIR__ . "/lib/Util.php";

$uploadsDir = "/var/www/html/uploads/";

$user = null;
if (isset($_COOKIE['user'])) {
    $user = @unserialize($_COOKIE['user']);
}
if (!$user instanceof User) {
    $user = new User("guest");
    setcookie("user", serialize($user), time() + 86400, "/");
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $f = $_FILES['file'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        $name = Util::safeUploadName($f['name'] ?? 'upload.bin');
        if (!Util::isAllowedUploadExtension($name)) {
            $msg = "Upload failed.";
        } else {
            $dst = $uploadsDir . $name;
            if (move_uploaded_file($f['tmp_name'], $dst)) {
                $msg = "Uploaded: " . $name;
            } else {
                $msg = "Upload failed.";
            }
        }
    } else {
        $msg = "Upload error: " . (string)$f['error'];
    }
}

$files = Util::listUploads($uploadsDir);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>MediaDrive</title>
  <link rel="stylesheet" href="/assets/style.css"/>
</head>
<body>
  <div class="app">
    <header class="topbar">
      <div class="brand">
        <div class="dot red"></div><div class="dot yellow"></div><div class="dot green"></div>
        <span class="brand-title">MediaDrive</span>
        <span class="badge">Preview & Convert</span>
      </div>

      <div class="actions">
        <a class="btn ghost" href="/profile.php">Preferences</a>
      </div>
    </header>

    <main class="content">
      <section class="card">
        <div class="card-head">
          <h2>Upload</h2>
          <p class="muted">Upload a file, then preview it.</p>
        </div>

        <?php if ($msg !== ""): ?>
          <div class="toast"><?= Util::h($msg) ?></div>
        <?php endif; ?>

        <form class="upload" method="post" enctype="multipart/form-data">
          <input class="file" type="file" name="file" required />
          <button class="btn primary" type="submit">Upload</button>
        </form>

        <div class="hint">
          Current user: <b><?= Util::h($user->name) ?></b> · Encoding: <b><?= Util::h($user->encoding) ?></b>
        </div>
      </section>

      <section class="card">
        <div class="card-head">
          <h2>My Files</h2>
          <p class="muted">Click preview to open a file.</p>
        </div>

        <?php if (count($files) === 0): ?>
          <div class="empty">No files yet. Upload one to get started.</div>
        <?php else: ?>
          <div class="table">
            <div class="row head">
              <div>Name</div><div>Size</div><div>Updated</div><div>Actions</div>
            </div>
            <?php foreach ($files as $it): ?>
              <div class="row">
                <div class="mono"><?= Util::h($it['name']) ?></div>
                <div><?= Util::h(Util::niceSize((int)$it['size'])) ?></div>
                <div><?= date("Y-m-d H:i:s", (int)$it['mtime']) ?></div>
                <div class="row-actions">
                  <a class="btn small" href="/preview.php?f=<?= urlencode($it['name']) ?>">Preview</a>
                  <a class="btn small ghost" href="/download.php?f=<?= urlencode($it['name']) ?>">Download</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </main>

    <footer class="footer">
      <span class="muted">MediaDrive · Internal tool</span>
      <a class="muted" href="/health.php">health</a>
    </footer>
  </div>
</body>
</html>
