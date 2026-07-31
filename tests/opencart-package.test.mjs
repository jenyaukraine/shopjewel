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

test("premium suite ships working builder, mega menu, AJAX filters, one-page checkout and reviewed AI tools", async () => {
  const [admin, settings, event, header, catalog, catalogTemplate, script, checkout] = await Promise.all([
    readFile(path.join(root, "admin/controller/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "admin/model/module/noveraile.php"), "utf8"),
    readFile(path.join(root, "catalog/controller/event/theme.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/common/header.twig"), "utf8"),
    readFile(path.join(root, "catalog/controller/page/catalog.php"), "utf8"),
    readFile(path.join(root, "catalog/view/template/page/catalog.twig"), "utf8"),
    readFile(path.join(root, "catalog/view/javascript/noveraile.js"), "utf8"),
    readFile(path.join(root, "catalog/view/template/checkout/checkout.twig"), "utf8"),
  ]);
  assert.match(settings, /module_noveraile_page_builder/);
  assert.match(event, /six_home_blocks/);
  assert.match(header, /class="mega-menu"/);
  assert.match(catalog, /catalog_results/);
  assert.match(catalogTemplate, /data-six-ajax-filter/);
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

  assert.equal(manifest.version, "2.1.1");
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
  assert.match(model, /'module_noveraile_status'\s*=>\s*\(int\)\$enable_storefront/);
  assert.match(model, /'shipping_dhl_status'\s*=>\s*0/);
  assert.match(model, /'shipping_dpd_status'\s*=>\s*0/);
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
