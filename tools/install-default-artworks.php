<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(2);
}
if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "ZipArchive is required.\n");
    exit(3);
}
$zipPath = $argv[1] ?? '';
if ($zipPath === '' || !is_file($zipPath)) {
    fwrite(STDERR, "Usage: php tools/install-default-artworks.php /path/to/default-artworks.zip\n");
    exit(4);
}

$root = dirname(__DIR__);
$dest = $root . '/assets/default-artworks';
$manifestPath = $dest . '/manifest.json';
$manifest = json_decode((string)file_get_contents($manifestPath), true);
if (!is_array($manifest)) {
    fwrite(STDERR, "Invalid default artwork manifest.\n");
    exit(5);
}

$expected = [];
foreach ($manifest as $row) {
    $file = basename((string)($row['file'] ?? ''));
    if ($file !== '') $expected[$file] = true;
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    fwrite(STDERR, "Unable to open ZIP.\n");
    exit(6);
}

$present = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = basename((string)$zip->getNameIndex($i));
    if ($name !== '') $present[$name] = true;
}
$missing = array_diff_key($expected, $present);
if ($missing) {
    fwrite(STDERR, "ZIP is missing expected artwork: " . implode(', ', array_keys($missing)) . "\n");
    $zip->close();
    exit(7);
}

foreach (array_keys($expected) as $name) {
    $stream = $zip->getStream($name);
    if (!$stream) {
        fwrite(STDERR, "Unable to read $name from ZIP.\n");
        $zip->close();
        exit(8);
    }
    $out = fopen($dest . '/' . $name . '.tmp', 'wb');
    if (!$out) {
        fclose($stream);
        fwrite(STDERR, "Unable to write $name.\n");
        $zip->close();
        exit(9);
    }
    stream_copy_to_stream($stream, $out);
    fclose($stream);
    fclose($out);
    if (!rename($dest . '/' . $name . '.tmp', $dest . '/' . $name)) {
        fwrite(STDERR, "Unable to finalize $name.\n");
        $zip->close();
        exit(10);
    }
}
$zip->close();

foreach (array_keys($expected) as $name) {
    $path = $dest . '/' . $name;
    $size = @getimagesize($path);
    if (!$size || ($size['mime'] ?? '') !== 'image/webp') {
        fwrite(STDERR, "Validation failed for $name.\n");
        exit(11);
    }
}

echo "Installed " . count($expected) . " default artworks into assets/default-artworks.\n";
