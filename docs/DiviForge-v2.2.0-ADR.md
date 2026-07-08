# DiviForge v2.2.0 - Architecture Decision Record

## ADR-001: CSS must be applied to Divi Page Custom CSS

**Status:** Accepted  
**Date:** 2026-07-06

### Context

DiviForge packages contain `page.css`. In v2.1.0 this file was saved only to `_diviforge_page_css`, a DiviForge-specific meta key. Divi does not render page-level CSS from that field.

### Decision

DiviForge v2.2.0 writes CSS to two meta keys:

- `_diviforge_page_css` as DiviForge's canonical copy.
- `_et_pb_custom_css` as Divi's page-level Custom CSS field.

### Consequences

- Imported pages are styled immediately after import.
- DiviForge can re-apply CSS later from its own stored copy.
- Future rollback/versioning features have a reliable CSS source.

---

## ADR-002: Introduce a dedicated CSS Importer

**Status:** Accepted  
**Date:** 2026-07-06

### Decision

CSS handling is moved to `DiviForge_CSS_Importer` instead of keeping it inside the generic importer.

### Consequences

- Cleaner import architecture.
- Easier future support for CSS validation, diffing and rollback.
- Easier debugging when Divi changes CSS storage behaviour.
