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

## Next step

Sprint 004 (AI Preview) and Sprint 005 (Package Builder) build on this foundation — see the repo root `README.md` for the full sprint sequence.
