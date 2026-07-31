# Changelog

## 2.2.0 — 2026-07-31

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
- Added Auto, Light and Dark modes with a persistent visitor switch.
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
