# ADR — v2.7.0 Package History

## Decision
Store import snapshots as post meta on the WordPress page using `_diviforge_import_history`.

## Reason
For MVP, keeping history close to the page avoids custom database tables and keeps installation simple. Each page owns its package timeline.

## Consequences
- Simple and portable.
- Easy to back up with WordPress data.
- Snapshot size can grow because content and CSS are stored. For MVP this is limited to the latest 20 snapshots per page.

## Future option
Move history to a custom table if DiviForge becomes multi-site, commercial, or needs advanced querying.
