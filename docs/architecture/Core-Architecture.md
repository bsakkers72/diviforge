# DiviForge Core Architecture

## Decision

DiviForge remains a WordPress plugin. The internal codebase will be modernized by introducing a modular `src/` architecture next to the existing `includes/` implementation.

The old implementation stays active while new services are introduced gradually. This prevents large rewrites from breaking the plugin.

## Current layers

```text
WordPress
  ↓
DiviForge plugin entrypoint
  ↓
Core Bootstrap
  ↓
Container / Config / Logger / Events
  ↓
Legacy DiviForge classes + future modules
```

## Directories

```text
src/Core             Core bootstrap, lifecycle, config, logger, container
src/Admin            Future admin modules
src/AI               Future AI engine modules
src/Infrastructure   WordPress and external API integrations
src/Repository       Data access layer
src/Service          Application services
```

## Migration rule

No existing working functionality is removed during architecture migration. New modules are added behind the core bootstrap and connected one by one.

## Next step

Sprint 001 continues with the WordPress-native AI foundation:

- AI service registration
- AI jobs repository
- AI database migration
- AI provider contracts
- AI Jobs admin screen
