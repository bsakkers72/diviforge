# ADR - v3.0.1 AI Provider Layer

## Decision
DiviForge will use a provider-independent AI configuration model. OpenAI remains the recommended default, but provider-specific settings are isolated from the AI workflow layer.

## Rationale
This prevents lock-in and prepares DiviForge for Claude, Gemini, Azure OpenAI, OpenRouter and local Ollama workflows.

## Consequences
Future AI features must call a provider abstraction instead of hardcoding OpenAI request logic.
