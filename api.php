<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
$config = require __DIR__ . '/config.php';
require __DIR__ . '/lib/TrophyLibrary.php';
$lib = new TrophyLibrary($config);

function body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
function ok(mixed $data = null): never {
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_SLASHES);
    exit;
}
function fail(Throwable|string $e, int $status = 400): never {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $e instanceof Throwable ? $e->getMessage() : $e]);
    exit;
}

try {
    $action = $_GET['action'] ?? 'list';
    switch ($action) {
        case 'list':
            ok($lib->all());
        case 'configured':
            ok($lib->configured());
        case 'profiles':
            ok($lib->profiles());
        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
            ok($lib->upload($_FILES['image'] ?? [], $_POST['name'] ?? null));
        case 'profile':
            $id = (string)($_GET['id'] ?? '');
            if ($_SERVER['REQUEST_METHOD'] === 'GET') ok($lib->getProfile($id));
            $b = body(); ok($lib->saveProfile($id, $b));
        case 'rename':
            $b = body(); ok($lib->rename((string)($b['id'] ?? ''), (string)($b['name'] ?? '')));
        case 'delete':
            $b = body(); $lib->delete((string)($b['id'] ?? '')); ok(true);
        case 'save-engraving':
            ok($lib->saveEngraving(body()));
        case 'engraving':
            ok($lib->getEngraving((string)($_GET['id'] ?? '')));
        default:
            fail('Unknown action.', 404);
    }
} catch (Throwable $e) {
    fail($e, 500);
}
