<?php
declare(strict_types=1);

final class TrophyLibrary
{
    private string $dataDir;
    private string $indexFile;
    private string $manifestFile;

    public function __construct(private array $config)
    {
        $this->dataDir = rtrim((string)$config['data_dir'], '/');
        $this->indexFile = $this->dataDir . '/library.json';
        $this->manifestFile = (string)$config['default_artworks_manifest'];
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
        return array_values(array_merge($this->defaultArtworks(), $this->userArtworks()));
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
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
        if (($file['size'] ?? 0) > $this->config['max_upload_bytes']) throw new RuntimeException('File exceeds upload limit.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = $this->config['allowed_mime'][$mime] ?? null;
        if (!$ext) throw new RuntimeException('Unsupported image type.');
        $size = @getimagesize($file['tmp_name']);
        if (!$size) throw new RuntimeException('Invalid image.');

        $id = bin2hex(random_bytes(8));
        $filename = $id . '.' . $ext;
        $target = $this->dataDir . '/trophies/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('Unable to store image.');

        $thumb = $this->makeThumbnail($target, $id, $mime);
        $now = gmdate('c');
        $entry = [
            'id' => $id,
            'name' => trim((string)$name) ?: pathinfo($file['name'] ?? 'Trophy', PATHINFO_FILENAME),
            'file' => 'data/trophies/' . $filename,
            'thumbnail' => $thumb ?: 'data/trophies/' . $filename,
            'mime' => $mime,
            'width' => (int)$size[0],
            'height' => (int)$size[1],
            'readonly' => false,
            'kind' => 'user',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $rows = $this->userArtworks();
        $rows[] = $entry;
        $this->writeJson($this->indexFile, $rows);
        return $entry;
    }

    public function rename(string $id, string $name): array
    {
        $this->assertMutable($id);
        $name = trim($name);
        if ($name === '') throw new RuntimeException('Name cannot be empty.');
        $rows = $this->userArtworks();
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
        if (!$found) throw new RuntimeException('Artwork not found.');
        $this->writeJson($this->indexFile, $rows);
        return $found;
    }

    public function delete(string $id): void
    {
        $this->assertMutable($id);
        $entry = $this->get($id);
        if (!$entry) throw new RuntimeException('Artwork not found.');
        foreach (['file','thumbnail'] as $k) {
            if (!empty($entry[$k])) {
                $path = dirname(__DIR__) . '/' . ltrim((string)$entry[$k], '/');
                if (is_file($path)) @unlink($path);
            }
        }
        @unlink($this->profilePath($id));
        $rows = array_values(array_filter($this->userArtworks(), fn($r) => ($r['id'] ?? '') !== $id));
        $this->writeJson($this->indexFile, $rows);
    }

    public function getProfile(string $id): array
    {
        if (!$this->get($id)) throw new RuntimeException('Artwork not found.');
        return $this->readJson($this->profilePath($id), [
            'id' => $id,
            'zone' => null,
            'defaults' => self::defaultSettings(),
        ]);
    }

    public function saveProfile(string $id, array $profile): array
    {
        if (!$this->get($id)) throw new RuntimeException('Artwork not found.');
        $defaults = array_replace_recursive(self::defaultSettings(), is_array($profile['defaults'] ?? null) ? $profile['defaults'] : []);
        $safe = [
            'id' => $id,
            'zone' => $profile['zone'] ?? ($defaults['zone'] ?? null),
            'defaults' => $defaults,
            'updated_at' => gmdate('c'),
        ];
        $safe['defaults']['zone'] = $safe['zone'];
        $this->writeJson($this->profilePath($id), $safe);
        return $safe;
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
                    'width' => $row['width'] ?? null,
                    'height' => $row['height'] ?? null,
                    'readonly' => $row['readonly'] ?? false,
                    'updated_at' => $profile['updated_at'] ?? $row['updated_at'],
                    'profile' => $profile,
                ];
            }
        }
        usort($out, fn($a, $b) => strcmp((string)$b['updated_at'], (string)$a['updated_at']));
        return $out;
    }

    public static function defaultSettings(): array
    {
        return [
            'lines' => [
                ['text' => 'Team Name', 'size' => 44],
                ['text' => 'Competition', 'size' => 34],
                ['text' => 'season/date', 'size' => 28],
            ],
            'font' => 'Georgia',
            'interline' => 8,
            'material' => 'gold',
            'surface' => 'flat',
            'curve' => 28,
            'offsetX' => 0,
            'offsetY' => 0,
            'opacity' => 0.82,
            'tone' => -10,
            'emboss' => 2,
            'contrast' => 0.38,
            'highlight' => 0.34,
            'shadow' => 0.36,
            'textScale' => 1,
            'tracking' => 0,
            'zone' => null,
        ];
    }

    private function defaultArtworks(): array
    {
        $rows = $this->readJson($this->manifestFile, []);
        if (!is_array($rows)) return [];
        foreach ($rows as &$row) {
            $row['readonly'] = true;
            if (empty($row['width']) || empty($row['height'])) {
                $path = dirname(__DIR__) . '/' . ltrim((string)($row['file'] ?? ''), '/');
                $size = is_file($path) ? @getimagesize($path) : false;
                if ($size) { $row['width'] = (int)$size[0]; $row['height'] = (int)$size[1]; }
            }
        }
        unset($row);
        return array_values($rows);
    }

    private function userArtworks(): array
    {
        $rows = $this->readJson($this->indexFile, []);
        return is_array($rows) ? array_values($rows) : [];
    }

    private function assertMutable(string $id): void
    {
        $entry = $this->get($id);
        if (!$entry) throw new RuntimeException('Artwork not found.');
        if (!empty($entry['readonly'])) throw new RuntimeException('Bundled default artwork cannot be renamed or deleted.');
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
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
        $name = $id . '.webp';
        $out = $this->dataDir . '/thumbs/' . $name;
        imagewebp($dst, $out, 82);
        imagedestroy($src); imagedestroy($dst);
        return 'data/thumbs/' . $name;
    }
}
