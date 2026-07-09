# ADR - DiviForge v3.1.0 AI Improve Review-First Workflow

## Decision
AI Improve responses are not imported automatically in v3.1.0.

## Reason
AI output must be validated before replacing a Divi page. A review-first workflow reduces risk while testing direct provider integration.

## Consequences
- Users can test real API calls from DiviForge.
- The generated output can be inspected before further automation.
- v3.2.0 can focus on validation, preview and controlled import.
