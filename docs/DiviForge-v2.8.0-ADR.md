# ADR - DiviForge v2.8.0

## Besluit
Import release notes worden automatisch gegenereerd door DiviForge en niet handmatig ingevoerd door de gebruiker.

## Context
Barry wil in de importhistorie kunnen zien welke functionaliteit een nieuwe versie biedt, zonder zelf per import een tekst te hoeven schrijven.

## Gevolg
De importer maakt bij iedere import een tekstuele samenvatting op basis van package metadata, layout-statistieken, CSS-aanwezigheid en eventuele manifest-highlights. Deze tekst wordt opgeslagen in de history snapshot.
