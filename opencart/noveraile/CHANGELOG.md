# Changelog

## 2.6.1 — 2026-09-01
- Restored total carat weight and stone count on catalog cards imported by the standalone CSV importer, without confusing 14K/18K gold caratage with gemstone weight.
- Removed the “Made to order” label from product cards while retaining the positive in-stock status.
- Redirected legacy rings, earrings, necklaces and bracelets URLs to the filtered catalog instead of rendering a 404 page.
- Rebuilt the newsletter signup as a balanced light panel with responsive input and offer layouts.

## 2.6.0 — 2026-08-31
- Added a merchant-friendly catalog price coefficient that updates imported prices, option surcharges, filters, cart totals and fixed market prices.
- Restored total carat weight and stone count for the existing supplier catalog without requiring a destructive re-import; future imports persist both as structured values.
- Routed category cards, menu entries and product breadcrumbs through the stable filtered catalog to eliminate broken category SEO links.
- Added previous/next controls and keyboard image browsing to the full-screen white product gallery.

## 2.5.2 — 2026-08-24
- Switched the storefront, header, catalog, account and checkout base surfaces to white with subtle product-card borders.
- Kept product photography on a white canvas, displayed the complete image and retained gallery navigation.
- Removed supplier articles from customer-facing product names while preserving model and SKU fields.
- Aligned journal cards into a consistent grid.

## 2.5.1 — 2026-08-11
- Applying a refinement stays on the catalog. The filter dropped the route from the address it requested, and OpenCart answers a request without one with the store front page — which `pushState` then wrote into the address bar.
- The quiz tells rings apart from earrings: choices were matched as substrings, and "earrings" contains "ring".
- The homepage offers wedding rings beside the four supplier categories, and its tile row no longer reserves a fifth cell the catalog cannot fill.
- Headings, kickers and field legends carry no trailing full stop in any of the five languages.

## 2.4.0 — 2026-08-06
- Imported the supplier assortment: one product per articul, with every gold caratage and diamond quality priced exactly through a single combined option.
- Built the metal, fineness, origin, cut, quality and style filters from what the catalog actually contains, so options nobody stocks disappear on their own.
- Lent that filter panel to the category, search and special listings, which previously offered no refinement at all.
- Rebuilt product breadcrumbs from the product's own category, so a piece reached from search or a shared link still leads back to its listing.
- Gave embedded video its own gallery slide instead of letting it overflow the product columns.
- Translated the storefront strings that still fell back to English across all five languages.
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
