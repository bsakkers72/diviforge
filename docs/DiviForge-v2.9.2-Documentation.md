# DiviForge v2.9.2 Documentation

## Purpose
v2.9.2 improves the AI export format so an existing Divi page can be handed to ChatGPT/Claude with as much usable structure and context as possible.

## Exported files
A page export now contains:

- `manifest.json` — package metadata, page stats, asset manifest and user request.
- `layout.json` — raw Divi shortcode content plus structured sections, rows, columns and modules.
- `builder-tree.json` — canonical AI-friendly hierarchy: page → sections → rows → columns → modules.
- `page-meta.json` — WordPress page details, full relevant post meta and Divi-related meta.
- `theme.json` — active WordPress/theme context.
- `page.css` — page-level Divi Custom CSS.
- `css/variables.json` — detected CSS variables, selectors and line counts.
- `class-map.json` — CSS classes detected from Divi attributes and page CSS.
- `divi-context.json` — WordPress, Divi, shortcode and script context.
- `scripts/page-scripts.js` — raw detected scripts.
- `scripts/scripts.json` — script manifest.
- `assets/images/` — local WordPress image files referenced by the page where readable.
- `page.html` — AI context rendering of the raw Divi content.
- `AI_REQUEST.json`, `prompt.txt`, `chatgpt-instructions.md`, `README.md`.

## Builder tree
`builder-tree.json` gives AI a hierarchy with:

- stable generated node IDs;
- parent/child relationships;
- positions inside the page;
- Divi tags and module types;
- all detected shortcode attributes;
- extracted CSS classes;
- categorized settings: layout, style, responsive, animation, visibility and custom;
- module content excerpts where available.

## Assets
Local WordPress upload images are copied into `assets/images/` when possible. The manifest records URL, filename, export status, zip path, attachment ID, alt text, caption, MIME type, dimensions, filesize and hash where available.

## Import compatibility
The raw Divi content remains in `layout.json.divi_content` to preserve import fidelity. The new files are additional context for AI and future DiviForge tooling.
