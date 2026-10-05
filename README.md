# Trophy Engraver

Filesystem-backed web application for creating reusable engraved trophy artwork.

## Deployment

The project is intended to be deployed under:

```
/PromoteToKing/trophy-engraver/
```

The application is otherwise independent from the Promote to King codebase.

## Storage model

Original trophy images are stored unchanged on disk. Metadata and engraving defaults are stored as JSON profiles.

```
data/
  trophies/      Original uploaded trophy images
  thumbs/        Generated library thumbnails
  profiles/      Trophy definitions and engraving-zone metadata
  engravings/    Saved engraving jobs
  library.json   Trophy library index
```

The data directory must be writable by PHP. Runtime data is intentionally excluded from Git apart from placeholder files.

## Initial capabilities

- filesystem-backed trophy library
- PNG/JPEG/WebP upload
- search/sort library explorer
- manual engraving-zone selection on canvas
- three engraving lines with independent sizes
- line spacing and X/Y offsets
- fine nudging
- flat/cylindrical modes with curve strength
- font and metal/color presets
- save trophy defaults
- save/load engraving jobs
- PNG export in the source image dimensions

## Requirements

- PHP 8.1+
- GD recommended for thumbnail generation
- writable `data/` tree

No database is required for the initial version.
