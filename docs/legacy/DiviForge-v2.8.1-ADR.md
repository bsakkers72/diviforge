# ADR v2.8.1 - Page snapshot strategy

## Besluit

Voor page-card headers gebruiken we een pragmatische snapshot-strategie: featured image eerst, daarna mShots voor gepubliceerde pagina's, daarna fallback.

## Reden

Echte server-side screenshots vereisen headless browser-infrastructuur en maken de MVP onnodig complex. Deze aanpak levert snel visuele waarde zonder zware dependencies.
