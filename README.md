# DiviForge

Create of WP plugin DiviForge.

## Development workflow

- `main` contains stable releases.
- `develop` contains the next release.
- `feature/*` branches contain one focused feature or sprint.
- Version numbers follow semver and actually increment (`diviforge.php` header `Version:` and the `DIVIFORGE_VERSION` constant): bump the patch number for bug fixes, minor for new backward-compatible features/sprints, major for breaking changes. A build date and time is stamped on top for exact build identification, format `<semver>-YYYYMMDD-HHMM`, e.g. `3.5.1-dev-20260711-0900`. This makes it possible both to see what kind of change shipped and to tell exactly which build is installed when testing multiple ZIPs.

## Roadmap 3.0

DiviForge is evolving into a WordPress-native AI Design Studio for Divi.

Current architecture work:

- Epic 000: Core Architecture
- Sprint 001: AI Foundation
- Sprint 002: AI Jobs
- Sprint 003: OpenAI Provider
- Sprint 004: AI Preview
- Sprint 005: Package Builder
