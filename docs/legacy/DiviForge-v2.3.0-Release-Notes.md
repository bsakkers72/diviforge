# DiviForge v2.3.0 Release Notes

## Nieuw

- Importpagina vernieuwd naar **Page Package Manager**.
- Alle WordPress-pagina's worden zichtbaar op `DiviForge > Import Package`.
- Per pagina kan direct een nieuw package worden geüpload.
- Bovenaan kan een nieuwe pagina worden aangemaakt vanuit een package.
- Na import/update verschijnt een directe knop **View result / preview**.
- Conceptpagina's openen via de WordPress preview-link.

## Verbeterd

- Bij upload naar een bestaande pagina blijven paginatitel en publicatiestatus behouden.
- De bestaande CSS-import naar Divi Page Custom CSS blijft actief.
- Admin-interface heeft betere uploadpanelen en responsive tabelweergave.

## Technisch

- `handle_import()` ondersteunt nu `target_page_id` vanuit de admin UI.
- `DiviForge_Importer::import_upload()` werkt veiliger met bestaande pagina's.
- Nieuwe helper voor preview URLs.
