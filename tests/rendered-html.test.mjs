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
  assert.match(html, /The six moments/);
  assert.match(html, /Crafted slowly/);
  assert.match(html, /https:\/\/katya-dev\.duckdns\.org\/og\.png/);
  assert.doesNotMatch(html, /codex-preview|Your site is taking shape/i);
});

test("server-renders collection and story routes", async () => {
  const [collectionResponse, storyResponse] = await Promise.all([
    render("/collections/the-promise"),
    render("/about"),
  ]);

  assert.equal(collectionResponse.status, 200);
  assert.equal(storyResponse.status, 200);

  const [collectionHtml, storyHtml] = await Promise.all([
    collectionResponse.text(),
    storyResponse.text(),
  ]);

  assert.match(collectionHtml, /Moment 01/);
  assert.match(collectionHtml, /The Promise/);
  assert.match(storyHtml, /One language\. Six chapters\./);
});
