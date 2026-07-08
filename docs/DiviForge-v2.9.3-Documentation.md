# DiviForge v2.9.3 Documentation

## Release theme

**AI Fidelity Export**. This release improves the DiviForge AI Roundtrip export so existing Divi pages are exported with richer, more precise context for ChatGPT, Claude or another AI assistant.

## Main changes

### 1. Builder Tree v2

`builder-tree.json` is now treated as the canonical AI-friendly page structure. It contains:

- page node
- sections
- rows
- columns
- modules
- parent/child relationships
- positions
- Divi tags and module types
- raw shortcode opening tag
- raw attribute text
- all parsed Divi shortcode attributes
- normalized settings groups
- responsive variants
- classes
- CSS binding hints
- detected asset references
- module content excerpts where available

### 2. Richer Divi settings export

Attributes are normalized into categories:

- layout
- spacing
- sizing
- background
- typography
- border
- shadow
- effects
- animation
- visibility
- custom CSS
- responsive
- module-specific
- other

The raw attributes are still preserved so import fidelity is not lost.

### 3. CSS analysis

A new file is exported:

```text
css/analysis.json
```

This contains:

- selectors
- CSS declarations
- classes used in selectors
- matched builder nodes where classes can be mapped back to Divi modules
- raw CSS rule text

`page.css` remains the full source of truth for page-level CSS.

### 4. Asset manifest

A new file is exported:

```text
assets/assets.json
```

DiviForge detects image/video references in shortcode attributes, content and CSS. Local WordPress uploads are copied into `assets/images/` where possible. Metadata includes URL, filename, attachment ID, alt text, caption, dimensions, MIME type, filesize and hash where available.

### 5. Richer design tokens

`design.json` now detects more page-level design signals:

- colors
- fonts
- radius values
- spacing values
- shadows
- gradients
- transitions
- animations
- breakpoints

These are provided as AI guidance for preserving or deliberately evolving the design language.

## Package files

A v2.9.3 export can contain:

```text
manifest.json
layout.json
builder-tree.json
page-meta.json
theme.json
page.css
css/variables.json
css/analysis.json
design.json
divi-context.json
class-map.json
AI_REQUEST.json
page.html
scripts/scripts.json
scripts/page-scripts.js
assets/assets.json
assets/images/*
assets/README.md
preview/README.md
chatgpt-instructions.md
prompt.txt
README.md
```

## MVP relevance

This release keeps the MVP focused: no new large workflow is added. It improves the existing AI export so the roundtrip becomes more reliable.
