import { NextResponse } from "next/server";
import { createPendingOrder, attachStripeSession } from "../../../lib/db";
import {
  isCheckoutCurrency,
  resolveOrderLines,
  type CheckoutLineInput,
} from "../../../lib/commerce";
import { getStripe } from "../../../lib/stripe";
import { runtimeEnv } from "../../../lib/runtime-env";

type CheckoutBody = {
  currency?: unknown;
  items?: CheckoutLineInput[];
  customer?: {
    name?: unknown;
    email?: unknown;
    phone?: unknown;
    address?: unknown;
    city?: unknown;
    postalCode?: unknown;
    country?: unknown;
    deliveryMethod?: unknown;
  };
};

export async function POST(request: Request) {
  try {
    const body = await request.json() as CheckoutBody;
    if (!isCheckoutCurrency(body.currency)) {
      return errorResponse("Оберіть підтримувану валюту.", 400);
    }
    const currency = body.currency;

    const customer = validateCustomer(body.customer);
    const lines = resolveOrderLines(body.items ?? [], currency);
    const subtotal = lines.reduce((total, line) => total + line.unitAmount * line.quantity, 0);
    const stripe = getStripe();
    const id = crypto.randomUUID();
    const orderNumber = createOrderNumber();
    const origin = safeOrigin(request);

    await createPendingOrder({
      id,
      orderNumber,
      customerName: customer.name,
      customerEmail: customer.email,
      customerPhone: customer.phone,
      address: customer.address,
      city: customer.city,
      postalCode: customer.postalCode,
      country: customer.country,
      deliveryMethod: customer.deliveryMethod,
      currency,
      subtotal,
      items: lines,
    });

    const session = await stripe.checkout.sessions.create(
      {
        mode: "payment",
        locale: "auto",
        customer_email: customer.email,
        client_reference_id: id,
        metadata: { order_id: id, order_number: orderNumber },
        payment_method_types: ["card"],
        billing_address_collection: "required",
        phone_number_collection: { enabled: true },
        allow_promotion_codes: true,
        line_items: lines.map((line) => ({
          quantity: line.quantity,
          price_data: {
            currency: currency.toLowerCase(),
            unit_amount: line.unitAmount,
            product_data: {
              name: line.title,
              description: [
                line.sku,
                Object.entries(line.options).map(([name, value]) => `${name}: ${value}`).join(" · "),
              ].filter(Boolean).join(" · ").slice(0, 500),
              images: [`${origin}${line.image}`],
              metadata: { product_id: line.productId, sku: line.sku },
            },
          },
        })),
        success_url: `${origin}/checkout/success?session_id={CHECKOUT_SESSION_ID}`,
        cancel_url: `${origin}/checkout/cancelled`,
      },
      { idempotencyKey: `checkout-${id}` },
    );

    if (!session.url) throw new Error("Stripe не повернув адресу сторінки оплати.");
    await attachStripeSession(id, session.id);
    return NextResponse.json({ url: session.url, orderNumber });
  } catch (error) {
    console.error("Checkout creation failed", error);
    const message = error instanceof Error ? error.message : "Не вдалося розпочати оплату.";
    const status = message.includes("не підключено") || message.includes("не налаштовано") ? 503 : 400;
    return errorResponse(message, status);
  }
}

function validateCustomer(customer: CheckoutBody["customer"]) {
  const values = {
    name: clean(customer?.name, 120),
    email: clean(customer?.email, 180).toLowerCase(),
    phone: clean(customer?.phone, 40),
    address: clean(customer?.address, 240),
    city: clean(customer?.city, 120),
    postalCode: clean(customer?.postalCode, 30),
    country: clean(customer?.country, 80),
    deliveryMethod: clean(customer?.deliveryMethod, 120),
  };
  if (!values.name || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) {
    throw new Error("Перевірте ім’я та електронну адресу.");
  }
  if (!values.address || !values.city || !values.postalCode || !values.country || !values.deliveryMethod) {
    throw new Error("Заповніть адресу та спосіб доставки.");
  }
  return values;
}

function clean(value: unknown, length: number): string {
  return typeof value === "string" ? value.trim().slice(0, length) : "";
}

function safeOrigin(request: Request): string {
  const env = runtimeEnv();
  const configured = env.NEXT_PUBLIC_SITE_URL?.trim();
  if (configured) {
    try {
      return new URL(configured).origin;
    } catch {
      // Fall through to the request origin.
    }
  }
  return new URL(request.url).origin;
}

function createOrderNumber(): string {
  const date = new Date();
  const day = [
    String(date.getUTCFullYear()).slice(-2),
    String(date.getUTCMonth() + 1).padStart(2, "0"),
    String(date.getUTCDate()).padStart(2, "0"),
  ].join("");
  return `6M-${day}-${crypto.randomUUID().slice(0, 6).toUpperCase()}`;
}

function errorResponse(error: string, status: number) {
  return NextResponse.json({ error }, { status });
}
