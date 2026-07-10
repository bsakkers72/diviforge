# ADR-002: AI Jobs UI

## Status
Accepted

## Context
[ADR-001](ADR-001-AI-Foundation.md) left one open decision: what to do with the admin screen for AI jobs. The legacy `includes/admin/class-diviforge-admin.php` already has a page at `diviforge-ai-jobs` (menu label "AI Jobs"), but it is a page-centric workflow queue: each job carries a `page_id`, a parsed Divi layout, a validation score, and Review/Import/Discard actions, all stored in the `diviforge_ai_jobs` option. It is the live backend for the existing AI Studio → AI Preview → import pipeline.

The new `wp_diviforge_ai_jobs` table from ADR-001 is a flatter, provider-agnostic record: provider, model, prompt, response, status, tokens, cost, timestamps. It has no `page_id` and no parsed/validated package — it is a cost and usage log for whatever calls the AI module, not a page-editing workflow.

These are two different concerns that both happen to be called "jobs".

## Decision
Ship a new, separate admin page — `src/Admin/AiJobsScreen`, menu label "AI Request Log", slug `diviforge-ai-log` — that lists rows from the new table. Do not touch the legacy `diviforge-ai-jobs` page or its option-based storage.

## Consequences
- No risk to the working AI Studio / AI Preview / import pipeline — nothing in `includes/` changed.
- Two "AI Jobs"-sounding pages exist in the admin menu for now: "AI Jobs" (legacy, page workflow queue) and "AI Request Log" (new, provider/cost audit log). This is a temporary, deliberate overlap, not a duplicate — flagged here in case it is confusing enough to warrant a rename or merge later.
- The new page is likely empty until Sprint 003 registers a real provider and something calls `AiService::submit()`/`run()`.

## Non-goals
- No migration of the legacy AI Studio job data into the new table.
- No removal or renaming of the legacy `diviforge-ai-jobs` page.
