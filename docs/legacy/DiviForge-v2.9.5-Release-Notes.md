# DiviForge v2.9.5 Release Notes

## Focus
Export Extraction Quality.

## Added / Improved
- Page CSS export now reads from multiple known Divi/DiviForge meta sources, including `_et_pb_custom_css`, `_diviforge_page_css` and `_royal_mcp_page_custom_css`.
- `page.css` is now populated when CSS was stored by earlier import flows outside Divi's default meta key.
- CSS analysis now receives the complete CSS source and can fill selectors, rule counts, media query counts, declarations and design-token extraction.
- Divi rows without explicit `et_pb_column` shortcodes are normalized with an implicit column, so the export hierarchy is always `section > row > column > module`.
- Statistics now reflect inferred columns and a more complete builder tree.
- CSS `url(...)` references are detected during asset collection.
- Version Overview updated with v2.9.5.

## MVP scope
No new screens or major workflows. This release improves the quality and completeness of the existing AI export package.
