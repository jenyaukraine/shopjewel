# 6MOMENTS

Fine-jewelry storefront built with Next.js, vinext, and Cloudflare Workers.

## Commerce MVP

- Filterable and searchable catalog with type, moment, metal, stone, availability, delivery, and price controls.
- Product attributes, configurable variants, engraving, gift hints, bundles, and recommendations.
- Persistent shopping bag and independent USD, EUR, CZK, and UAH display currencies.
- Browser-based CSV catalog workspace at `/admin/catalog`.

The CSV workspace validates and previews these columns before publishing:

```text
id,slug,sku,title,category,moment,price,old_price,metal,fineness,stone_type,availability,delivery_days,weight,carat,stone_count,image,subtitle,description
```

Imported records persist on the current device. The catalog data model is intentionally API-ready so the same fields can be moved to a production database and shared with a mobile application.

## Local development

Requires Node.js 22.13 or newer.

```bash
npm ci
npm run dev
```

## Validation

```bash
npm run lint
npx tsc --noEmit
npm test
```

`npm test` creates the production build and verifies the rendered home,
collection, and story pages.

## Production container

The included Docker and Compose files run the site on port 3000 and connect it
to the shared external `web-edge` network:

```bash
docker network inspect web-edge >/dev/null 2>&1 || docker network create web-edge
docker compose up -d --build
```

The included Caddy configuration routes
`https://katya-dev.duckdns.org/` to the `sixmoments-store` container. Merge that
site block into the shared Caddy configuration and reload Caddy after the
container becomes healthy.
