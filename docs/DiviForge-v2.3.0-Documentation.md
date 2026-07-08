# DiviForge v2.3.0 Documentation

## Doel van deze release

DiviForge v2.3.0 verandert de pagina `DiviForge > Import Package` in een **Page Package Manager**. De beheerder ziet nu alle bestaande WordPress-pagina's en kan per pagina direct een nieuw DiviForge package uploaden. Bovenaan blijft een aparte workflow beschikbaar om een compleet nieuwe pagina aan te maken op basis van een package.

## Nieuwe workflow

### Nieuwe pagina aanmaken

1. Ga naar `DiviForge > Import Package`.
2. Gebruik het blok **Create a new page from package**.
3. Upload een `.zip` of `.dfg` package.
4. DiviForge maakt een nieuwe conceptpagina aan.
5. De layout wordt geïmporteerd.
6. `page.css` wordt toegepast op Divi Page Custom CSS.
7. Na import verschijnt een knop **View result / preview**.

### Bestaande pagina bijwerken

1. Ga naar `DiviForge > Import Package`.
2. Zoek de bestaande pagina in de paginatabel.
3. Upload een package in de rij van die pagina.
4. DiviForge vervangt de pagina-inhoud door de layout uit het package.
5. De bestaande paginatitel en status blijven behouden.
6. `page.css` wordt opnieuw toegepast op de Divi Page Custom CSS van die pagina.
7. De beheerder kan daarna direct naar **Preview**, **Edit** of **Divi**.

## Functionele wijzigingen

- Alle pagina's worden getoond op de importpagina.
- Per pagina is een eigen uploadformulier beschikbaar.
- Bovenaan staat een formulier om een nieuwe pagina te maken vanuit een package.
- Na import verschijnt een directe preview/resultaatknop.
- Voor conceptpagina's wordt gebruikgemaakt van de WordPress preview-link.
- Voor bestaande pagina's blijft titel en publicatiestatus behouden.
- De package registry metadata blijft per pagina opgeslagen.
- De CSS-import uit v2.2.0 blijft actief via `_et_pb_custom_css` en `_diviforge_page_css`.

## Technische wijzigingen

### Admin interface

Bestand:

`includes/admin/class-diviforge-admin.php`

Nieuwe onderdelen:

- `render_pages_table()`
- `get_page_preview_url()`
- uitgebreid `import_package()` scherm
- uitgebreid `handle_import()` met `target_page_id`

### Importer

Bestand:

`includes/class-diviforge-importer.php`

De importer accepteerde al een optionele `$target_page_id`. In v2.3.0 wordt deze optie actief gebruikt vanuit de admin interface. Bij bestaande pagina's worden alleen `post_content` en metadata bijgewerkt. De bestaande `post_title`, `post_status` en `post_type` blijven ongemoeid.

### CSS

Bestand:

`assets/css/admin.css`

Nieuwe styling voor:

- Page Package Manager
- uploadpanelen
- paginatabel
- responsive weergave

## Datamodel / metadata

Per geïmporteerde pagina worden onder andere deze velden bijgewerkt:

- `_et_pb_use_builder`
- `_et_pb_custom_css`
- `_diviforge_page_css`
- `_diviforge_version`
- `_diviforge_package_hash`
- `_diviforge_package_imported_at`
- `_diviforge_package_title`
- `_diviforge_package_filename`
- `_diviforge_package_manifest`
- `_diviforge_package_version`
- `_diviforge_package_author`
- `_diviforge_css_status`
- `_diviforge_css_applied_at`
- `_diviforge_css_hash`

## Bestandsstructuur

```text
diviforge/
├── diviforge.php
├── includes/
│   ├── admin/
│   │   └── class-diviforge-admin.php
│   ├── class-diviforge.php
│   ├── class-diviforge-package.php
│   ├── class-diviforge-importer.php
│   └── class-diviforge-css-importer.php
├── assets/
│   ├── css/admin.css
│   └── js/admin.js
├── docs/
└── examples/
```

## Roadmap na v2.3.0

### v2.4.0

- Package history per pagina.
- Rollback naar vorige package.
- Alleen CSS opnieuw uploaden vanuit package.
- Verschillen tonen tussen huidig package en nieuw package.

### v2.5.0

- Asset import voor afbeeldingen.
- Automatische koppeling van lokale package-afbeeldingen aan Divi image modules.
- Package preview thumbnails.

### v3.0.0

- Prompt Studio.
- Package Studio.
- Preview Engine.
- Component Library.
