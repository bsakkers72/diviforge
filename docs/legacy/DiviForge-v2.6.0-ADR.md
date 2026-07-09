# ADR — v2.6.0 Package Preview

## Decision
DiviForge will provide a lightweight preview generated from package JSON instead of trying to render Divi before import.

## Rationale
True Divi rendering requires a WordPress post context and builder processing. For MVP, a fast and safe approximation is more valuable than complex temporary post creation.

## Consequences
- Users get a useful preview before import.
- The preview is clearly labelled as approximate.
- A future release can replace this with true temporary-page rendering if needed.
