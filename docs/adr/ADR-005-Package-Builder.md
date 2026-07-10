# ADR-005: Package Builder

## Status
Accepted

## Context
The original handover spec asked for page export as a "DiviForge Package": a ZIP containing at minimum `page.json`, `page.css`, `assets/`, `instructions.md`, `metadata.json`, capturing sections/rows/columns/modules/settings/classes/custom CSS/scripts/Divi settings/images/assets — never just content — plus an auto-generated AI prompt with an optional user instruction appended.

Before writing new code in `src/Export`/`src/Packages`, the existing legacy implementation was checked to avoid duplicating work. It already exceeds the spec by a wide margin:

- `includes/class-diviforge-package.php` — package inspection/validation for uploaded packages.
- `includes/admin/class-diviforge-admin.php`, `build_export_package_data()` — builds a "package format v6" covering manifest, layout, builder-tree (sections/rows/columns/modules with ids and positions), page meta, theme context, CSS variables, full CSS analysis, semantic structure, statistics, a relation model, script extraction, and asset collection (including downloading referenced media into the ZIP).
- `handle_export_page_package()` — streams all of the above into a ZIP, including `AI_REQUEST.json` and `prompt.txt`: an auto-generated AI prompt built from the page (`build_ai_improve_prompt()`), with the user's free-text instruction appended — exactly the "automatic AI prompt + optional user instruction" requirement.

## Decision
Do not build a new export/package pipeline in `src/Export` or `src/Packages`. The handover spec's package export requirement is already satisfied by the existing `includes/` implementation. `src/Export` and `src/Packages` stay empty scaffolding until a concrete new-architecture consumer needs to trigger an export (e.g. if the AI module is later extended to build a package from a job response — see the open Sprint 004/005 convergence question in [ADR-004](ADR-004-AI-Preview.md)).

No adapter/wrapper is added at this point either: there is currently no caller in `src/` that needs to invoke export through a DI-friendly interface, and writing one without a consumer would be speculative abstraction with no way to validate the interface shape.

## Consequences
- Sprint 005 as originally scoped ("Package Builder") requires no implementation work right now.
- `includes/` is untouched, consistent with every prior ADR's non-goal of not rewriting importer/exporter logic.
- If a future sprint needs programmatic access to export (not just the admin-post form flow), that is a new, concretely-scoped decision — not something to anticipate here.

## Non-goals
- No new export/package code in `src/`.
- No refactor of `build_export_package_data()` or `handle_export_page_package()`.
