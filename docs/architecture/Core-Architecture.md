# DiviForge Core Architecture

## Decision

DiviForge remains a WordPress plugin. The internal codebase will be modernized by introducing a modular `src/` architecture next to the existing `includes/` implementation.

The old implementation stays active while new services are introduced gradually. This prevents large rewrites from breaking the plugin.

## Current layers

```text
WordPress
  ↓
DiviForge plugin entrypoint
  ↓
Core Bootstrap
  ↓
Container / Config / Logger / Events
  ↓
Legacy DiviForge classes + future modules
```

## Directories

```text
src/Core             Core bootstrap, lifecycle, config, logger, container
src/Admin            Future admin modules
src/AI               Future AI engine modules
src/Infrastructure   WordPress and external API integrations
src/Repository       Data access layer
src/Service          Application services
```

## Migration rule

No existing working functionality is removed during architecture migration. New modules are added behind the core bootstrap and connected one by one.

## AI module (Sprint 001)

The WordPress-native AI foundation is in place under `src/AI`, `src/Repository` and `src/Infrastructure/Database` — see [ADR-001](../adr/ADR-001-AI-Foundation.md):

- AI service registration — done (`AiServiceProvider`, wired from `ServiceRegistry`)
- AI jobs repository — done (`AiJobRepositoryInterface` / `AiJobRepository`)
- AI database migration — done (`AiJobsTable`, `wp_diviforge_ai_jobs`)
- AI provider contracts — done (`AiProviderInterface`, `AiProviderRegistry`, no concrete providers yet)
- AI Jobs admin screen — done, shipped as a new page ("AI Request Log", `src/Admin/AiJobsScreen`) rather than rewiring the legacy `diviforge-ai-jobs` screen. See [ADR-002](../adr/ADR-002-AI-Jobs-UI.md) for why they stay separate.
- OpenAI provider — done (`AI\Provider\OpenAi\OpenAiProvider`, reuses the existing `diviforge_ai_settings` option). See [ADR-003](../adr/ADR-003-OpenAI-Provider.md). No cost calculation yet.
- AI Preview — done, but scoped narrowly: a raw prompt/response detail view for one job (`AiJobsScreen`), not a Divi layout preview. See [ADR-004](../adr/ADR-004-AI-Preview.md) — it flags an open product decision for Sprint 005 rather than guessing at it.

## Open decision for Sprint 005

"Package Builder" in the root README could mean two different things and needs a call from Barry before implementation:

1. A generic page **export** package (per the original handover spec: `page.json`, `page.css`, `assets/`, `instructions.md`, `metadata.json` as a ZIP) — independent of the AI job model, living in `src/Export`/`src/Packages`.
2. Extending `AiJob` to carry a parsed/validated Divi layout so the new AI engine can build an importable package directly from a job's response — which would start converging the new engine with the legacy AI Studio pipeline (see ADR-002, ADR-004).

Not started until this is decided.

## Next step

Sprint 005 (Package Builder) — blocked on the decision above.
