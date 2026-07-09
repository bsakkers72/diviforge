# ADR - DiviForge v2.3.0 Page Package Manager

## Status

Accepted

## Context

De importpagina bood alleen een generieke upload voor een nieuwe pagina. De gewenste workflow is dat een beheerder alle bestaande pagina's ziet en per pagina direct een nieuw DiviForge package kan uploaden. Daarnaast moet bovenaan de pagina een eenvoudige mogelijkheid blijven bestaan om een nieuwe pagina aan te maken vanuit een package.

## Besluit

De pagina `DiviForge > Import Package` wordt omgevormd naar een Page Package Manager.

- Bovenaan: nieuw package uploaden om nieuwe pagina te maken.
- Daaronder: tabel met bestaande pagina's.
- Per pagina: package uploaden, preview, edit, Divi en re-apply CSS.
- Bij bestaande pagina's blijven titel en status behouden.

## Consequenties

- De importpagina wordt de centrale beheerplek voor pagina-package updates.
- Gebruikers hoeven niet eerst een pagina-ID op te zoeken.
- De workflow sluit beter aan op iteratief bouwen met ChatGPT/Claude + DiviForge.
