<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/User.php";
require_once __DIR__ . "/lib/Util.php";

$user = null;
if (isset($_COOKIE['user'])) {
    $user = @unserialize($_COOKIE['user']);
}
if (!$user instanceof User) {
    $user = new User("guest");
}

$msg = "";
$allowed = ["UTF-8", "GBK", "BIG5", "ISO-2022-CN-EXT"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enc = (string)($_POST['encoding'] ?? "UTF-8");
    if (!in_array($enc, $allowed, true)) {
        $msg = "Unsupported encoding";
    } else {
        $user->encoding = $enc;
        setcookie("user", serialize($user), time() + 86400, "/");
        $msg = "Saved";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Preferences · MediaDrive</title>
  <link rel="stylesheet" href="/assets/style.css"/>
</head>
<body>
  <div class="app">
    <header class="topbar">
      <div class="brand">
        <div class="dot red"></div><div class="dot yellow"></div><div class="dot green"></div>
        <a class="brand-title link" href="/">MediaDrive</a>
        <span class="badge">Preferences</span>
      </div>
      <div class="actions"></div>
    </header>

    <main class="content">
      <section class="card">
        <div class="card-head">
          <h2>Preview Encoding</h2>
          <p class="muted">Choose how filenames are converted before preview.</p>
        </div>

        <?php if ($msg !== ""): ?>
          <div class="toast"><?= Util::h($msg) ?></div>
        <?php endif; ?>

        <form method="post" class="prefs">
          <label class="label">Encoding</label>
          <select class="select" name="encoding">
            <?php foreach ($allowed as $e): ?>
              <option value="<?= Util::h($e) ?>" <?= $user->encoding === $e ? "selected" : "" ?>>
                <?= Util::h($e) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <div class="row-actions">
            <button class="btn primary" type="submit">Save</button>
            <a class="btn ghost" href="/">Back</a>
          </div>

          <div class="hint">
            Stored in <span class="mono">user</span> cookie.
          </div>
        </form>
      </section>
    </main>
  </div>
</body>
</html>
