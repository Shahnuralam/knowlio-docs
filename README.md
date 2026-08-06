# Knowlio Docs

A self-contained **knowledge base / documentation** plugin for WordPress. Write and organise documentation in a clean, full-screen admin, then publish a fast, professional docs site on the front end with a single shortcode.

No page builder, no external service, and no dependency on any other plugin.

## Features

**Authoring**
- Documentation **articles** and **categories**, managed from a dedicated full-screen admin.
- Full WordPress **rich-text (TinyMCE) editor** with media-library image upload.
- **Paste from Microsoft Word / Google Docs** — headings, lists, and images are preserved while unsafe markup is stripped.
- **Content starter templates** (feature guide, how-to, FAQ, release notes).
- Draft / published states, manual ordering, and featured articles pinned to the landing page.

**Front end**
- One shortcode — `[knowlio]` — renders the whole knowledge base: landing page, category pages, single articles, and search.
- Selectable **layout presets**: sidebar, wide, boxed, magazine.
- Automatic **table of contents** built from article headings, with scroll highlighting.
- Estimated reading time, view counts, and copy-to-clipboard buttons on code blocks.
- **Responsive images** (`srcset` / `sizes` / lazy loading) so readers never download a full-resolution file to view it scaled down.

**Standards**
- Output escaped; input sanitised and unslashed; every write protected by a nonce and a capability check.
- All database access uses prepared statements against the plugin's own tables.
- Fully translatable, including right-to-left and non-Latin scripts.

## Requirements

- WordPress **5.8+**
- PHP **7.4+**

## Installation

1. Copy the `knowlio-docs` folder into `wp-content/plugins/` (or upload a zip via **Plugins → Add New**).
2. Activate **Knowlio Docs** from the *Plugins* screen.
3. Under the **Knowlio Docs** menu, add one or more categories and articles.
4. Create a WordPress page (e.g. "Docs") and add the `[knowlio]` shortcode.
5. In **Knowlio Docs → Settings**, select that page as the knowledge base page and pick a layout preset.

## Usage

Put the shortcode on any page:

```
[knowlio]
```

The single shortcode renders every state — landing page, category listing, single article, and search — based on the URL query (`knowlio_cat`, `knowlio_article`, `knowlio_s`).

## Architecture

Knowlio Docs uses a small self-contained MVC framework inside the plugin:

- **String router** — `controller__action` resolves to `Knowlio{Name}Controller`, reached via `admin.php?page=knowlio-docs` and `admin-ajax`/`admin-post` (authenticated only).
- **Active-Record ORM** (`KnowlioModel`) over `$wpdb` — chainable `where/join/order_by`, prepared values, mass-assignment and persistence whitelists, declarative validations, lifecycle callbacks.
- **View/layout renderer** with dual HTML/JSON output.
- Custom tables created via `dbDelta`, versioned by `KNOWLIO_DB_VERSION`.

## License

[GPLv3 or later](LICENSE) © Shahnur Alam
