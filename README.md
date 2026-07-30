# 6MOMENTS

Fine-jewelry storefront built with Next.js, vinext, and Cloudflare Workers.

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
