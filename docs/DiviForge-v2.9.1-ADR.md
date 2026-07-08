# ADR - v2.9.1 Deep Divi Export

## Decision
DiviForge exports both the raw Divi shortcode content and a structured JSON representation of sections, rows, columns and modules.

## Reason
Raw Divi content is safest for import fidelity. Structured JSON is better for AI understanding. Keeping both enables reliable roundtrips without hiding page structure from ChatGPT/Claude.

## Consequences
- `layout.json.divi_content` remains canonical for import.
- Structured arrays are advisory AI context.
- Export packages are larger but more useful.
- Local assets are exported when resolvable; external assets remain URL references.
