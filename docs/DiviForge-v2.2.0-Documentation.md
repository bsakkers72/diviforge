# DiviForge v2.2.0 - Functional & Technical Documentation

**Release date:** 2026-07-06  
**Product:** DiviForge  
**Plugin version:** 2.2.0  
**Target platform:** WordPress + Divi / Divi Builder  
**Primary package format:** `.dfg` / `.zip`

---

## 1. Product vision

DiviForge is an AI-assisted workflow layer for building professional Divi pages from structured packages. A package contains the page structure, page-level CSS and supporting metadata. DiviForge converts that package into a draft WordPress page that can immediately be opened and refined in Divi.

The long-term direction is a complete AI development environment for Divi: prompt, generate, inspect, preview, import, version, update and reuse.

---

## 2. What changed in v2.2.0

Version 2.2.0 focuses on the import engine and fixes the most important workflow gap from v2.1.0.

### Added

- New `DiviForge_CSS_Importer` class.
- Automatic import of `page.css` into Divi Page Custom CSS.
- CSS is now written to Divi meta key `_et_pb_custom_css`.
- DiviForge also keeps its own canonical CSS copy in `_diviforge_page_css`.
- CSS import status, source, hash and timestamp are stored as metadata.
- New **Re-apply CSS** action after import.
- Import result panel with **Edit Page**, **Open in Divi** and **Re-apply CSS** buttons.
- Divi static CSS cache clearing hooks where available.
- Expanded package registry metadata.

### Fixed

- `page.css` was previously stored only in `_diviforge_page_css`, which Divi does not use for page-level custom CSS.
- Imported pages could therefore appear unstyled until CSS was manually copied into the Divi page settings.

---

## 3. Package specification v1.1

A first-generation DiviForge package should contain:

```text
manifest.json
layout.json
page.css
images/          optional
fonts/           optional, planned
preview/         optional, planned
```

### Required files

#### `manifest.json`

Contains package metadata.

Recommended fields:

```json
{
  "title": "Homepage Concept",
  "version": "1.0.0",
  "builder": "divi",
  "author": "DiviForge",
  "description": "AI-generated Divi page package"
}
```

#### `layout.json`

Contains the page structure: sections, rows, columns and modules.

#### `page.css`

Contains page-level CSS. In v2.2.0 this file is automatically written to Divi Page Custom CSS for the created or updated page.

---

## 4. Import workflow

```text
Upload package
    |
    v
Inspect package structure
    |
    v
Read manifest.json
    |
    v
Read layout.json
    |
    v
Create/update draft WordPress page
    |
    v
Activate Divi Builder metadata
    |
    v
Convert layout.json to Divi shortcodes
    |
    v
Read page.css
    |
    v
Store CSS in DiviForge registry
    |
    v
Write CSS to Divi Page Custom CSS
    |
    v
Clear Divi static CSS cache where available
    |
    v
Show import result actions
```

---

## 5. CSS import architecture

### Class

```text
includes/class-diviforge-css-importer.php
```

### Responsibilities

- Normalize imported CSS.
- Preserve valid CSS selectors and declarations.
- Remove unsafe PHP/script/style tags.
- Store canonical CSS for future re-apply/rollback.
- Write CSS to Divi Page Custom CSS.
- Store metadata for traceability.
- Clear Divi static CSS cache where possible.

### Metadata written

```text
_diviforge_page_css          canonical CSS copy
_et_pb_custom_css            Divi Page Custom CSS
_diviforge_css_source        package | stored-css
_diviforge_css_status        applied | missing
_diviforge_css_applied_at    timestamp
_diviforge_css_hash          sha256 hash of CSS
_diviforge_css_target_meta   _et_pb_custom_css
```

---

## 6. Package registry metadata

Each imported page receives metadata to connect it back to the imported package.

```text
_diviforge_version
_diviforge_package_hash
_diviforge_package_imported_at
_diviforge_package_title
_diviforge_package_filename
_diviforge_package_manifest
_diviforge_package_version
_diviforge_package_author
```

This prepares DiviForge for future update, diff and rollback functionality.

---

## 7. Admin interface

### Import Package

After import the user now sees:

- import success message
- layout status
- CSS status
- **Edit Page** button
- **Open in Divi** button
- **Re-apply CSS** button

### Re-apply CSS

The Re-apply CSS action reads `_diviforge_page_css` and writes it again to `_et_pb_custom_css`. This is useful when Divi settings, cache or manual edits caused page CSS to disappear or become stale.

---

## 8. Security model

- Import requires a user that can edit pages.
- Admin actions use WordPress nonces.
- Re-apply CSS requires permission to edit the target page.
- CSS is normalized and stripped of PHP/script/style tags while preserving normal CSS syntax.
- ZIP processing requires `ZipArchive`.

---

## 9. Current limitations

- Images inside `images/` are detected by the inspector but not yet automatically uploaded and remapped.
- Fonts are part of the package vision but are not yet imported.
- The layout importer is still intentionally simple and supports a basic subset of Divi modules.
- Preview rendering is planned but not included in this release.
- Rollback metadata is prepared, but full rollback is planned for a later version.

---

## 10. File structure

```text
diviforge/
├── diviforge.php
├── includes/
│   ├── class-diviforge.php
│   ├── class-diviforge-package.php
│   ├── class-diviforge-importer.php
│   ├── class-diviforge-css-importer.php
│   └── admin/
│       └── class-diviforge-admin.php
├── assets/
│   ├── css/admin.css
│   └── js/admin.js
├── docs/
└── examples/
    └── packages/
```

---

## 11. Roadmap

### v2.3 - Asset Import

- Upload `images/` to the WordPress media library.
- Replace local package image references with media URLs.
- Add missing asset warnings.
- Add package asset registry.

### v2.4 - Layout Importer Expansion

- Better row and column structure handling.
- More Divi modules.
- Module classes, row classes and column classes.
- Safer shortcode generation.

### v2.5 - Package Versioning

- Page/package history.
- Re-import layout.
- Re-import CSS.
- Rollback imported package.

### v3.0 - DiviForge Studio

- Prompt Studio.
- Package Studio.
- Preview engine.
- Component library.
- Design system integration.
- Marketplace-ready package format.

---

## 12. Developer notes

The main functional fix is in `DiviForge_CSS_Importer::apply_to_page()`.

The key implementation detail is:

```php
update_post_meta($post_id, '_diviforge_page_css', $css);
update_post_meta($post_id, '_et_pb_custom_css', $css);
```

The first meta key belongs to DiviForge. The second meta key is the Divi page-level custom CSS target.

---

## 13. Acceptance criteria for v2.2.0

- Uploading a package with `page.css` creates a draft page.
- The imported page has Divi Builder enabled.
- The page content is filled from `layout.json`.
- The CSS is visible in Divi Page Settings > Advanced > Custom CSS.
- The CSS is also stored in `_diviforge_page_css`.
- The import result screen offers Edit Page, Open in Divi and Re-apply CSS.
- Re-apply CSS restores the stored CSS into Divi Page Custom CSS.

