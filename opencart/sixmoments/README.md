# 6MOMENTS Storefront Suite for OpenCart

Installable extension package for OpenCart 4.1.x. It does not replace OpenCart core files.

## Installation

1. Back up the OpenCart files and database.
2. In **Extensions → Installer**, upload `sixmoments.ocmod.zip` and click **Install**.
3. Open **Extensions → Extensions → Modules**, find **6MOMENTS Storefront Suite**, and click **Install**.
4. Open the module settings, review the contact details and save.
5. Refresh OpenCart caches from the maintenance/developer settings if an older theme is still visible.

The module-install action registers the storefront events, creates the five languages (English, German, Czech, Russian and Ukrainian), four currencies (USD, EUR, CZK and UAH), the starter categories/products, newsletter and gift-hint tables, and the packaged payment/shipping extensions.

## Production setup

- Enter a Stripe secret key and webhook signing secret, point the Stripe webhook to the URL displayed in module settings, then enable Stripe. Payment is intentionally disabled until credentials are supplied.
- Set the DHL and DPD prices and enable only the services used by the store. These packaged shipping methods are configurable flat tariffs; carrier API quotations, label printing and tracking synchronization require separate carrier modules/contracts.
- Replace the starter product data, prices, images and stock with the final catalog.
- Set real company/contact data and have the privacy, terms, returns, warranty and imprint copy approved for every sales market.
- Configure mail delivery, taxes, geo zones, order statuses and OpenCart cron jobs.
- Review currency conversion rates. OpenCart converts base prices; fixed market-specific price lists need a dedicated pricing extension or explicit per-currency catalog data.
- The branded storefront copy is translated in all five languages. Native OpenCart account/checkout strings for the added non-English locales use the included English fallback until official/localized language packs are installed.

## Included features

- Responsive 6MOMENTS storefront and product/catalog templates.
- Story, diamonds, quiz, FAQ, shipping, privacy, terms and imprint pages.
- Starter catalog organized around the six moments, including lab-grown/natural tags.
- Newsletter capture and “send a hint” product form.
- Stripe-hosted Checkout Session integration with signed webhook handling.
- DHL and DPD flat-rate shipping extensions.
- Admin settings in a single 6MOMENTS module screen.

Tested on a clean OpenCart 4.1.0.3 installation with PHP 8.3 and MariaDB.
