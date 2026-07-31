import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

async function render(path = "/") {
  const workerUrl = new URL("../dist/server/index.js", import.meta.url);
  workerUrl.searchParams.set("test", `${process.pid}-${Date.now()}`);
  const { default: worker } = await import(workerUrl.href);

  return worker.fetch(
    new Request(`http://localhost${path}`, {
      headers: { accept: "text/html" },
    }),
    {
      ASSETS: {
        fetch: async () => new Response("Not found", { status: 404 }),
      },
    },
    {
      waitUntil() {},
      passThroughOnException() {},
    },
  );
}

test("server-renders the finished storefront", async () => {
  const response = await render();
  assert.equal(response.status, 200);
  assert.match(response.headers.get("content-type") ?? "", /^text\/html\b/i);

  const html = await response.text();
  assert.match(html, /<html lang="uk"/i);
  assert.match(html, /<title>NOVERAILE — Де моменти стають спадщиною<\/title>/i);
  assert.match(html, /Де моменти стають спадщиною\./);
  assert.match(html, /Найбажаніші/);
  assert.doesNotMatch(html, /class="intro"/);
  assert.match(html, /Солітер «Обіцянка»/);
  assert.match(html, /Створено неквапливо/);
  assert.match(html, /Категорії товарів/);
  assert.match(html, /Спецпропозиції/);
  assert.match(html, /Онлайн-консультації/);
  assert.match(html, /https:\/\/katya-dev\.duckdns\.org\/og-store\.png/);
  assert.doesNotMatch(html, /_vinext\/image/);
  assert.doesNotMatch(html, /codex-preview|Your site is taking shape/i);
});

test("OpenCart benefits use the storefront line icons instead of placeholder glyphs", async () => {
  const template = await readFile(
    new URL("../opencart/noveraile/catalog/view/template/common/home.twig", import.meta.url),
    "utf8",
  );

  const benefits = template.match(/<section class="benefits-strip"[\s\S]*?<\/section>/)?.[0] ?? "";
  assert.equal((benefits.match(/<svg class="line-icon"/g) ?? []).length, 4);
  assert.doesNotMatch(benefits, /[◇◎✦○]/);
});

test("OpenCart hero carousel never serves the legacy image with baked-in text", async () => {
  const controller = await readFile(
    new URL("../opencart/noveraile/catalog/controller/event/theme.php", import.meta.url),
    "utf8",
  );

  const heroSlides = controller.match(/\$data\['six_hero_slides'\]\s*=\s*\[[\s\S]*?\n\s*\];/)?.[0] ?? "";
  assert.match(heroSlides, /editorial\/lab-grown-diamond\.png/);
  assert.doesNotMatch(heroSlides, /hero-noveraile\.webp/);
});

test("OpenCart product cards keep the platform AJAX cart handler active", async () => {
  const [script, controller] = await Promise.all([
    readFile(
      new URL("../opencart/noveraile/catalog/view/javascript/noveraile.js", import.meta.url),
      "utf8",
    ),
    readFile(
      new URL("../opencart/noveraile/catalog/controller/event/theme.php", import.meta.url),
      "utf8",
    ),
  ]);

  assert.doesNotMatch(script, /stopImmediatePropagation\(\)/);
  assert.doesNotMatch(script, /function submitProductCard\b/);
  assert.match(script, /ajaxSuccess\.sixBagFlight/);
  assert.match(controller, /noveraile\.js\?v=\d+\.\d+\.\d+/);
});

