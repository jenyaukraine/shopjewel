import assert from "node:assert/strict";
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
  assert.match(html, /<title>6MOMENTS — Де моменти стають спадщиною<\/title>/i);
  assert.match(html, /Де моменти стають спадщиною\./);
  assert.match(html, /Позачасові прикраси/);
  assert.match(html, /Солітер «Обіцянка»/);
  assert.match(html, /Створено неквапливо/);
  assert.match(html, /https:\/\/katya-dev\.duckdns\.org\/og-store\.png/);
  assert.doesNotMatch(html, /_vinext\/image/);
  assert.doesNotMatch(html, /codex-preview|Your site is taking shape/i);
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
  assert.match(imprintHtml, /6MOMENTS Jewelry/);
});
