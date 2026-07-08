# DiviForge v2.8.0 Documentation

## Sprint
Package History refinement.

## Nieuw in v2.8.0
DiviForge slaat bij iedere package-import automatisch een release note op in de importhistorie. Barry hoeft niets handmatig in te vullen. De samenvatting wordt opgebouwd uit de importactie en package-inhoud.

## Automatische samenvatting
De release note bevat waar beschikbaar:

- importtype: nieuwe pagina of update van bestaande pagina;
- package titel en versie;
- categorie uit manifest.json;
- aantal secties;
- aantal modules;
- aantal afbeeldingen;
- aantal regels page.css;
- bevestiging of Page Custom CSS is toegepast;
- optionele package-highlights uit manifest.json via `features`, `changes` of `highlights`.

## Opslag
De release note wordt opgeslagen per history entry in post meta `_diviforge_import_history` als veld `release_note`.

## Doel voor MVP
Deze versie maakt Package History bruikbaarder voor dagelijks gebruik. Het versieoverzicht toont niet alleen technische metadata, maar ook in gewone taal wat een import toevoegde of wijzigde.
