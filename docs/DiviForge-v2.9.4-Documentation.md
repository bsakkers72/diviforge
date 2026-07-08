# DiviForge v2.9.4 Documentation

## Focus
v2.9.4 improves the AI export quality. The goal is not to add a new workflow screen, but to make exported packages richer, more machine-readable and more useful for ChatGPT, Claude or another AI assistant.

## New / improved export files
A page export can now include:

- `builder-tree.json` — canonical Divi hierarchy with semantic hints.
- `css/analysis.json` — CSS selector analysis with declarations, media query context, property groups and builder-node matches.
- `semantic-structure.json` — inferred page roles such as hero, CTA, FAQ, pricing, testimonials, gallery, footer, navigation and form.
- `statistics.json` — section, row, column, module, module-type, CSS, asset and semantic counts.
- `relations.json` — relation model joining builder nodes, CSS rules, classes, semantic roles and assets.
- `assets/assets.json` — media manifest with metadata and `used_by` context where detectable.
- `design.json` — design tokens detected from both page CSS and Divi shortcode attributes.

## AI fidelity improvements
The export now gives an AI more context about:

- which CSS selectors belong to which Divi nodes;
- which images are used by which modules or CSS rules;
- which section/module likely acts as hero, CTA, FAQ, footer, etc.;
- how many modules and module types exist;
- which CSS declarations relate to layout, spacing, typography, color, background, border, shadow or motion.

## Canonical architecture
`builder-tree.json` remains the main AI-readable structure. `layout.json.divi_content` remains available for import fidelity. The new semantic and relation files are supporting context, not destructive replacement sources.
