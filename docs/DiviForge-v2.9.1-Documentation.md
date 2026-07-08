# DiviForge v2.9.1 Documentation

## Release focus
Deep Divi Export improves the AI Roundtrip export package so ChatGPT/Claude receives as much Divi page context as possible.

## Export package v2 additions
The export ZIP now contains:

- `manifest.json` with richer metadata, stats and asset manifest.
- `layout.json` with raw `divi_content` plus structured arrays for:
  - sections;
  - rows;
  - columns;
  - modules;
  - all detected shortcode attributes;
  - detected CSS classes.
- `page.css` with Divi Page Custom CSS.
- `design.json` with detected colors, fonts and radius values.
- `divi-context.json` with WordPress, theme, page meta, shortcode tags and script context.
- `class-map.json` with all detected classes from Divi shortcodes and CSS selectors.
- `AI_REQUEST.json` with AI-neutral task instructions.
- `scripts/page-scripts.js` with detected inline scripts and relevant script-like page meta.
- `assets/images/` with local WordPress media files referenced by the page where DiviForge can resolve them.
- `page.html`, `prompt.txt`, `chatgpt-instructions.md` and `README.md`.

## Important implementation notes
`layout.json.divi_content` remains the canonical source for roundtrip import. The structured arrays are generated as AI context. This avoids losing Divi shortcode fidelity while still giving AI a clear overview of the page hierarchy.

Local images are copied when they can be resolved through the WordPress uploads directory or `attachment_url_to_postid()`. External images remain as URLs in the manifest.
