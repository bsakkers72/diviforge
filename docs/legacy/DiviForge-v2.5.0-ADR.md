# ADR — Package Inspector vóór Preview

## Context
De roadmap werkt toe naar een eerste MVP. Voor een betrouwbare importworkflow moet DiviForge eerst packages kunnen beoordelen voordat ze worden geïmporteerd.

## Besluit
In v2.5.0 wordt Package Inspector gebouwd vóór live preview en vóór package history.

## Reden
- Lage technische complexiteit.
- Hoge waarde voor de gebruiker.
- Minder kans op foutieve imports.
- Goede basis voor latere preview- en historyfunctionaliteit.

## Consequenties
- De inspector importeert bewust nog niets.
- De readiness score is indicatief, niet blokkerend.
- Preview blijft op de roadmap voor v2.6.
