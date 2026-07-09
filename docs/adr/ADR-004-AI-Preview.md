# ADR-004: AI Preview (job detail view)

## Status
Accepted

## Context
The roadmap's "Sprint 004 - AI Preview" item is named after the legacy `diviforge-ai-preview` page, which previews a parsed-and-validated Divi layout before import (score, checks, before/after frames, approve/discard). That page operates on the legacy page-centric job model (`page_id`, `parsed`, `validation`) described in [ADR-002](ADR-002-AI-Jobs-UI.md) — the new `AiJob` has none of that, by design, because it is a generic provider/cost log, not a Divi package pipeline.

Building a real Divi-layout preview for `AiJob` would require deciding whether the new AI engine should grow page/layout/validation concepts and start converging with (or replacing) the legacy AI Studio pipeline. That is a product direction decision, not an implementation detail, and is called out to Barry separately rather than guessed at here.

## Decision
Interpret Sprint 004 narrowly and safely for the new architecture: add a job detail view to `src/Admin/AiJobsScreen` (`?page=diviforge-ai-log&job=<id>`) showing the full prompt and response text for one job, with a "View" link from each row in the list. This previews the *raw AI request/response*, not a parsed Divi layout.

## Consequences
- Every job in the new AI Request Log can now be inspected in full, not just as a trimmed excerpt in the list.
- This does not replace or resemble the legacy AI Preview's layout/validation/import workflow. Nothing in `includes/` changed.

## Non-goals
- No Divi layout parsing, validation scoring, or import action for `AiJob`. If/when the new engine needs that, it is a separate decision (see the open question logged for Barry about Sprint 005 scope).
