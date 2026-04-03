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
    setcookie("user", serialize($user), time() + 86400, "/");
}

$f = (string)($_GET['f'] ?? "");
if ($f === "") {
    http_response_code(400);
    echo "Missing parameter: f";
    exit;
}

$rawPath = $user->basePath . $f;

if (preg_match('/flag|\/flag|\.\.|php:|data:|expect:/', $rawPath)) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

$convertedPath = @iconv($user->encoding, "UTF-8//IGNORE", $rawPath);

if ($convertedPath === false || $convertedPath === "") {
    http_response_code(500);
    echo "Conversion failed";
    exit;
}

$content = @file_get_contents($convertedPath);
if ($content === false) {
    http_response_code(404);
    echo "Not found";
    exit;
}

$displayRaw = $rawPath;
$displayConv = $convertedPath;
$isText = true;

for ($i=0; $i<min(strlen($content), 512); $i++) {
    $c = ord($content[$i]);
    if ($c === 0) { $isText = false; break; }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Preview · MediaDrive</title>
  <link rel="stylesheet" href="/assets/style.css"/>
</head>
<body>
  <div class="app">
    <header class="topbar">
      <div class="brand">
        <div class="dot red"></div><div class="dot yellow"></div><div class="dot green"></div>
        <a class="brand-title link" href="/">MediaDrive</a>
        <span class="badge">Preview</span>
      </div>
      <div class="actions">
        <a class="btn ghost" href="/profile.php">Preferences</a>
      </div>
    </header>

    <main class="content">
      <section class="card">
        <div class="card-head">
          <h2>File Preview</h2>
          <p class="muted">Converted paths are shown for debugging.</p>
        </div>

        <div class="kv">
          <div><span class="k">User</span><span class="v"><?= Util::h($user->name) ?></span></div>
          <div><span class="k">Encoding</span><span class="v mono"><?= Util::h($user->encoding) ?></span></div>
          <div><span class="k">Raw path</span><span class="v mono"><?= Util::h($displayRaw) ?></span></div>
          <div><span class="k">Converted</span><span class="v mono"><?= Util::h($displayConv) ?></span></div>
        </div>

        <div class="row-actions">
          <a class="btn ghost" href="/">Back</a>
          <a class="btn" href="/download.php?f=<?= urlencode($f) ?>">Download</a>
        </div>

        <div class="preview">
          <?php if ($isText): ?>
            <pre><?= Util::h($content) ?></pre>
          <?php else: ?>
            <pre class="mono"><?=
              Util::h(bin2hex(substr($content, 0, 2048)))
            ?></pre>
            <div class="hint">Binary preview (hex, first 2KB)</div>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <footer class="footer">
      <span class="muted">MediaDrive · Internal tool</span>
      <a class="muted" href="/health.php">health</a>
    </footer>
  </div>
</body>
</html>
