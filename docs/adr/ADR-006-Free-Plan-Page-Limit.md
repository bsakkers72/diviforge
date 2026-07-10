# ADR-006: Free plan page limit

## Status
Accepted

## Context
Barry's plan is to list DiviForge in the WordPress plugin directory as a freemium plugin: free and fully functional up to a point, with a paid license required to go further. He set the free limit at 3 pages. The licensing backend itself (Freemius vs. a self-hosted store) is not decided yet, but the page-limit gate does not need to wait for that decision.

## Decision
Add `src/Licensing`:

- `LicenseServiceInterface` / `LicenseStatus` — the contract and status constants (`free`, `active`).
- `OptionBackedLicenseService` — a placeholder implementation backed by a single WP option (`diviforge_license_status`). This is explicitly temporary; whichever licensing backend Barry picks will replace it, without any other class needing to change.
- `PlanLimits` — `FREE_PAGE_LIMIT = 3`.
- `PageLimitGuard` — counts existing DiviForge pages (pages carrying `_diviforge_package_imported_at` or `_diviforge_version` postmeta) and decides whether another one can be created, given the license status.

Wired into the container from `ServiceRegistry`. The only legacy touch is `handle_import()` in `includes/admin/class-diviforge-admin.php`: when importing to a **new** page (not updating an existing one), it now asks `PageLimitGuard::canCreateAnotherPage()` first and redirects with a notice if the free limit is reached. `render_import_notices()` got one new `if` block for that notice.

## Consequences
- The free tier is genuinely functional (import, export, AI Studio all work) up to 3 pages, which is what WordPress.org's guidelines expect from a freemium listing — not crippleware.
- Swapping in a real licensing backend later (e.g. Freemius) only means writing a new `LicenseServiceInterface` implementation and changing one line in `LicensingServiceProvider`. `PageLimitGuard` and the legacy call site do not change.
- Only page **creation** is gated. Updating an existing DiviForge page, or any AI Studio/AI Preview action on an existing page, is unaffected.

## Non-goals
- No actual license purchase/validation flow yet — that depends on the Freemius-vs-self-hosted decision, still open.
- No WordPress.org submission checklist (readme.txt, GPL headers, translations) — separate, later work.
