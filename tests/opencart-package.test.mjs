import assert from "node:assert/strict";
import { readFile, readdir } from "node:fs/promises";
import path from "node:path";
import test from "node:test";

const root = path.resolve("opencart/noveraile");

test("OpenCart 4 package has valid marketplace metadata and entry points", async () => {
  const manifest = JSON.parse(await readFile(path.join(root, "install.json"), "utf8"));
  assert.equal(manifest.name, "NOVERAILE Commerce Suite");
  assert.match(manifest.version, /^\d+\.\d+\.\d+$/);
  assert.equal(manifest.author, "NOVERAILE");
  const required = [
    "admin/controller/module/noveraile.php",
    "admin/language/en-gb/module/noveraile.php",
    "admin/view/template/module/noveraile.twig",
    "catalog/controller/event/theme.php",
    "catalog/view/stylesheet/noveraile.css",
  ];
  await Promise.all(required.map((file) => readFile(path.join(root, file))));
});

test("container startup refreshes NOVERAILE media without reseeding an existing store", async () => {
  const [entrypoint, bootstrap] = await Promise.all([
    readFile(path.resolve("docker/entrypoint.sh"), "utf8"),
    readFile(path.resolve("docker/bootstrap-noveraile.php"), "utf8"),
  ]);

  assert.match(entrypoint, /find \/var\/www\/html\/image\/cache\/catalog\/noveraile -type f -delete/);
  assert.match(entrypoint, /noveraile_seed_demo=0/);
  assert.match(entrypoint, /timeout --kill-after=5s 30s env NOVERAILE_WITH_DEMO_DATA=0/);
  assert.match(entrypoint, /keeping the existing registration/);
  assert.match(bootstrap, /getenv\('NOVERAILE_WITH_DEMO_DATA'\)/);
  assert.match(bootstrap, /bootstrap\(\$withDemoData\)/);
});

test("admin content remains adjacent to the OpenCart sidebar", async () => {
  const template = await readFile(path.join(root, "admin/view/template/module/noveraile.twig"), "utf8");
  assert.match(template, /\{\{ header \}\}\{\{ column_left \}\}\s*<div id="content">/);
});

