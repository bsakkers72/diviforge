# DiviForge v2.9.3 Release Notes

## Added

- `builder-tree.json` upgraded to schema `diviforge-builder-tree/v2`.
- New `css/analysis.json` with selector/declaration analysis and class-to-builder-node hints.
- New `assets/assets.json` with the exported asset manifest.
- Richer normalized Divi attribute categories for sections, rows, columns and modules.
- Responsive attribute extraction for tablet/phone and pipe-separated responsive values.
- Per-node CSS binding hints based on detected classes.
- Per-node asset references detected from Divi attributes.
- Richer `design.json` with colors, fonts, spacing, radius, shadows, gradients, transitions, animations and breakpoints.
- Version overview updated with v2.9.2 and v2.9.3.

## Changed

- Package schema moved to `diviforge-package/v4`.
- Layout schema moved to `diviforge-layout/v4`.
- Export package format is now labelled **AI Package v4 / High Fidelity Builder Tree Export**.
- AI prompt guidance now prioritizes `builder-tree.json` for understanding the page structure.

## Notes

- `layout.json.divi_content` and `page.css` remain the import fidelity sources.
- `builder-tree.json` is the canonical AI understanding layer, not a replacement for the raw Divi shortcode content yet.