test("OpenCart in-stock labels use the storefront success color", async () => {
  const [stylesheet, controller] = await Promise.all([
    readFile(new URL("../opencart/noveraile/catalog/view/stylesheet/noveraile.css", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/catalog/controller/event/theme.php", import.meta.url), "utf8"),
  ]);

  assert.match(stylesheet, /--success:\s*#4e7658/);
  assert.match(stylesheet, /\.product-details-list li:first-child,[\s\S]*?li:first-child::before\s*\{\s*color:\s*var\(--success\)/);
  assert.match(controller, /noveraile\.css\?v=\d+\.\d+\.\d+/);
});

test("OpenCart checkout uses the branded responsive purchase flow", async () => {
  const [template, stylesheet, script, controller, installer] = await Promise.all([
    readFile(new URL("../opencart/noveraile/catalog/view/template/checkout/checkout.twig", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/catalog/view/stylesheet/noveraile.css", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/catalog/view/javascript/noveraile.js", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/catalog/controller/event/theme.php", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/admin/model/module/noveraile.php", import.meta.url), "utf8"),
  ]);

  assert.match(template, /data-six-simple-checkout/);
  assert.match(template, /class="checkout-page-grid"/);
  assert.match(template, /id="checkout-delivery-step"[\s\S]*id="checkout-payment-step"[\s\S]*id="checkout-confirm"/);
  assert.match(template, /class="checkout-page-roadmap"/);
  assert.match(stylesheet, /\.checkout-page-grid\s*\{[^}]*grid-template-columns:/s);
  assert.match(stylesheet, /@media \(max-width: 820px\)[\s\S]*?\.checkout-page-grid\s*\{[^}]*grid-template-columns:\s*1fr/s);
  assert.match(stylesheet, /\.checkout-page-account-choice/);
  assert.match(script, /guest\.click\(\)/);
  assert.match(script, /checkout\/shipping_method\.save/);
  assert.match(controller, /extension\/noveraile\/checkout\/checkout/);
  assert.match(installer, /catalog\/view\/checkout\/checkout\/before/);
});

test("OpenCart account login uses the branded responsive client portal", async () => {
  const [template, stylesheet, controller, installer] = await Promise.all([
    readFile(new URL("../opencart/noveraile/catalog/view/template/account/login.twig", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/catalog/view/stylesheet/noveraile.css", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/catalog/controller/event/theme.php", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/admin/model/module/noveraile.php", import.meta.url), "utf8"),
  ]);

  assert.match(template, /class="account-login__portal"/);
  assert.match(template, /id="form-login"[\s\S]*?data-oc-toggle="ajax"/);
  assert.match(template, /autocomplete="email"/);
  assert.match(template, /autocomplete="current-password"/);
  assert.match(stylesheet, /\.account-login__portal\s*\{[^}]*grid-template-columns:/s);
  assert.match(stylesheet, /@media \(max-width: 760px\)[\s\S]*?\.account-login__portal\s*\{[^}]*grid-template-columns:\s*1fr/s);
  assert.match(controller, /extension\/noveraile\/account\/login/);
  assert.match(controller, /noveraile\.css\?v=\d+\.\d+\.\d+/);
  assert.match(installer, /catalog\/view\/account\/login\/before/);
});

test("OpenCart product zoom fills the preview and provides an interactive lightbox", async () => {
  const [template, stylesheet, script] = await Promise.all([
    readFile(new URL("../opencart/noveraile/catalog/view/template/product/product.twig", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/catalog/view/stylesheet/noveraile.css", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/catalog/view/javascript/noveraile.js", import.meta.url), "utf8"),
  ]);

  assert.match(template, /class="product-photo product-photo--detail"/);
  assert.match(template, /data-six-zoom-stage/);
  assert.match(template, /data-six-zoom-(?:in|out|reset)/);
  assert.match(stylesheet, /\.product-photo--detail\s*\{[^}]*width:\s*100%[^}]*height:\s*100%[^}]*object-fit:\s*cover/s);
  assert.match(script, /function setupProductZoom\b/);
  assert.match(script, /addEventListener\('wheel'/);
  assert.match(script, /setPointerCapture/);
  assert.match(script, /matchMedia\('\(pointer: coarse\)'\)/);
  assert.match(script, /touchViewport \? 2 : 4/);
  assert.match(script, /touchViewport \|\| Date\.now\(\) - openedAt < 400/);
  assert.match(script, /openedAt = Date\.now\(\)/);
});

test("server-renders product attributes and story routes", async () => {
  const [productResponse, storyResponse] = await Promise.all([
    render("/products/first-ride"),
    render("/about"),
  ]);

  assert.equal(productResponse.status, 200);
  assert.equal(storyResponse.status, 200);

  const [productHtml, storyHtml] = await Promise.all([
    productResponse.text(),
    storyResponse.text(),
  ]);

  assert.match(productHtml, /Біговел «Перша поїздка»/);
  assert.match(productHtml, /Розмір коліс/);
  assert.match(productHtml, /Розмір рами/);
  assert.match(productHtml, /Характеристики товару/);
  assert.match(productHtml, /Доставка:[\s\S]*3[\s\S]*днів/);
  assert.match(productHtml, /Натякнути про подарунок/);
  assert.match(storyHtml, /Одна мова\. Шість розділів\./);
});

test("server-renders catalog filters and CSV manager", async () => {
  const [catalogResponse, managerResponse] = await Promise.all([
    render("/collections"),
    render("/admin/catalog"),
  ]);

  assert.equal(catalogResponse.status, 200);
  assert.equal(managerResponse.status, 200);

  const [catalogHtml, managerHtml] = await Promise.all([
    catalogResponse.text(),
    managerResponse.text(),
  ]);

  assert.match(catalogHtml, /Пошук за назвою, артикулом, категорією або моментом/);
  assert.match(catalogHtml, /Діапазон цін/);
  assert.match(catalogHtml, /Мінімальна ціна/);
  assert.match(catalogHtml, /Максимальна ціна/);
  assert.match(catalogHtml, /Швидко додати/);
  assert.match(managerHtml, /Імпортуйте товари без редагування коду/);
  assert.match(managerHtml, /Завантажити шаблон CSV/);
  assert.match(managerHtml, /Оберіть CSV-файл/);
});

test("image requests fall back safely when Cloudflare bindings are unavailable", async () => {
  const workerUrl = new URL("../dist/server/index.js", import.meta.url);
  workerUrl.searchParams.set("test", `image-${process.pid}-${Date.now()}`);
  const { default: worker } = await import(workerUrl.href);
  const response = await worker.fetch(
    new Request("http://localhost/_vinext/image?url=%2Fproducts%2Fpromise-solitaire.webp&w=640&q=75"),
    {},
    { waitUntil() {}, passThroughOnException() {} },
  );

  assert.equal(response.status, 307);
  assert.equal(response.headers.get("location"), "http://localhost/products/promise-solitaire.webp");
});

test("server-renders Ukrainian legal information pages", async () => {
  const [privacyResponse, imprintResponse] = await Promise.all([
    render("/privacy"),
    render("/imprint"),
  ]);

  assert.equal(privacyResponse.status, 200);
  assert.equal(imprintResponse.status, 200);

  const [privacyHtml, imprintHtml] = await Promise.all([
    privacyResponse.text(),
    imprintResponse.text(),
  ]);
  assert.match(privacyHtml, /Політика конфіденційності/);
  assert.match(privacyHtml, /Ваші права/);
  assert.match(imprintHtml, /Юридична інформація/);
  assert.match(imprintHtml, /NOVERAILE Jewelry/);
});

test("server-renders restored brand blocks and diamond education", async () => {
  const [homeResponse, diamondsResponse] = await Promise.all([
    render("/"),
    render("/diamonds"),
  ]);

  assert.equal(homeResponse.status, 200);
  assert.equal(diamondsResponse.status, 200);

  const [homeHtml, diamondsHtml] = await Promise.all([
    homeResponse.text(),
    diamondsResponse.text(),
  ]);
  assert.match(homeHtml, /Лабораторні[\s\S]*діаманти/);
  assert.match(homeHtml, /@noveraile_jewelry/);
  assert.match(homeHtml, /atelier@noveraile\.store/);
  assert.match(homeHtml, /Останні історії/);
  assert.match(homeHtml, /Тиха архітектура каблучки/);
  assert.match(diamondsHtml, /Оберіть свій діамант/);
  assert.match(diamondsHtml, /Лабораторний діамант — це підробка/);
});

test("server-renders Stripe checkout outcomes and protected orders admin", async () => {
  const [successResponse, cancelledResponse, adminResponse] = await Promise.all([
    render("/checkout/success"),
    render("/checkout/cancelled"),
    render("/admin/orders"),
  ]);

  assert.equal(successResponse.status, 200);
  assert.equal(cancelledResponse.status, 200);
  assert.equal(adminResponse.status, 200);

  const [successHtml, cancelledHtml, adminHtml] = await Promise.all([
    successResponse.text(),
    cancelledResponse.text(),
    adminResponse.text(),
  ]);
  assert.match(successHtml, /Безпечна оплата Stripe/);
  assert.match(cancelledHtml, /Оплату не завершено/);
  assert.match(cancelledHtml, /Ваш вибір збережено/);
  assert.match(adminHtml, /Комерційна панель/);
  assert.match(adminHtml, /Замовлення NOVERAILE/);
});

test("OpenCart catalog keeps jewelry facets and attribute sorts wired", async () => {
  const [template, controller, model, installer] = await Promise.all([
    readFile(new URL("../opencart/noveraile/catalog/view/template/page/catalog.twig", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/catalog/controller/page/catalog.php", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/catalog/model/catalog.php", import.meta.url), "utf8"),
    readFile(new URL("../opencart/noveraile/admin/model/module/noveraile.php", import.meta.url), "utf8"),
  ]);

  assert.match(template, /name="ring_size"/);
  assert.match(template, /name="carat_min"/);
  assert.match(template, /name="stone_shape"/);
  assert.match(template, /name="style"/);
  assert.match(controller, /carat-desc/);
  assert.match(controller, /weight-desc/);
  assert.match(model, /product_attribute/);
  assert.match(model, /product_option_value/);
  assert.match(installer, /installJewelryAttributes/);
  assert.match(installer, /module_noveraile_attribute_map/);
});
