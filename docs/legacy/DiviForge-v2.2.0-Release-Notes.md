# DiviForge v2.2.0 - Release Notes

## Main improvement

`page.css` from an uploaded DiviForge package is now automatically applied to the imported Divi page.

## Fixed

In v2.1.0, CSS was only stored in `_diviforge_page_css`. Divi does not use that field for Page Custom CSS. Version 2.2.0 now also writes the CSS to `_et_pb_custom_css`.

## Added

- New CSS Importer class.
- CSS metadata registry.
- Import status panel.
- Edit Page button.
- Open in Divi button.
- Re-apply CSS button.
- Divi static CSS cache clearing where supported.

## Upgrade note

After installing v2.2.0, newly imported packages will automatically place `page.css` in the page-level Divi Custom CSS field.

For pages imported with v2.1.0, import the package again or manually copy the stored `_diviforge_page_css` value to the page CSS field.
