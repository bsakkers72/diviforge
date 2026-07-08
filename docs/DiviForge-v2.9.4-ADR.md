# ADR - v2.9.4 AI Fidelity Analysis

## Decision
DiviForge export will include semantic, statistical and relational AI context files in addition to raw Divi content and builder-tree.json.

## Context
AI assistants need more than raw Divi shortcodes. They need to understand hierarchy, CSS ownership, media usage and page intent.

## Consequences
- `builder-tree.json` remains canonical for AI understanding.
- `layout.json.divi_content` remains important for Divi import fidelity.
- `semantic-structure.json`, `statistics.json` and `relations.json` are advisory context files.
- Export packages become larger but more useful for AI roundtrip workflows.
