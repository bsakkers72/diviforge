# DiviForge v2.4.1 Documentation

## Sprint 4 — UX & Workflow, release 2

DiviForge v2.4.1 improves the Pages workflow that was introduced in v2.4.0. The main goal is to make page management more visual and useful when the number of generated Divi pages grows.

## New functionality

### 1. Premium Pages cards
The Pages screen now shows richer page cards with:

- featured/preview thumbnail area;
- DiviForge badge for pages created or updated through DiviForge;
- package title and version;
- page status;
- package statistics;
- quick package upload per page;
- Preview, Divi, Edit, CSS and Delete actions.

### 2. Package thumbnail support
When a package contains a preview image, DiviForge attempts to import that image into the WordPress Media Library and set it as the page featured image.

Supported thumbnail locations include:

- `preview/`
- `thumb/`
- `thumbnail/`
- `screenshots/`
- `preview.png`, `preview.jpg`, `preview.webp`
- `thumbnail.png`, `thumbnail.jpg`, `thumbnail.webp`

If no preview image is found, DiviForge uses the first image in `images/` as fallback.

### 3. Package statistics
During import DiviForge now stores package statistics per page:

- number of sections;
- number of rows;
- number of columns;
- number of modules;
- number of images;
- number of CSS lines.

These values are shown on the page cards.

### 4. View modes
The Pages screen now supports:

- Grid view;
- Compact view.

The selected view is stored locally in the browser.

### 5. Improved filters
The page filter bar now includes:

- All;
- Published;
- Drafts;
- DiviForge;
- With thumbnail.

## Technical changes

### Importer
`DiviForge_Importer` now includes:

- `package_stats()`
- `import_package_thumbnail()`

New post meta keys:

- `_diviforge_package_stats`
- `_diviforge_stat_sections`
- `_diviforge_stat_rows`
- `_diviforge_stat_columns`
- `_diviforge_stat_modules`
- `_diviforge_stat_images`
- `_diviforge_stat_css_lines`
- `_diviforge_package_thumbnail_id`

### Package Inspector
`DiviForge_Package::inspect_upload()` now returns additional package statistics and preview image information.

## Roadmap position

v2.4.1 completes the next step of Sprint 4: visual Pages management. The next release should continue with the Import Wizard and pre-import inspection flow.
