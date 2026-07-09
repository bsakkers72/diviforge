# ADR-001: AI Foundation

## Status
Accepted

## Context
Roadmap 3.0 requires AI jobs, providers and preview workflows. The legacy `includes/` admin class already has working AI Studio / AI Chat / AI Jobs screens, but stores job state ad hoc (options, transients, post meta) with no shared contract between providers. [ADR-000](ADR-000-Core-Architecture.md) established the `src/` core bootstrap this foundation builds on.

## Decision
Introduce the AI module foundation in `src/AI`, `src/Repository` and `src/Infrastructure/Database`:

- `AI\Job\AiJob` / `AiJobStatus` — the job entity and its status contract (queued, running, completed, error).
- `AI\Provider\AiProviderInterface` — the contract every provider (OpenAI, Anthropic, Google, Local LLM) must implement, plus `AiPrompt` / `AiCompletion` value objects and an `AiProviderRegistry` to resolve providers by key. No concrete providers are implemented yet — that is Sprint 003.
- `Repository\AiJobRepositoryInterface` / `AiJobRepository` — persistence for jobs against a dedicated table.
- `Infrastructure\Database\AiJobsTable` — creates `wp_diviforge_ai_jobs` (provider, model, prompt, response, status, tokens, cost, timestamps) via `dbDelta`, hooked into the plugin's activation flow.
- `AI\AiService` — orchestrates submitting and running a job through the registry; `AI\AiServiceProvider` registers all of the above into the Core `Container` from `ServiceRegistry::registerDefaults()`.

## Consequences
- Every AI request going forward becomes a job row instead of a one-off option/transient, which is required for cost/token tracking and the AI Jobs admin screen.
- Adding a provider means writing one class implementing `AiProviderInterface` and registering it in `AiProviderRegistry` — no other code changes.
- The legacy AI Studio / AI Jobs screens in `includes/` are untouched by this ADR. Wiring them to read from the new repository/table is a follow-up decision, not made here.

## Non-goals
- No concrete AI provider implementations (OpenAI/Anthropic/Google/Local LLM) — Sprint 003.
- No admin UI changes — the legacy AI Jobs screen keeps running as-is until it's explicitly migrated.
- No rewrite of the existing importer/exporter logic.
