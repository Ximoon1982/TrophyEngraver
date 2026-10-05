<?php
declare(strict_types=1);
return [
    'app_name' => 'Trophy Engraver',
    'data_dir' => __DIR__ . '/data',
    'default_artworks_manifest' => __DIR__ . '/assets/default-artworks/manifest.json',
    'max_upload_bytes' => 25 * 1024 * 1024,
    'allowed_mime' => [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ],
    'thumbnail_max' => 360,
];
