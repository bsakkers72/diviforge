# DiviForge v2.5.0 — Package Inspector MVP

## Doel
DiviForge v2.5.0 start Sprint 5 uit de MVP-roadmap: **Package Inspector**. Het doel is om een package veilig te kunnen controleren voordat het wordt geïmporteerd in een nieuwe of bestaande Divi-pagina.

## Scope
Deze release voegt géén grote nieuwe workflow toe buiten de roadmap. De bestaande importfunctionaliteit blijft gelijk. De focus ligt op inspectie, validatie en duidelijke informatie vóór import.

## Nieuwe functionaliteit

### Package Inspector
Te vinden via:

`DiviForge > Package Inspector`

De gebruiker uploadt een `.zip` of `.dfg` package. DiviForge voert daarna een veilige analyse uit zonder de pagina aan te maken of te wijzigen.

De inspector toont:

- Package titel en bestandsnaam
- Import readiness score
- Aanwezigheid van `manifest.json`
- Aanwezigheid van `layout.json`
- Aanwezigheid van `page.css`
- Divi compatibility status
- Aantal secties, rijen, kolommen en modules
- Aantal afbeeldingen
- Aantal CSS-regels
- Manifest metadata
- Module types
- Sectielijst
- Assetlijst
- Waarschuwingen

## Technische werking

De bestaande klasse `DiviForge_Package` is uitgebreid met extra inspectiedata:

- `readiness`
- `manifest_summary`
- `module_types`
- `css_lines`
- `css_bytes`
- `package_bytes`

De inspectie loopt via de bestaande AJAX-action:

`wp_ajax_diviforge_inspect_package`

Er wordt bewust nog niets geïmporteerd. De inspector is een veilige preflight-stap.

## Readiness score

De readiness score is een eenvoudige MVP-score van 0 tot 100.

Puntenaftrek vindt plaats bij:

- ontbrekende of ongeldige `manifest.json`
- ontbrekende of ongeldige `layout.json`
- ontbrekende `sections` array
- ontbrekende `page.css`
- package dat niet als Divi-package is gemarkeerd

## MVP-grens

Niet inbegrepen in deze release:

- echte visuele live preview
- import vanuit inspector-scherm
- package history
- rollback
- component-import

Deze onderdelen blijven op de roadmap staan voor latere sprints.
