# ADR - v2.9.5 Export Extraction Quality

## Decision
Do not add new export files in this release. Improve the actual data extraction quality of the existing package structure.

## Rationale
The v2.9.4 export architecture was good, but exported data could still be incomplete when CSS was stored in non-default meta keys or when Divi omitted column shortcodes. Better data fidelity is more valuable for the MVP than adding more UI or additional files.

## Consequences
- `builder-tree.json` becomes more reliable because implicit columns are generated when needed.
- `page.css`, `design.json` and `css/analysis.json` become useful when CSS is stored in older DiviForge/Royal MCP keys.
- Asset detection improves for CSS background images.
