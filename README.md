# Trophy Engraver

Filesystem-backed PHP/JS trophy engraving application, intended for deployment under:

`/PromoteToKing/trophy-engraver/`

## Pages
- `index.php` — engraver
- `library.php` — artwork library
- `settings.php` — saved/pre-configured settings

## Storage
Bundled default artwork lives in `assets/default-artworks/` and is read-only from the UI. User-uploaded originals, thumbnails, and saved profiles live under `data/` and are intentionally ignored by Git.

The app initializes new engraving text as:
1. `Team Name`
2. `Competition`
3. `season/date`

## Requirements
- PHP 8.1+
- Fileinfo
- GD recommended for uploaded-artwork thumbnails
- writable `data/` tree
