import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Product photography is already exported as compact WebP assets. Serving it
  // directly avoids relying on Cloudflare's IMAGES/ASSETS bindings in local
  // development and keeps the storefront portable across preview environments.
  images: {
    unoptimized: true,
  },
};

export default nextConfig;
