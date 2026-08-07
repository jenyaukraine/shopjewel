# Changelog

## 2.4.0 — 2026-08-06

- Added a supplier feed importer that groups the article-per-combination CSV into products, generates five-language catalogue copy from the structured columns, and downloads the supplier images into the shop.
- Priced every gold caratage and diamond quality combination exactly through one combined product option, because the supplier's showroom prices are not additively separable across the two axes.
- Made the import run in batches from the admin with live progress, resume, cancel and a per-article failure report, so a 10 MB feed with thousands of images never depends on a single request.
- Retired articles that disappear from a feed by disabling them instead of deleting, keeping their orders, reviews and URLs intact.
- Built the metal, fineness, origin, cut, quality and style filters from what the catalog actually contains, so unstocked options such as platinum and 950 gold cannot appear.
- Added destination-based DHL and DPD rate tiers with delivery windows for Ukraine, the European Union and the rest of the world.
- Added a shop logo setting used by the header and footer, falling back to the typographic wordmark.
- Removed the trailing full stops from storefront headings in all five languages.

## 2.3.0 — 2026-08-01

- Completed the 6 Moments brand migration, multilingual static content and browser-language selection.
- Added ten discounted jewelry products with complete five-language descriptions and specifications.
- Added configurable fixed prices for USD, EUR, CZK and UAH alongside automatic exchange-rate refresh.
- Added geo-zone-aware DHL/DPD quotes, an explicit promotion-code form and a localized order confirmation summary.
- Improved quiz ranking, currency-aware budgets, lab-grown navigation and storefront accessibility labels.

## 2.2.0 — 2026-07-31

- Added an accessible two-handle price slider synchronized with the minimum and maximum price fields.
- Added transactional multilingual CSV product import with automatic delimiter detection, row validation and create/update modes.
- Added one-click full catalog export and a spreadsheet-ready CSV template.
- Redesigned the module dashboard with a focused catalog workspace, live counts, drag-and-drop upload and a safe client-side preview.
- Preserved product options, extra images, discounts and relations when imported rows update existing products.
- Granted the primary OpenCart administrator access to the module workspace during install and container bootstrap.

## 2.1.2 — 2026-07-31

- Fixed Page Builder and quiz JSON validation under OpenCart request sanitization.
- Normalized JSON before saving so encoded entities are not persisted to settings.
- Added staged in-place ZIP updates with backups and automatic rollback, without uninstalling registered components.

## 2.1.1 — 2026-07-31

- Aligned the public product name, extension code, package name and documentation under NOVERAILE.
- Removed the development update-feed URL; reviewed updates are distributed through OpenCart Marketplace.
- Prevented saved AI and Stripe secrets from being rendered into the administrator form.
- Added newsletter request rate limiting and clarified AI/third-party service disclosures.

## 2.1.0 — 2026-07-31

- Added OpenCart 4.0.2.3 compatibility alongside OpenCart 4.1.x.
- Added a consistent polished storefront appearance.
- Added Page Builder, Mega Menu, progressive catalog filters and optional One Page Checkout.
- Added reviewed AI Product Description and AI SEO workflows through a configurable HTTPS provider.
- Added LCP image priority, lazy loading, stable dimensions and deferred below-fold rendering.
- Preserved routes already claimed by another extension and avoided OpenCart core-file replacement.

## 1.2.0 — 2026-07-31

- Prepared the OpenCart 4.1 package and release validation.
- Made module activation, shipping and payment helpers opt-in.
- Moved sample languages, currencies, products and articles to a separate demo installer.
- Added support for normal merchant products and configurable storefront identity.
- Added newsletter consent enforcement and gift-hint rate limiting.
