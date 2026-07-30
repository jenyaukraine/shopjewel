import { runtimeEnv } from "./runtime-env";

const COOKIE_NAME = "sixmoments_admin";
const SESSION_SECONDS = 60 * 60 * 12;

export async function verifyAdminPassword(password: string, request: Request): Promise<boolean> {
  const expected = configuredAdminPassword(request);
  if (!expected) return false;
  const [actualHash, expectedHash] = await Promise.all([digest(password), digest(expected)]);
  return constantTimeEqual(actualHash, expectedHash);
}

export async function isAdminRequest(request: Request): Promise<boolean> {
  const cookieValue = readCookie(request.headers.get("cookie"), COOKIE_NAME);
  if (!cookieValue) return false;
  const [expiresText, signature] = cookieValue.split(".");
  const expires = Number(expiresText);
  if (!expires || expires < Math.floor(Date.now() / 1000) || !signature) return false;
  const expected = await sign(expiresText, request);
  return constantTimeEqual(signature, expected);
}

export async function createAdminCookie(request: Request): Promise<string> {
  const expires = Math.floor(Date.now() / 1000) + SESSION_SECONDS;
  const value = `${expires}.${await sign(String(expires), request)}`;
  const secure = new URL(request.url).protocol === "https:" ? "; Secure" : "";
  return `${COOKIE_NAME}=${value}; Path=/; HttpOnly; SameSite=Strict; Max-Age=${SESSION_SECONDS}${secure}`;
}

export function clearAdminCookie(request: Request): string {
  const secure = new URL(request.url).protocol === "https:" ? "; Secure" : "";
  return `${COOKIE_NAME}=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0${secure}`;
}

export function adminConfigurationReady(request: Request): boolean {
  return Boolean(configuredAdminPassword(request) && configuredSessionSecret(request));
}

function configuredAdminPassword(request: Request): string | null {
  const env = runtimeEnv();
  if (env.ADMIN_PASSWORD) return env.ADMIN_PASSWORD;
  return isLocal(request) ? "sixmoments-demo" : null;
}

function configuredSessionSecret(request: Request): string | null {
  const env = runtimeEnv();
  if (env.ADMIN_SESSION_SECRET) return env.ADMIN_SESSION_SECRET;
  return isLocal(request) ? "local-sixmoments-session-secret" : null;
}

async function sign(payload: string, request: Request): Promise<string> {
  const secret = configuredSessionSecret(request);
  if (!secret) return "";
  const key = await crypto.subtle.importKey(
    "raw",
    new TextEncoder().encode(secret),
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign"],
  );
  const signature = await crypto.subtle.sign("HMAC", key, new TextEncoder().encode(payload));
  return bytesToBase64Url(new Uint8Array(signature));
}

async function digest(value: string): Promise<Uint8Array> {
  return new Uint8Array(await crypto.subtle.digest("SHA-256", new TextEncoder().encode(value)));
}

function constantTimeEqual(left: string | Uint8Array, right: string | Uint8Array): boolean {
  const a = typeof left === "string" ? new TextEncoder().encode(left) : left;
  const b = typeof right === "string" ? new TextEncoder().encode(right) : right;
  let mismatch = a.length === b.length ? 0 : 1;
  const length = Math.max(a.length, b.length);
  for (let index = 0; index < length; index += 1) {
    mismatch |= (a[index] ?? 0) ^ (b[index] ?? 0);
  }
  return mismatch === 0;
}

function bytesToBase64Url(bytes: Uint8Array): string {
  let binary = "";
  bytes.forEach((byte) => { binary += String.fromCharCode(byte); });
  return btoa(binary).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/g, "");
}

function readCookie(header: string | null, name: string): string | null {
  if (!header) return null;
  const pair = header.split(";").map((value) => value.trim()).find((value) => value.startsWith(`${name}=`));
  return pair ? pair.slice(name.length + 1) : null;
}

function isLocal(request: Request): boolean {
  const hostname = new URL(request.url).hostname;
  return hostname === "localhost" || hostname === "127.0.0.1";
}
