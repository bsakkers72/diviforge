# DiviForge v2.9.2 Release Notes

## Focus
AI Package v3: richer export context for existing Divi pages.

## Added
- `builder-tree.json` with an AI-friendly hierarchy: page → sections → rows → columns → modules.
- Stable node IDs, parent IDs, positions, Divi tags, Divi types, attributes, classes and categorized settings.
- Module content extraction with HTML, plain text and excerpts where available.
- `page-meta.json` containing page details, WordPress post metadata and Divi-relevant metadata.
- `theme.json` containing WordPress/theme context.
- `css/variables.json` with detected CSS variables, selectors and CSS line count.
- `scripts/scripts.json` with detected script blocks and source hints.
- Expanded asset metadata for exported images: attachment ID, alt text, caption, MIME type, dimensions, filesize and hash where available.

## Changed
- Package schema updated to `diviforge-package/v3`.
- Layout schema updated to `diviforge-layout/v3`.
- AI request now treats `builder-tree.json` as a canonical AI source alongside raw Divi content and `page.css`.

## Notes
Raw `layout.json.divi_content` remains available for import fidelity. `builder-tree.json` is intended to help ChatGPT/Claude understand and modify the page without guessing the Divi structure.
