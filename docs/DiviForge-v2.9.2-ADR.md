# ADR: Builder Tree as AI Context

## Status
Accepted

## Context
Raw Divi shortcodes preserve import fidelity but are hard for AI to reason about. AI needs hierarchy, relationships, positions and attributes.

## Decision
DiviForge exports `builder-tree.json` as an AI-friendly canonical context layer, while keeping raw `layout.json.divi_content` for import fidelity.

## Consequences
- AI has a clearer structure to modify.
- Import remains stable because the original Divi content is still included.
- Future tools can build validators, diffs and partial updates on top of the builder tree.
