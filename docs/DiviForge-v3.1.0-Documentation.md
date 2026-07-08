# DiviForge v3.1.0 Documentation

## Release goal
v3.1.0 introduces the first direct **AI Improve** workflow inside DiviForge.

The goal is not yet automatic page overwrite. The goal is a safe MVP step:

1. Select a Divi page in AI Studio.
2. Choose a prompt template.
3. Add a custom request.
4. DiviForge builds the AI package context internally.
5. DiviForge sends the request to the active OpenAI provider.
6. The generated package response is shown in AI Studio for review.

Automatic preview/import is planned for v3.2.0.

## Important safety rule
v3.1.0 does **not** overwrite pages automatically. AI output is review-only.

## Scope
- Direct AI Improve from AI Studio.
- OpenAI provider support for the first direct generation path.
- Provider layer remains in place for other providers.
- AI response stored temporarily for the current user.
- AI Request History logs success/failure.
- Version overview updated.

## Out of scope
- Automatic import of the AI response.
- Visual diff.
- Desktop/tablet/mobile preview.
- Claude/Gemini direct calls.

These are planned after the v3.1 foundation.
