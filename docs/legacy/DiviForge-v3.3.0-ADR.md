# ADR - DiviForge v3.3.0 AI Chat

## Decision
DiviForge stores AI Chat conversations per page, not globally.

## Context
AI improvements are iterative. A homepage, services page and contact page can each have different goals, history, constraints and design decisions. A global chat would make that context harder to manage.

## Consequences
- Each page receives a persistent thread.
- Messages are stored locally in WordPress options.
- Chat output is routed to AI Preview instead of directly importing.
- The architecture remains compatible with future page-specific AI memory.

## Status
Accepted.
