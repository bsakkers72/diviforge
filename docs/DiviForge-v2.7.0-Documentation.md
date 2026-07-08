# DiviForge v2.7.0 Documentation

## Release theme
Sprint 7 introduces **Package History**: a focused MVP feature for tracking package imports per page and restoring earlier import snapshots when needed.

## Added
- New Studio navigation item: **History**.
- New admin screen: `DiviForge > Package History`.
- Per DiviForge page import timeline.
- Snapshot data saved on every new import from v2.7.0 onward:
  - import mode: new/update
  - package title
  - package version
  - package filename
  - package hash
  - import datetime
  - package stats: sections, modules, images, CSS lines
  - generated Divi content
  - page.css content
- Restore action per snapshot.
- History shortcut button on page cards.

## Restore behavior
Restoring a snapshot replaces the selected page content and stored Divi page CSS with the content and CSS from that snapshot. The page remains the same WordPress page; only its imported package output is restored.

## Scope control
This release stays within the original MVP roadmap. It does not add component importing, marketplace features, AI generation, or external storage.

## Known limitation
Snapshots start from v2.7.0. Pages imported with older DiviForge versions may show current package metadata, but will not have a complete restore timeline until they are imported again.
