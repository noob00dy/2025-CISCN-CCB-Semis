<?php
declare(strict_types=1);

final class Util {
    public static function h(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function listUploads(string $dir): array {
        $out = [];
        if (!is_dir($dir)) return $out;
        $items = scandir($dir);
        if ($items === false) return $out;

        foreach ($items as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $f;
            if (is_file($path)) {
                $out[] = [
                    'name' => $f,
                    'size' => filesize($path) ?: 0,
                    'mtime' => filemtime($path) ?: time(),
                ];
            }
        }
        usort($out, fn($a,$b) => $b['mtime'] <=> $a['mtime']);
        return $out;
    }

    public static function niceSize(int $bytes): string {
        $units = ['B','KB','MB','GB'];
        $i = 0;
        $v = (float)$bytes;
        while ($v >= 1024 && $i < count($units)-1) { $v /= 1024; $i++; }
        return sprintf($i === 0 ? "%d %s" : "%.1f %s", $v, $units[$i]);
    }

    public static function safeUploadName(string $name): string {
        $name = str_replace(["\0", "\\", "/"], "", $name);
        if (strpos($name, "..") !== false || trim($name) === "") {
            return "file_" . bin2hex(random_bytes(4));
        }
        return $name;
    }

    private static function getAllowedUploadExtensions(): array {
        return [
            'txt', 'pdf',
            'doc', 'docx', 'rtf', 'odt',
            'xls', 'xlsx', 'csv',
            'ppt', 'pptx',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
        ];
    }

    public static function isAllowedUploadExtension(string $name): bool {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '') {
            return false;
        }
        return in_array($ext, self::getAllowedUploadExtensions(), true);
    }
}