test("desktop navigation keeps every primary link vertically aligned", async () => {
  const [header, stylesheet] = await Promise.all([
    readFile(path.join(root, "catalog/view/template/common/header.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8"),
  ]);
  const navigationRule = stylesheet.match(/\.desktop-nav\s*\{[^}]*\}/)?.[0] ?? "";

  assert.match(navigationRule, /display:\s*flex/);
  assert.match(navigationRule, /align-items:\s*center/);
  assert.match(header, /class="bag"[^>]*><svg class="bag-icon"/);
  assert.match(stylesheet, /\.bag-icon\s*\{/);
});

test("mobile categories are deduplicated and use semantic jewellery icons", async () => {
  const [event, header, stylesheet] = await Promise.all([
    readFile(path.join(root, "catalog/controller/event/theme.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/common/header.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8"),
  ]);

  assert.match(event, /\$category_names\s*=\s*\[\]/);
  assert.match(event, /mb_strtolower/);
  assert.match(event, /'icon'\s*=>\s*\$this->categoryIcon\(\$name\)/);
  assert.match(event, /noveraile\.css\?v=2\.3\.0\.1/);
  assert.match(event, /noveraile\.js\?v=2\.3\.0\.1/);
  assert.match(header, /class="mobile-category-icon"/);
  assert.match(header, /category\.icon == 'earring'/);
  assert.match(header, /class="mobile-main-icon"/);
  assert.match(header, /class="mega-menu-heading-icon"[\s\S]*<i><\/i><i><\/i><i><\/i><i><\/i>/);
  assert.doesNotMatch(header, /mega-menu-heading[\s\S]*?<span><i aria-hidden="true"><\/i><small>/);
  assert.match(header, /\{\{ six_home_label \}\}/);
  assert.doesNotMatch(header, /<span>◇<\/span>\{\{ category\.name \}\}/);
  assert.doesNotMatch(header, />⌂ \{\{ text_home \}\}/);
  assert.match(stylesheet, /\.mobile-category-icon svg/);
  assert.match(stylesheet, /\.mobile-main-icon svg/);
  assert.match(stylesheet, /\.mega-menu-heading-icon\s*\{/);
  assert.doesNotMatch(stylesheet, /data-theme|theme-toggle|prefers-color-scheme/);
});

test("product listing presents unique image-led category cards", async () => {
  const [event, listing, stylesheet] = await Promise.all([
    readFile(path.join(root, "catalog/controller/event/theme.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/product/listing.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8"),
  ]);

  assert.match(event, /public function listing\([\s\S]*?\$category_names\s*=\s*\[\]/);
  assert.match(event, /public function listing\([\s\S]*?mb_strtolower/);
  assert.match(event, /public function listing\([\s\S]*?category_image/);
  assert.match(event, /public function listing\([\s\S]*?product_image/);
  assert.match(listing, /class="listing-category-all"/);
  assert.match(listing, /<img src="\{\{ category\.image \}\}"/);
  assert.doesNotMatch(listing, /listing-category-link listing-category-link--all/);
  assert.match(stylesheet, /\.listing-category-link img\s*\{/);
  assert.match(stylesheet, /linear-gradient\(180deg,rgba\(18,15,12,\.05\)/);
});

test("storefront is light-only and ships no theme control", async () => {
  const [header, stylesheet, script, admin, settings, adminTemplate] = await Promise.all([
    readFile(path.join(root, "catalog/view/template/common/header.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8"),
    readFile(path.join(root, "catalog/view/javascript/noveraile.js"), "utf8"),
    readFile(path.join(root, "admin/controller/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "admin/model/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "admin/view/template/module/noveraile.twig"), "utf8"),
  ]);

  assert.match(header, /name="color-scheme" content="light"/);
  for (const source of [header, stylesheet, script, admin, settings, adminTemplate]) {
    assert.doesNotMatch(source, /data-theme|theme-toggle|noveraile-theme|prefers-color-scheme|module_noveraile_color_mode/);
  }
});

test("customer-facing service icons use one coherent SVG system", async () => {
  const [cart, cartList, checkout, footer, stylesheet] = await Promise.all([
    readFile(path.join(root, "catalog/view/template/checkout/cart.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/template/checkout/cart_list.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/template/checkout/checkout.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/template/common/footer.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8"),
  ]);

  assert.match(cart, /class="checkout-inline-icon"/);
  assert.match(cartList, /class="assurance-icon"/);
  assert.match(checkout, /class="checkout-card-icon"/);
  assert.match(footer, /footer-brand[\s\S]*<svg class="line-icon"/);
  assert.match(stylesheet, /\.cart-page-assurances \.assurance-icon\s*\{/);
  assert.match(stylesheet, /\.checkout-card-icon\s*\{/);
  for (const source of [cart, cartList, checkout, footer, stylesheet]) {
    assert.doesNotMatch(source, /[◇◎↺✉]/);
  }
});

test("storefront notifications share one responsive status system", async () => {
  const [stylesheet, script, cart] = await Promise.all([
    readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8"),
    readFile(path.join(root, "catalog/view/javascript/noveraile.js"), "utf8"),
    readFile(path.join(root, "catalog/view/template/checkout/cart.twig"), "utf8"),
  ]);

  assert.match(stylesheet, /#alert\s*\{[^}]*position:\s*fixed;[^}]*display:\s*grid/);
  assert.match(stylesheet, /#alert \.alert-success[\s\S]*--notice-accent:\s*#52715a/);
  assert.match(stylesheet, /#alert \.alert-danger[\s\S]*--notice-accent:\s*#8a443b/);
  assert.match(stylesheet, /\.cart-page-alert\.is-info[\s\S]*--notice-accent:\s*#9a7445/);
  assert.match(stylesheet, /\.six-form-status\.is-success/);
  assert.match(stylesheet, /@media \(max-width:\s*420px\)[\s\S]*#alert/);
  assert.match(script, /const setFormStatus =/);
  assert.match(script, /status\.classList\.toggle\('is-error'/);
  assert.match(cart, /const role = type === 'error' \? 'alert' : 'status'/);
});

test("mobile catalog and cart keep primary content above the fold", async () => {
  const [stylesheet, cart, cartList] = await Promise.all([
    readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8"),
    readFile(path.join(root, "catalog/view/template/checkout/cart.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/template/checkout/cart_list.twig"), "utf8"),
  ]);

  assert.match(stylesheet, /\.catalog-hero\s*\{\s*min-height:\s*350px;\s*padding:\s*34px 22px 38px;/);
  assert.match(stylesheet, /\.catalog-dual-nav\s*\{\s*gap:\s*10px;\s*padding:\s*14px 16px;/);
  assert.match(stylesheet, /\.six-catalog-page \.catalog-toolbar\s*\{\s*display:\s*grid;\s*grid-template-columns:\s*auto 1fr;/);
  assert.match(stylesheet, /\.cart-page-progress\s*\{\s*min-height:\s*44px;/);
  assert.match(stylesheet, /\.cart-page-masthead\s*\{\s*gap:\s*14px;\s*padding:\s*23px 2px 22px;/);
  assert.match(stylesheet, /\.cart-page-layout\s*\{\s*gap:\s*28px;\s*padding-top:\s*24px;/);
  assert.match(stylesheet, /#checkout-cart\.cart-page\s*\{\s*padding:\s*0 14px 72px;/);
  assert.match(stylesheet, /#checkout-cart\.cart-page \.cart-page-section-heading h2\s*\{\s*margin:\s*5px 0 10px;/);
  assert.match(cartList, /data-cart-auto-update/);
  assert.doesNotMatch(cartList, /cart-page-update|button_update/);
  assert.match(cartList, /cart-page-summary-heading[\s\S]*summary-bag-icon/);
  assert.doesNotMatch(cartList, /cart-page-summary-heading[^\n]*>◇</);
  assert.match(cartList, /cart-page-assurances[\s\S]*<svg class="assurance-icon"/);
  assert.match(cartList, /cart-page-empty-benefits[\s\S]*<svg class="line-icon"/);
  assert.doesNotMatch(cartList, /<div><span>[◇◎○]<\/span><strong>/);
  assert.match(stylesheet, /\.cart-page-empty-benefits \.line-icon\s*\{/);
  assert.match(cart, /scheduleQuantityUpdate\(form, 220\)/);
  assert.match(cart, /scheduleQuantityUpdate\(this\.form, 0\)/);
});

test("premium suite ships working builder, mega menu, AJAX filters, one-page checkout and reviewed AI tools", async () => {
  const [admin, settings, adminTemplate, event, header, catalog, catalogTemplate, script, checkout] = await Promise.all([
    readFile(path.join(root, "admin/controller/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "admin/model/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "admin/view/template/module/noveraile.twig"), "utf8"),
    readFile(path.join(root, "catalog/controller/event/theme.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/common/header.twig"), "utf8"),
    readFile(path.join(root, "catalog/controller/page/catalog.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/page/catalog.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/javascript/noveraile.js"), "utf8"),
    readFile(path.join(root, "catalog/view/template/checkout/checkout.twig"), "utf8"),
  ]);
  assert.match(settings, /module_noveraile_page_builder/);
  assert.match(admin, /htmlspecialchars_decode\(.*module_noveraile_page_builder/s);
  assert.match(admin, /htmlspecialchars_decode\(.*module_noveraile_quiz_rules/s);
  assert.match(admin, /public function update\(\): void/);
  assert.match(admin, /version_compare\(\$next_version, self::VERSION, '<='\)/);
  assert.match(admin, /\.noveraile-backup-/);
  assert.match(adminTemplate, /multipart\/form-data/);
  assert.match(event, /six_home_blocks/);
  assert.match(event, /GROUP BY c\.category_id, cd\.name, c\.sort_order/);
  assert.match(header, /class="mega-menu"/);
  assert.match(catalog, /catalog_results/);
  assert.match(catalogTemplate, /data-six-ajax-filter/);
  assert.match(catalog, /getPriceBounds/);
  assert.match(catalogTemplate, /data-six-price-range/);
  assert.match(catalogTemplate, /data-six-price-lower/);
  assert.match(catalogTemplate, /data-six-price-upper/);
  assert.match(script, /--range-start/);
  assert.match(script, /history\.pushState/);
  assert.match(checkout, /checkout-page-grid/);
  assert.match(checkout, /checkout-page-sidebar-heading[\s\S]*summary-bag-icon/);
  assert.match(admin, /function aiGenerate\(/);
  assert.match(admin, /function aiApply\(/);
  assert.match(admin, /never invent specifications/);
});

test("all six sales-readiness promises are implemented and release-checked", async () => {
  const [manifestSource, admin, event, header, home, stylesheet, script, workflow, feedSource] = await Promise.all([
    readFile(path.join(root, "install.json"), "utf8"),
    readFile(path.join(root, "admin/controller/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "catalog/controller/event/theme.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/common/header.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/template/common/home.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8"),
    readFile(path.join(root, "catalog/view/javascript/noveraile.js"), "utf8"),
    readFile(path.resolve(".github/workflows/opencart-compatibility.yml"), "utf8"),
    readFile(path.resolve("public/updates/noveraile.json"), "utf8"),
  ]);
  const manifest = JSON.parse(manifestSource);
  const feed = JSON.parse(feedSource);

  assert.equal(manifest.version, "2.3.0");
  assert.equal(feed.version, manifest.version);
  assert.deepEqual(feed.opencart.tested, ["4.0.2.3", "4.1.0.3"]);
  assert.match(admin, /version_compare\(VERSION, '4\.0\.2\.3', '<'\)/);
  assert.match(workflow, /opencart: \["4\.0\.2\.3", "4\.1\.0\.3"\]/);

  assert.match(header, /name="color-scheme" content="light"/);
  assert.doesNotMatch(header, /data-theme|data-six-theme-toggle/);
  assert.doesNotMatch(script, /noveraile-theme|prefers-color-scheme/);
  assert.doesNotMatch(stylesheet, /data-theme|theme-toggle/);

  assert.match(header, /name="viewport"/);
  assert.match(stylesheet, /@media \(max-width: 360px\)/);
  assert.match(home, /fetchpriority="high"/);
  assert.match(home, /loading="lazy"/);
  assert.match(stylesheet, /content-visibility:\s*auto/);

  assert.match(event, /function claimView\(/);
  assert.match(event, /function blogRoute\(/);
  assert.match(header, /six_native_menu_status/);
  assert.match(header, /for link in links/);
});

test("marketplace install is opt-in and demo content is a separate action", async () => {
  const [controller, model] = await Promise.all([
    readFile(path.join(root, "admin/controller/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "admin/model/module/noveraile.php"), "utf8"),
  ]);
  assert.match(controller, /function installDemo\(/);
  assert.match(model, /bootstrap\(false\)/);
  assert.match(model, /private function installPermissions\(\): void/);
  assert.match(model, /extension\/noveraile\/module\/noveraile/);
  assert.match(model, /'module_noveraile_status'\s*=>\s*\(int\)\$enable_storefront/);
  assert.match(model, /'shipping_dhl_status'\s*=>\s*0/);
  assert.match(model, /'shipping_dpd_status'\s*=>\s*0/);
});

test("admin ships transactional multilingual product import and one-click export", async () => {
  const [controller, model, template, language] = await Promise.all([
    readFile(path.join(root, "admin/controller/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "admin/model/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "admin/view/template/module/noveraile.twig"), "utf8"),
    readFile(path.join(root, "admin/language/en-gb/module/noveraile.php"), "utf8"),
  ]);

  assert.match(controller, /public function exportProducts\(\): void/);
  assert.match(controller, /public function importProducts\(\): void/);
  assert.match(controller, /public function downloadCatalogTemplate\(\): void/);
  assert.match(controller, /fgetcsv\([^\n]+\$delimiter/);
  assert.match(controller, /count\(\$rows\) > 10000/);
  assert.match(model, /public function exportProducts\(\): array/);
  assert.match(model, /public function importProducts\(array \$rows, bool \$update_existing\): array/);
  assert.match(model, /START TRANSACTION[\s\S]+COMMIT[\s\S]+ROLLBACK/);
  assert.match(model, /usesProductCodeTable\(\)/);
  assert.match(model, /version_compare\(VERSION, '4\.1\.0\.0', '>='\)/);
  assert.match(model, /DELETE FROM `" \. DB_PREFIX \. "product_to_category`/);
  assert.match(model, /'sku' => \$product\['sku'\]/);
  assert.doesNotMatch(model.match(/if \(\$product\['product_id'\]\)[\s\S]*?\} else \{/s)?.[0] ?? "", /deleteOptions|deleteImages|deleteDiscounts|deleteRelated/);
  assert.match(template, /id="tab-catalog"/);
  assert.match(template, /id="catalog-drop"/);
  assert.match(template, /id="catalog-preview"/);
  assert.match(template, /href="\{\{ catalog_export \}\}"/);
  assert.match(language, /text_catalog_import_notice/);
});

test("storefront catalog accepts normal merchant products", async () => {
  const [eventController, catalogModel, bundleController] = await Promise.all([
    readFile(path.join(root, "catalog/controller/event/theme.php"), "utf8"),
    readFile(path.join(root, "catalog/model/catalog.php"), "utf8"),
    readFile(path.join(root, "catalog/controller/bundle.php"), "utf8"),
  ]);
  assert.doesNotMatch(catalogModel, /`p`\.`model`\s+LIKE\s+'NVR-%'/);
  assert.doesNotMatch(eventController.match(/private function getNoveraileProducts[\s\S]*?\n    }/)?.[0] ?? "", /model LIKE 'NVR-%'/);
  assert.doesNotMatch(bundleController, /str_starts_with/);
});

test("extension source contains no development artifacts or live Stripe secrets", async () => {
  async function walk(directory) {
    const entries = await readdir(directory, { withFileTypes: true });
    return (await Promise.all(entries.map(async (entry) => {
      const target = path.join(directory, entry.name);
      return entry.isDirectory() ? walk(target) : [target];
    }))).flat();
  }
  const files = await walk(root);
  assert.equal(files.some((file) => /(?:^|[\\/])(?:\.env|\.DS_Store|Thumbs\.db)$|\.(?:log|map|psd)$/i.test(file)), false);
  const combinedSource = [];
  for (const file of files.filter((name) => /\.(?:php|twig|js|json|md)$/i.test(name))) {
    const source = await readFile(file, "utf8");
    combinedSource.push(source);
    assert.doesNotMatch(source, /sk_live_[A-Za-z0-9]+|whsec_[A-Za-z0-9]+/, file);
  }
  const source = combinedSource.join("\n");
  assert.doesNotMatch(source, /Veloura|katya-dev\.duckdns\.org|noveraile\.store/i);
  assert.doesNotMatch(source, /value="\{\{\s*payment_stripe_(?:secret_key|webhook_secret)\s*\}\}"/);
  assert.match(source, /function assertPublicHttpsEndpoint\(/);
  assert.match(source, /FILTER_FLAG_NO_PRIV_RANGE\s*\|\s*FILTER_FLAG_NO_RES_RANGE/);
});

test("6 Moments storefront requirements remain wired into the package", async () => {
  const [installer, home, theme, cart, coupon, quiz, pricing, total, success] = await Promise.all([
    readFile(path.join(root, "admin/model/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/common/home.twig"), "utf8"),
    readFile(path.join(root, "catalog/controller/event/theme.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/checkout/cart_list.twig"), "utf8"),
    readFile(path.join(root, "catalog/controller/coupon.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/page/quiz.twig"), "utf8"),
    readFile(path.join(root, "catalog/model/pricing.php"), "utf8"),
    readFile(path.join(root, "catalog/model/total/bundle.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/checkout/success.twig"), "utf8"),
  ]);
  assert.match(installer, /module_noveraile_catalog_version', '6'/);
  assert.equal((installer.match(/\['(?:promise-solitaire|union-band|arrival-pendant|becoming-hoops|gratitude-bracelet|legacy-signet|eternity-band|horizon-studs|keepsake-pendant|self-promise-ring)'/g) ?? []).length, 10);
  assert.doesNotMatch(installer, /First Ride Balance Bike|NVR-SE-007/);
  assert.match(home, /stone=lab-grown/);
  assert.match(theme, /open\.er-api\.com\/v6\/latest\/USD/);
  assert.match(theme, /six_coupon_action/);
  assert.match(cart, /six_coupon-form|six-coupon-form/);
  assert.match(coupon, /session->data\['coupon'\]/);
  assert.match(quiz, /data-rules="\{\{ quiz_rules \}\}"/);
  assert.match(installer, /module_noveraile_price_book/);
  assert.match(pricing, /cartAdjustment/);
  assert.match(total, /market_adjustment/);
  assert.match(theme, /open\.er-api\.com\/v6\/latest\/USD/);
  assert.match(success, /six_order_id/);
});
