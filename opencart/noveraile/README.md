# NOVERAILE Commerce Suite for OpenCart

Installable storefront extension for OpenCart 4.0.2.3 and 4.1.x. It uses OpenCart events and does not replace core files. Version 2.6.1 works with an existing merchant catalog, includes transactional multilingual CSV import/export, catalog-wide price coefficients, fixed market price books, and keeps the jewelry sample catalog optional.

## Product import and export

Open **Extensions → Modules → NOVERAILE Commerce Suite → Catalog data**. Use **Export all products** to create a UTF-8 CSV backup, edit it in Excel, Numbers or Google Sheets, then drop it into the import panel. The importer recognizes comma, semicolon and tab delimiters, groups translations by `product_id` or `model`, validates every row before writing, and rolls the whole operation back if any row is invalid. Updating a product preserves its options, additional images, discounts and relations.

## Installation

1. Back up the OpenCart files and database.
2. In **Extensions → Installer**, upload `noveraile.ocmod.zip` and click **Install**.
3. Open **Extensions → Extensions → Modules**, find **NOVERAILE Storefront Suite**, and click **Install**.
4. Open the module settings, set the storefront brand and contact details, enable the module, and save.
5. Optional: on a clean or staging store, click **Install demo content** to add the packaged sample catalog.
6. Refresh OpenCart caches from the maintenance/developer settings if an older theme is still visible.

## Updating

Starting with version 2.1.2, open the NOVERAILE module settings and use **Safe in-place update** to upload a newer `noveraile.ocmod.zip`. Do not remove the package from OpenCart's Extension Installer. The updater validates and stages the archive, preserves the registered module/payment/shipping/total components and settings, saves a backup under OpenCart storage, and restores the previous files if replacement fails.

The module-install action registers the storefront events and creates the newsletter and gift-hint tables. The storefront, Stripe, DHL, DPD and set-total helpers are disabled until the merchant explicitly enables them. Normal installation does not delete, replace or seed catalog records.

The separate demo action creates five languages (English, German, Czech, Russian and Ukrainian), four currencies (USD, EUR, CZK and UAH), and the sample categories, products and articles. Use it only on a new or staging store; products with the reserved `NVR-` demo model prefix can be replaced during demo upgrades.

## Production setup

- Enter a Stripe secret key and webhook signing secret, point the Stripe webhook to the URL displayed in module settings, then enable Stripe. Payment is intentionally disabled until credentials are supplied.
- Set the DHL and DPD prices and enable only the services used by the store. These packaged shipping methods are configurable flat tariffs; carrier API quotations, label printing and tracking synchronization require separate carrier modules/contracts.
- If demo content was imported, replace its product data, prices, images and stock with the final catalog.
- Set real company/contact data and have the privacy, terms, returns, warranty and imprint copy approved for every sales market.
- Configure mail delivery, taxes, geo zones, order statuses and OpenCart cron jobs.
- Review currency conversion rates. OpenCart converts base prices; fixed market-specific price lists need a dedicated pricing extension or explicit per-currency catalog data.
- The branded storefront copy is translated in all five languages. Native OpenCart account/checkout strings for the added non-English locales use the included English fallback until official/localized language packs are installed.

## Included features

- A consistent light storefront presentation.
- Core Web Vitals foundations: priority LCP media, lazy below-fold images, stable media sizing and deferred long-page rendering.
- Configurable Blog route and optional native OpenCart/MegaMenu output; canonical links, analytics and registered module assets remain intact.
- Visual Page Builder for reordering and disabling homepage sections, plus editable hero copy.
- Catalog-powered desktop Mega Menu with a configurable featured link.
- Progressive AJAX filters with facets, price/carat ranges, sorting, pagination, browser history and a normal-GET fallback.
- Optional responsive One Page Checkout that keeps OpenCart delivery, payment and confirmation fragments together.
- Server-side AI Studio for product descriptions and SEO metadata, with configurable HTTPS endpoint/model and mandatory human review before applying a draft.
- Responsive storefront and product/catalog templates for normal OpenCart catalog records.
- Story, diamonds, quiz, FAQ, shipping, privacy, terms and imprint pages.
- Starter catalog organized around the six moments, including lab-grown/natural tags.
- Newsletter capture and “send a hint” product form.
- Stripe-hosted Checkout Session integration with signed webhook handling.
- DHL and DPD flat-rate shipping extensions.
- Admin settings in a single NOVERAILE module screen.

The release checks target clean OpenCart 4.0.2.3 and 4.1.0.3 installations. OpenCart 3.x is not supported; a core view already replaced by another extension is preserved.

## External services and data

Stripe Checkout, DHL/DPD helpers and AI tools are optional and disabled by default. Their accounts, contracts, fees and API availability are not included. When an administrator requests AI content, the selected product fields are sent to the HTTPS endpoint and model configured by that administrator. Generated drafts are not published until an administrator reviews and applies them.

API credentials are stored in OpenCart settings. Existing AI and Stripe secrets are never rendered back into the module form; leaving a secret field blank keeps its saved value.
