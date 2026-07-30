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
  assert.match(html, /<title>6MOMENTS — Where moments become legacy<\/title>/i);
  assert.match(html, /Where moments become legacy\./);
  assert.match(html, /Choose the details that make it yours\./);
  assert.match(html, /Promise Solitaire/);
  assert.match(html, /Crafted slowly/);
  assert.match(html, /https:\/\/katya-dev\.duckdns\.org\/og-store\.png/);
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

  assert.match(productHtml, /First Ride Balance Bike/);
  assert.match(productHtml, /Wheel size/);
  assert.match(productHtml, /Frame size/);
  assert.match(productHtml, /Product specifications/);
  assert.match(productHtml, /Delivery in[\s\S]*3[\s\S]*days/);
  assert.match(productHtml, /Hint about this gift/);
  assert.match(storyHtml, /One language\. Six chapters\./);
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

  assert.match(catalogHtml, /Search by name, SKU, category or moment/);
  assert.match(catalogHtml, /Maximum price/);
  assert.match(catalogHtml, /Quick add/);
  assert.match(managerHtml, /Import products without touching code/);
  assert.match(managerHtml, /Download CSV template/);
  assert.match(managerHtml, /Choose a CSV file/);
});
