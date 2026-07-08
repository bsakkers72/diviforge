# DiviForge v2.9.5 Documentation

## Purpose
v2.9.5 stabilizes the AI export format by improving extraction quality rather than adding new package files.

## Export quality rule
A DiviForge export should contain enough information for an AI assistant to understand and reconstruct the page using only the package.

## Key changes

### Complete CSS source lookup
The exporter now checks several known CSS meta keys:

- `_et_pb_custom_css`
- `_diviforge_page_css`
- `_royal_mcp_page_custom_css`
- `_et_pb_page_custom_css`
- `_et_pb_custom_css_page`
- `_et_pb_post_custom_css`

This prevents exports with empty `page.css` when CSS was stored by an earlier DiviForge/Royal MCP import flow.

### Normalized Divi hierarchy
Divi stored content may contain modules directly inside rows without explicit column shortcodes. The exporter now creates implicit columns so `builder-tree.json` always remains AI-friendly:

```text
Page
└── Section
    └── Row
        └── Column
            └── Module
```

### CSS analysis
When CSS is found, `css/analysis.json` includes selector rules, declarations, class references, media query counts and node binding hints.

### Asset detection
Asset collection now detects regular URLs, shortcode image attributes and CSS `url(...)` references. Local WordPress uploads are copied into `assets/images/` when possible.

## Package format
v2.9.5 exports `diviforge-package/v6` and `diviforge-builder-tree/v4`.
