import Stripe from "stripe";
import { runtimeEnv } from "./runtime-env";

let stripeClient: Stripe | null = null;

export function getStripe(): Stripe {
  const env = runtimeEnv();
  const secretKey = env.STRIPE_SECRET_KEY;
  if (!secretKey) throw new Error("Stripe ще не підключено до цього середовища.");
  if (!stripeClient) {
    stripeClient = new Stripe(secretKey, {
      httpClient: Stripe.createFetchHttpClient(),
      maxNetworkRetries: 2,
    });
  }
  return stripeClient;
}

export function getStripeWebhookSecret(): string {
  const env = runtimeEnv();
  if (!env.STRIPE_WEBHOOK_SECRET) {
    throw new Error("Stripe webhook secret не налаштовано.");
  }
  return env.STRIPE_WEBHOOK_SECRET;
}

export function stripeCryptoProvider() {
  return Stripe.createSubtleCryptoProvider();
}
