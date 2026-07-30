import { NextResponse } from "next/server";
import Stripe from "stripe";
import {
  markOrderPaid,
  markOrderPaymentFailed,
} from "../../../../lib/db";
import {
  getStripe,
  getStripeWebhookSecret,
  stripeCryptoProvider,
} from "../../../../lib/stripe";

export async function POST(request: Request) {
  const signature = request.headers.get("stripe-signature");
  if (!signature) {
    return NextResponse.json({ error: "Missing Stripe signature." }, { status: 400 });
  }

  try {
    const payload = await request.text();
    const stripe = getStripe();
    const event = await stripe.webhooks.constructEventAsync(
      payload,
      signature,
      getStripeWebhookSecret(),
      undefined,
      stripeCryptoProvider(),
    );

    if (
      event.type === "checkout.session.completed" ||
      event.type === "checkout.session.async_payment_succeeded"
    ) {
      await fulfillSession(event.data.object as Stripe.Checkout.Session);
    } else if (
      event.type === "checkout.session.async_payment_failed" ||
      event.type === "checkout.session.expired"
    ) {
      await markOrderPaymentFailed((event.data.object as Stripe.Checkout.Session).id);
    }

    return NextResponse.json({ received: true });
  } catch (error) {
    console.error("Stripe webhook rejected", error);
    return NextResponse.json({ error: "Invalid webhook." }, { status: 400 });
  }
}

async function fulfillSession(session: Stripe.Checkout.Session) {
  if (session.payment_status !== "paid" && session.payment_status !== "no_payment_required") return;
  const paymentIntentId = typeof session.payment_intent === "string"
    ? session.payment_intent
    : session.payment_intent?.id ?? null;
  await markOrderPaid(session.id, paymentIntentId, {
    name: session.customer_details?.name,
    email: session.customer_details?.email,
    phone: session.customer_details?.phone,
  });
}
