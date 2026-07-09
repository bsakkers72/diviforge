# ADR-000: Establish Core Architecture

## Status
Accepted

## Context
DiviForge started as a classic WordPress plugin with most behavior inside legacy `includes/` classes. Roadmap 3.0 requires AI jobs, providers, package builders, validators, preview workflows and future marketplace features. Keeping all logic in a small set of legacy classes would make the product difficult to maintain.

## Decision
Introduce a modern modular `src/` architecture while keeping DiviForge a WordPress plugin.

The first architecture layer adds:

- PSR-4-like autoloader
- Dependency container
- Configuration service
- Logger
- Event dispatcher
- Lifecycle hook service
- Bootstrap service

## Consequences
- Future modules can be added without overloading legacy admin classes.
- Existing plugin behavior remains available through the legacy `DiviForge::instance()` call.
- AI Engine work can now be implemented inside `src/AI` while still using WordPress hooks, `$wpdb`, capabilities and admin pages.

## Non-goals
- No standalone AI service.
- No CLI-only product.
- No rewrite of existing importer/exporter logic in this step.
