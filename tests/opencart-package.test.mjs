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

test("admin content remains adjacent to the OpenCart sidebar", async () => {
  const template = await readFile(path.join(root, "admin/view/template/module/noveraile.twig"), "utf8");
  assert.match(template, /\{\{ header \}\}\{\{ column_left \}\}\s*<div id="content">/);
});

test("desktop navigation keeps every primary link vertically aligned", async () => {
  const stylesheet = await readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8");
  const navigationRule = stylesheet.match(/\.desktop-nav\s*\{[^}]*\}/)?.[0] ?? "";

  assert.match(navigationRule, /display:\s*flex/);
  assert.match(navigationRule, /align-items:\s*center/);
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
  assert.match(event, /noveraile\.css\?v=2\.2\.0\.5/);
  assert.match(header, /class="mobile-category-icon"/);
  assert.match(header, /category\.icon == 'earring'/);
  assert.match(header, /class="mobile-main-icon"/);
  assert.match(header, /\{\{ six_home_label \}\}/);
  assert.doesNotMatch(header, /<span>◇<\/span>\{\{ category\.name \}\}/);
  assert.doesNotMatch(header, />⌂ \{\{ text_home \}\}/);
  assert.match(stylesheet, /\.mobile-category-icon svg/);
  assert.match(stylesheet, /\.mobile-main-icon svg/);
  assert.match(stylesheet, /html\[data-theme="dark"\] \.mobile-drawer-header\s*\{[^}]*background:\s*#201e1b/);
  assert.match(stylesheet, /html\[data-theme="dark"\] \.mobile-category-icon\s*\{[^}]*background:\s*transparent;[^}]*border:\s*0/);
  assert.match(stylesheet, /html\[data-theme="dark"\] \.mobile-category-icon svg\s*\{[^}]*stroke:\s*currentColor/);
});

test("mobile theme control is a labelled, stateful switch", async () => {
  const [header, stylesheet, script] = await Promise.all([
    readFile(path.join(root, "catalog/view/template/common/header.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8"),
    readFile(path.join(root, "catalog/view/javascript/noveraile.js"), "utf8"),
  ]);

  assert.match(header, /class="mobile-theme-toggle"[^>]+role="switch"[^>]+aria-checked="false"/);
  assert.match(header, /data-six-theme-label/);
  assert.match(header, /class="mobile-theme-switch-knob"/);
  assert.match(stylesheet, /\.mobile-theme-switch-knob\s*\{/);
  assert.match(stylesheet, /html\[data-theme="dark"\] \.mobile-theme-switch-knob\s*\{[^}]*translateX/);
  assert.match(stylesheet, /> button:not\(\.mobile-theme-toggle\)/);
  assert.match(script, /setAttribute\('aria-checked', String\(isDark\)\)/);
  assert.match(script, /button\.dataset\.darkLabel/);
});

test("mobile catalog and cart keep primary content above the fold", async () => {
  const stylesheet = await readFile(path.join(root, "catalog/view/stylesheet/noveraile.css"), "utf8");

  assert.match(stylesheet, /\.catalog-hero\s*\{\s*min-height:\s*350px;\s*padding:\s*34px 22px 38px;/);
  assert.match(stylesheet, /\.catalog-dual-nav\s*\{\s*gap:\s*10px;\s*padding:\s*14px 16px;/);
  assert.match(stylesheet, /\.six-catalog-page \.catalog-toolbar\s*\{\s*display:\s*grid;\s*grid-template-columns:\s*auto 1fr;/);
  assert.match(stylesheet, /\.cart-page-progress\s*\{\s*min-height:\s*44px;/);
  assert.match(stylesheet, /\.cart-page-masthead\s*\{\s*gap:\s*14px;\s*padding:\s*23px 2px 22px;/);
  assert.match(stylesheet, /\.cart-page-layout\s*\{\s*gap:\s*28px;\s*padding-top:\s*24px;/);
  assert.match(stylesheet, /#checkout-cart\.cart-page\s*\{\s*padding:\s*0 14px 72px;/);
  assert.match(stylesheet, /#checkout-cart\.cart-page \.cart-page-section-heading h2\s*\{\s*margin:\s*5px 0 10px;/);
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

  assert.equal(manifest.version, "2.2.0");
  assert.equal(feed.version, manifest.version);
  assert.deepEqual(feed.opencart.tested, ["4.0.2.3", "4.1.0.3"]);
  assert.match(admin, /version_compare\(VERSION, '4\.0\.2\.3', '<'\)/);
  assert.match(workflow, /opencart: \["4\.0\.2\.3", "4\.1\.0\.3"\]/);

  assert.match(header, /data-theme=/);
  assert.match(header, /data-six-theme-toggle/);
  assert.match(script, /localStorage\.setItem\('noveraile-theme'/);
  assert.match(stylesheet, /html\[data-theme="dark"\]/);

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
  assert.doesNotMatch(source, /sixmoments|6MOMENTS|Veloura|katya-dev\.duckdns\.org|noveraile\.store/i);
  assert.doesNotMatch(source, /value="\{\{\s*payment_stripe_(?:secret_key|webhook_secret)\s*\}\}"/);
  assert.match(source, /function assertPublicHttpsEndpoint\(/);
  assert.match(source, /FILTER_FLAG_NO_PRIV_RANGE\s*\|\s*FILTER_FLAG_NO_RES_RANGE/);
});
