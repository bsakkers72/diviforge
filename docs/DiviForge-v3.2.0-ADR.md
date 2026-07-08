# ADR - DiviForge v3.2.0 AI Jobs and Preview

## Decision

AI output must not be applied directly to a page. It must first become an AI Job and pass through AI Preview.

## Reason

Direct AI output can be incomplete, invalid or visually unsafe. A job-based workflow gives the user review, validation and discard options before changing WordPress content.

## Consequences

- AI Improve writes a job.
- AI Preview becomes the approval gate.
- Import is explicit.
- Later async processing and background queues can reuse the same model.
