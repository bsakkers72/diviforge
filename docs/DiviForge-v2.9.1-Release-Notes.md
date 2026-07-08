# DiviForge v2.9.1 Release Notes

## Deep Divi Export

- Export package schema updated to `diviforge-package/v2`.
- `layout.json` now includes raw Divi content plus structured sections, rows, columns and modules.
- Shortcode attributes and custom CSS classes are exported for AI context.
- Added `divi-context.json` for WordPress/page metadata and script context.
- Added `class-map.json` for classes detected in Divi shortcodes and CSS.
- Added `AI_REQUEST.json` for AI-neutral instructions.
- Added `scripts/page-scripts.js` for detected inline scripts and script-like metadata.
- Local WordPress media assets used by the page are copied into `assets/images/` where possible.
- Version overview updated with v2.9.1.
