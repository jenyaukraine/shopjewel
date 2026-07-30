export function runtimeEnv(): Cloudflare.Env {
  const shared = globalThis as typeof globalThis & {
    __SIXMOMENTS_ENV__?: Cloudflare.Env;
  };
  if (shared.__SIXMOMENTS_ENV__) return shared.__SIXMOMENTS_ENV__;
  return process.env as unknown as Cloudflare.Env;
}
