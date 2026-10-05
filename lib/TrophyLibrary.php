<?php
declare(strict_types=1);

final class TrophyLibrary
{
    private string $dataDir;
    private string $indexFile;

    public function __construct(private array $config)
    {
        $this->dataDir = rtrim($config['data_dir'], '/');
        $this->indexFile = $this->dataDir . '/library.json';
        foreach (['trophies','thumbs','profiles','engravings'] as $dir) {
            $path = $this->dataDir . '/' . $dir;
            if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
                throw new RuntimeException("Cannot create $path");
            }
        }
        if (!is_file($this->indexFile)) {
            file_put_contents($this->indexFile, "[]\n", LOCK_EX);
        }
    }

    public function all(): array
    {
        $rows = $this->readJson($this->indexFile, []);
        return is_array($rows) ? array_values($rows) : [];
    }

    public function get(string $id): ?array
    {
        foreach ($this->all() as $row) {
            if (($row['id'] ?? '') === $id) return $row;
        }
        return null;
    }

    public function upload(array $file, ?string $name = null): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed.');
        }
        if (($file['size'] ?? 0) > $this->config['max_upload_bytes']) {
            throw new RuntimeException('File exceeds upload limit.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = $this->config['allowed_mime'][$mime] ?? null;
        if (!$ext) throw new RuntimeException('Unsupported image type.');

        $size = @getimagesize($file['tmp_name']);
        if (!$size) throw new RuntimeException('Invalid image.');

        $id = bin2hex(random_bytes(8));
        $filename = $id . '.' . $ext;
        $target = $this->dataDir . '/trophies/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Unable to store image.');
        }

        $thumb = $this->makeThumbnail($target, $id, $mime);
        $now = gmdate('c');
        $entry = [
            'id' => $id,
            'name' => trim((string)$name) ?: pathinfo($file['name'] ?? 'Trophy', PATHINFO_FILENAME),
            'file' => 'data/trophies/' . $filename,
            'thumbnail' => $thumb,
            'mime' => $mime,
            'width' => $size[0],
            'height' => $size[1],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $rows = $this->all();
        $rows[] = $entry;
        $this->writeJson($this->indexFile, $rows);
        $this->saveProfile($id, [
            'id' => $id,
            'zone' => null,
            'defaults' => self::defaultSettings(),
        ]);
        return $entry;
    }

    public function rename(string $id, string $name): array
    {
        $name = trim($name);
        if ($name === '') throw new RuntimeException('Name cannot be empty.');
        $rows = $this->all();
        $found = null;
        foreach ($rows as &$row) {
            if (($row['id'] ?? '') === $id) {
                $row['name'] = $name;
                $row['updated_at'] = gmdate('c');
                $found = $row;
                break;
            }
        }
        unset($row);
        if (!$found) throw new RuntimeException('Trophy not found.');
        $this->writeJson($this->indexFile, $rows);
        return $found;
    }

    public function delete(string $id): void
    {
        $entry = $this->get($id);
        if (!$entry) throw new RuntimeException('Trophy not found.');
        foreach (['file','thumbnail'] as $k) {
            if (!empty($entry[$k])) {
                $path = dirname(__DIR__) . '/' . ltrim($entry[$k], '/');
                if (is_file($path)) @unlink($path);
            }
        }
        @unlink($this->profilePath($id));
        $rows = array_values(array_filter($this->all(), fn($r) => ($r['id'] ?? '') !== $id));
        $this->writeJson($this->indexFile, $rows);
    }

    public function configured(): array
    {
        $out = [];
        foreach ($this->all() as $row) {
            $profile = $this->readJson($this->profilePath((string)$row['id']), null);
            if (is_array($profile) && !empty($profile['zone'])) {
                $row['profile_updated_at'] = $profile['updated_at'] ?? null;
                $out[] = $row;
            }
        }
        return $out;
    }

    public function profiles(): array
    {
        $out = [];
        foreach ($this->all() as $row) {
            $profile = $this->readJson($this->profilePath((string)$row['id']), null);
            if (is_array($profile) && !empty($profile['zone'])) {
                $out[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'thumbnail' => $row['thumbnail'] ?? $row['file'],
                    'file' => $row['file'],
                    'width' => $row['width'],
                    'height' => $row['height'],
                    'updated_at' => $profile['updated_at'] ?? $row['updated_at'],
                    'profile' => $profile,
                ];
            }
        }
        usort($out, fn($a, $b) => strcmp((string)$b['updated_at'], (string)$a['updated_at']));
        return $out;
    }

    public function getProfile(string $id): array
    {
        if (!$this->get($id)) throw new RuntimeException('Trophy not found.');
        return $this->readJson($this->profilePath($id), [
            'id' => $id,
            'zone' => null,
            'defaults' => self::defaultSettings(),
        ]);
    }

    public function saveProfile(string $id, array $profile): array
    {
        if (!$this->get($id) && !is_file($this->profilePath($id))) {
            // Upload creates the index immediately before its profile.
        }
        $safe = [
            'id' => $id,
            'zone' => $profile['zone'] ?? null,
            'defaults' => array_replace_recursive(self::defaultSettings(), $profile['defaults'] ?? []),
            'updated_at' => gmdate('c'),
        ];
        $this->writeJson($this->profilePath($id), $safe);
        return $safe;
    }

    public function saveEngraving(array $job): array
    {
        $id = $job['id'] ?? bin2hex(random_bytes(8));
        $job['id'] = $id;
        $job['updated_at'] = gmdate('c');
        $this->writeJson($this->dataDir . '/engravings/' . basename($id) . '.json', $job);
        return $job;
    }

    public function getEngraving(string $id): array
    {
        $path = $this->dataDir . '/engravings/' . basename($id) . '.json';
        if (!is_file($path)) throw new RuntimeException('Engraving not found.');
        return $this->readJson($path, []);
    }

    public static function defaultSettings(): array
    {
        return [
            'font' => 'Georgia',
            'material' => 'gold',
            'projection' => 'flat',
            'curve' => 0,
            'lineSpacing' => 1.0,
            'offsetX' => 0,
            'offsetY' => 0,
            'lines' => [
                ['text' => 'Team Name', 'size' => 42],
                ['text' => 'Competition', 'size' => 32],
                ['text' => 'season/date', 'size' => 26],
            ],
        ];
    }

    private function profilePath(string $id): string
    {
        return $this->dataDir . '/profiles/' . basename($id) . '.json';
    }

    private function readJson(string $path, mixed $fallback): mixed
    {
        if (!is_file($path)) return $fallback;
        $decoded = json_decode((string)file_get_contents($path), true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $fallback;
    }

    private function writeJson(string $path, mixed $data): void
    {
        $tmp = $path . '.tmp';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false || file_put_contents($tmp, $json . "\n", LOCK_EX) === false || !rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException('Unable to write data.');
        }
    }

    private function makeThumbnail(string $source, string $id, string $mime): ?string
    {
        if (!extension_loaded('gd')) return null;
        $create = match ($mime) {
            'image/png' => 'imagecreatefrompng',
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/webp' => 'imagecreatefromwebp',
            default => null,
        };
        if (!$create || !function_exists($create)) return null;
        $src = @$create($source);
        if (!$src) return null;

        $w = imagesx($src); $h = imagesy($src);
        $max = (int)$this->config['thumbnail_max'];
        $scale = min(1, $max / max($w, $h));
        $tw = max(1, (int)round($w * $scale));
        $th = max(1, (int)round($h * $scale));
        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false); imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0,0,0,0,$tw,$th,$w,$h);
        $name = $id . '.webp';
        $out = $this->dataDir . '/thumbs/' . $name;
        imagewebp($dst, $out, 82);
        imagedestroy($src); imagedestroy($dst);
        return 'data/thumbs/' . $name;
    }
}
