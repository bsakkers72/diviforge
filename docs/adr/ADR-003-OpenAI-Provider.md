# ADR-003: OpenAI Provider

## Status
Accepted

## Context
[ADR-001](ADR-001-AI-Foundation.md) defined `AiProviderInterface` with no concrete implementation. The legacy AI Studio (`includes/admin/class-diviforge-admin.php`, `call_ai_provider()`) already calls OpenAI's `/v1/responses` endpoint directly using settings from the `diviforge_ai_settings` option (api key, model, temperature, max tokens, timeout).

## Decision
Add `AI\Provider\OpenAi\OpenAiProvider implements AiProviderInterface`, calling the same OpenAI endpoint and reading the same `diviforge_ai_settings` option so Barry does not have to configure API credentials twice. `AiServiceProvider` registers it into `AiProviderRegistry` under the container.

## Consequences
- Any code that resolves `AiService` from the container can now run a job against real OpenAI models.
- The legacy AI Studio's own direct HTTP call is untouched — this is a second, independent call path. Nothing in `includes/` was changed.
- The provider is always registered, even without an API key configured. Running a job without a key raises a `RuntimeException`, which `AiService::run()` turns into an `error` job — this is the intended behavior, not a bug.

## Non-goals
- No cost calculation. `AiCompletion::cost()` is always `0.0` for OpenAI — a real per-model pricing table is future work, deliberately not guessed at here.
- No Anthropic, Google or Local LLM provider yet.
- No change to the legacy AI Studio provider settings screen or its own OpenAI call.
