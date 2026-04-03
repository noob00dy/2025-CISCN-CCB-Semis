<?php
declare(strict_types=1);
require_once __DIR__ . "/lib/User.php";

$uploadsDir = "/var/www/html/uploads/";

$user = null;
if (isset($_COOKIE['user'])) {
    $user = @unserialize($_COOKIE['user']);
}
if (!$user instanceof User) $user = new User("guest");

$f = (string)($_GET['f'] ?? "");
if ($f === "") { http_response_code(400); echo "Missing f"; exit; }

$path = $uploadsDir . $f;
$path = @iconv($user->encoding, "UTF-8//IGNORE", $path);
if ($path === false || $path === "") { http_response_code(500); echo "Conversion failed"; exit; }

$real = realpath($path);
$uploadsReal = realpath($uploadsDir);
if ($real === false || $uploadsReal === false || strpos($real . DIRECTORY_SEPARATOR, $uploadsReal . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(404);
    echo "Not found";
    exit;
}
if (!is_file($real)) { http_response_code(404); echo "Not found"; exit; }

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . basename($f) . "\"");
readfile($real);
