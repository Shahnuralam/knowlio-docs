=== Knowlio Docs ===
Contributors: shahnuralam025
Tags: documentation, knowledge base, docs, help center, faq
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A self-contained knowledge base and documentation plugin: manage docs in wp-admin and publish a professional docs site with the [knowlio] shortcode.

== Description ==

Knowlio Docs turns WordPress into a complete knowledge base / documentation site. You write and organise articles inside a clean, full-screen admin, and readers browse a fast, professional documentation front end published with a single shortcode.

It is fully self-contained: no page builder, no external service, and no dependency on any other plugin.

**Authoring**

* Documentation articles and categories, each managed from a dedicated full-screen admin that keeps you focused on writing.
* A full WordPress rich-text (TinyMCE) editor with media-library image upload.
* Paste directly from Microsoft Word or Google Docs — headings, lists, and images are preserved while unsafe markup is stripped.
* Content starter templates (feature guide, how-to, FAQ, release notes) to begin an article from a proven structure.
* Drafts and published states, manual ordering, and featured articles pinned to the landing page.

**Front end**

* One shortcode — `[knowlio]` — renders the whole knowledge base: landing page, category pages, single articles, and search.
* Selectable layout presets: sidebar, wide, boxed, and magazine.
* Automatic table of contents built from the article's headings, with scroll highlighting.
* Estimated reading time, view counts, and copy-to-clipboard buttons on code blocks.
* Responsive images: Knowlio Docs adds `srcset`, `sizes`, and lazy loading so a reader never downloads a full-resolution file just to see it scaled down in the reading column.

**Built to WordPress standards**

* Output is escaped, input is sanitised and unslashed, and every write action is protected by a nonce and a capability check.
* All database access uses prepared statements against the plugin's own tables.
* No anonymous request handlers: every route requires a logged-in user with the right capability.
* Your WordPress admin bar stays exactly where it is, so you are never trapped inside the plugin.
* Fully translatable, including right-to-left and non-Latin scripts.

== Installation ==

1. Upload the `knowlio-docs` folder to the `/wp-content/plugins/` directory, or install the plugin through the Plugins screen in WordPress.
2. Activate the plugin through the *Plugins* screen.
3. Open the **Knowlio Docs** menu, then add one or more categories and articles.
4. Create a WordPress page (e.g. "Docs") and add the `[knowlio]` shortcode to its content.
5. In **Knowlio Docs → Settings**, select that page as the knowledge base page and choose a layout preset.

== Frequently Asked Questions ==

= How do I display the knowledge base on the front end? =

Create a normal WordPress page and add the `[knowlio]` shortcode to it. The single shortcode renders every state — the landing page, category listings, individual articles, and search results — based on the URL.

= Can I paste content from Word or Google Docs? =

Yes. Copy from your document and paste into the article editor. Headings, lists, links, and images are kept; scripts, inline event handlers, and other unsafe markup are removed automatically.

= Does Knowlio Docs create custom database tables? =

Yes. It creates three tables (articles, categories, and settings) using the WordPress table prefix.

= What happens to my articles if I delete the plugin? =

Nothing, by default. Deleting Knowlio Docs leaves your articles and categories in the database, so uninstalling to troubleshoot a problem never costs you your documentation. If you do want a clean removal, switch on **Delete all data when the plugin is uninstalled** in **Knowlio Docs &rarr; Settings** before you delete the plugin.

= Does it work on multisite? =

Yes. Each site in the network gets its own tables and its own documentation, and uninstalling cleans up every site.

= Can I change how the documentation looks? =

Yes. In **Knowlio Docs → Settings** you can pick from four layout presets (sidebar, wide, boxed, magazine). The presets set the width and column structure of the front end without any theme changes.

= Do I need any other plugin? =

No. Knowlio Docs is self-contained and works with any standard WordPress theme.

== Screenshots ==

1. The knowledge base landing page with categories and featured articles.
2. A single article with its automatic table of contents.
3. The full-screen admin article editor with the rich-text toolbar and template picker.
4. Layout and page settings.

== Changelog ==

= 1.0.1 =
* Fixed the text domain so it matches the assigned wordpress.org plugin slug, as required by the plugin directory review.
* Regenerated the translation template.

= 1.0.0 =
* Initial release: articles and categories, rich-text editor with media upload and paste-from-Word support, `[knowlio]` shortcode front end, four layout presets, content starter templates, automatic table of contents, responsive images, search, reading time, and view counts.
* Roles and permissions mapping Knowlio Docs capabilities onto WordPress roles.
* Optional data removal on uninstall, off by default.
* Multisite aware: per-site tables, per-site content, network-wide cleanup on uninstall.

== Upgrade Notice ==

= 1.0.1 =
Internationalization fix for wordpress.org directory compliance; no functional changes.

= 1.0.0 =
Initial release.
