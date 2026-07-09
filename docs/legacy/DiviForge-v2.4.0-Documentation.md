# DiviForge v2.4.0 Documentation

## Release theme

Sprint 4 starts the transition from a classic WordPress admin import page to a professional **AI Design Studio for Divi**.

The focus of v2.4.0 is UX and workflow: a cleaner dashboard, modern navigation, page cards, package overview, search, filters and a more pleasant daily workflow.

## What changed

### 1. Studio dashboard

The dashboard now shows:

- total WordPress pages
- DiviForge-managed pages
- imports today
- current sprint indicator
- quick create form
- recent imported pages
- sprint roadmap strip

### 2. Pages view

A new `DiviForge > Pages` screen introduces card-based page management. Each card includes:

- page thumbnail or generated initials
- page status
- last package metadata
- last update date
- import date
- upload package form
- Preview button
- Open in Divi button
- Edit button
- Re-apply CSS button when available
- Delete button

The existing URL `DiviForge > Import Package` now opens the same Pages workflow so existing usage remains intact.

### 3. Package library shell

A first `Packages` screen has been added. It reads package metadata from pages that were imported or updated through DiviForge.

This prepares the foundation for future package history, tags, favorites and rollback.

### 4. Studio navigation

DiviForge now uses a wider product-style navigation:

- Dashboard
- Pages
- Packages
- Components
- Templates
- Inspector
- Settings

### 5. Search and filters

The Pages screen includes:

- page search
- filter by all pages
- published pages
- drafts
- DiviForge-managed pages

## Technical notes

### Existing import engine preserved

The existing import engine remains in use:

- `DiviForge_Importer::import_upload()`
- `DiviForge_CSS_Importer::apply_to_page()`
- `DiviForge_Package::inspect_upload()`

This release changes the user workflow without replacing the underlying package import logic.

### Metadata used

The Pages and Packages views use existing metadata:

- `_diviforge_version`
- `_diviforge_package_hash`
- `_diviforge_package_imported_at`
- `_diviforge_package_title`
- `_diviforge_package_filename`
- `_diviforge_package_version`
- `_diviforge_package_author`
- `_diviforge_css_status`

## Roadmap after v2.4.0

### v2.4.1 — Package Cards & Thumbnails

- store package thumbnails
- show package preview images
- add visual package cards
- add tags and favorites

### v2.4.2 — Import Wizard

- upload step
- package inspection step
- metadata confirmation
- preview step
- import confirmation

### v2.4.3 — Package History

- store every import as history item
- compare imports
- restore previous package
- re-apply layout or CSS separately

### v2.5 — Component Library

- import Hero components
- import CTA components
- import FAQ components
- import pricing blocks
- prepare component replacement workflow

## Installation

Upload `diviforge-v2.4.0-plugin.zip` through WordPress:

Plugins > Add New > Upload Plugin
