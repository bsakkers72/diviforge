# ADR - v2.9.3 AI Fidelity Export

## Decision

DiviForge will treat `builder-tree.json` as the canonical AI understanding layer for exported Divi pages, while preserving `layout.json.divi_content` and `page.css` as the import fidelity sources.

## Context

AI tools need more than raw Divi shortcodes to safely modify a page. They need hierarchy, settings, responsive variants, CSS relationships, asset references and design tokens.

## Consequences

- Export packages become richer but still remain ZIP-based and simple to exchange.
- AI assistants receive a more complete page dossier.
- Future import logic can gradually move toward tree-based generation while still supporting raw Divi content.
