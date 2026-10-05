# Trophy Engraver

Filesystem-backed PHP/JS trophy engraving application, intended for deployment under:

`/PromoteToKing/trophy-engraver/`

## Pages
- `index.php` — engraver
- `library.php` — artwork library
- `settings.php` — saved/pre-configured settings

## Engraver workflow
- large full-trophy preview plus a smaller plate close-up
- text fields directly below the plate close-up
- full-screen engraving-zone selection with adjustable corners
- optional engraving-zone display in the normal trophy preview
- fit-to-screen full-preview modal
- three initialized lines: `Team Name`, `Competition`, `season/date`
- flat or curved-plate projection
- Gold, Silver, Bronze, Dark and Light material presets
- opacity, tone, emboss/recess, X/Y positioning and nudges
- visible values for slider controls
- less-used finish controls folded under Advanced

## Artwork library
Bundled defaults are read-only from the UI and use neutral sequential names:
- `Trophy 1` … `Trophy 21`
- `Medal 1` … `Medal 5`

The manifest is `assets/default-artworks/manifest.json`. User-uploaded originals, thumbnails and saved profiles live under `data/` and are intentionally ignored by Git.

To install or refresh the bundled image files from the supplied artwork ZIP:

```bash
php tools/install-default-artworks.php /path/to/default-artworks.zip
```

## Requirements
- PHP 8.1+
- Fileinfo
- ZipArchive for installing the bundled artwork package
- GD recommended for uploaded-artwork thumbnails
- writable `data/` tree
