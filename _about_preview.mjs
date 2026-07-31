import { createServer } from "node:http";
import { readFile } from "node:fs/promises";
import { extname, join } from "node:path";

const root = process.cwd();
const mime = { ".png": "image/png", ".webp": "image/webp", ".svg": "image/svg+xml" };

createServer(async (request, response) => {
  const pathname = new URL(request.url, "http://localhost").pathname;

  if (pathname !== "/about") {
    try {
      const asset = await readFile(join(root, pathname));
      response.writeHead(200, { "content-type": mime[extname(pathname)] || "application/octet-stream" });
      response.end(asset);
    } catch {
      response.writeHead(404).end();
    }
    return;
  }

  const [template, css] = await Promise.all([
    readFile(join(root, "opencart/sixmoments/catalog/view/template/page/about.twig"), "utf8"),
    readFile(join(root, "opencart/sixmoments/catalog/view/stylesheet/sixmoments.css"), "utf8"),
  ]);
  const body = template
    .replace("{{ header }}", '<div class="preview-rail">Complimentary insured delivery and returns</div><header class="preview-header"><span>6MOMENTS</span><nav>Shop&nbsp;&nbsp;&nbsp;&nbsp; Our story&nbsp;&nbsp;&nbsp;&nbsp; Journal</nav><small>Bag&nbsp; 0</small></header>')
    .replace("{{ footer }}", '<footer class="preview-footer">6MOMENTS — Where moments become legacy.</footer>')
    .replaceAll("{{ asset }}", "/opencart/sixmoments/image/catalog/sixmoments/")
    .replaceAll("{{ six_about_title }}", "A life is remembered in moments.")
    .replaceAll(/{{ six_[^}]+ }}/g, "#");

  response.writeHead(200, { "content-type": "text/html; charset=utf-8" });
  response.end(`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}</style><style>.preview-rail{height:30px;display:grid;place-items:center;color:#fff;background:#1c1b19;font-size:8px;letter-spacing:.16em;text-transform:uppercase}.preview-header{height:88px;padding:0 4vw;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;background:#fbfaf7;border-bottom:1px solid #ded8d0}.preview-header>span{font:600 25px var(--serif);letter-spacing:.16em}.preview-header nav{font-size:9px;letter-spacing:.12em;text-transform:uppercase}.preview-header small{justify-self:end}.preview-footer{padding:80px 6vw;color:#eee6db;background:#151412;font:28px var(--serif)}@media(max-width:760px){.preview-header{height:66px;grid-template-columns:1fr auto}.preview-header nav{display:none}.preview-header>span{font-size:20px}.preview-rail{height:26px}}</style></head><body class="sixmoments-store">${body}</body></html>`);
}).listen(18181, "127.0.0.1");
