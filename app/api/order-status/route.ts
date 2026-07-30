import { NextResponse } from "next/server";
import { getOrderBySession, markOrderPaid } from "../../../lib/db";
import { getStripe } from "../../../lib/stripe";

export async function GET(request: Request) {
  const sessionId = new URL(request.url).searchParams.get("session_id");
  if (!sessionId || !sessionId.startsWith("cs_")) {
    return NextResponse.json({ error: "Некоректний номер сесії." }, { status: 400 });
  }

  try {
    let order = await getOrderBySession(sessionId);
    if (!order) return NextResponse.json({ error: "Замовлення не знайдено." }, { status: 404 });

    if (order.paymentStatus !== "paid") {
      const session = await getStripe().checkout.sessions.retrieve(sessionId);
      if (session.payment_status === "paid" || session.payment_status === "no_payment_required") {
        const paymentIntentId = typeof session.payment_intent === "string"
          ? session.payment_intent
          : session.payment_intent?.id ?? null;
        await markOrderPaid(session.id, paymentIntentId, {
          name: session.customer_details?.name,
          email: session.customer_details?.email,
          phone: session.customer_details?.phone,
        });
        order = (await getOrderBySession(sessionId))!;
      }
    }

    return NextResponse.json({
      orderNumber: order.orderNumber,
      paymentStatus: order.paymentStatus,
      fulfillmentStatus: order.fulfillmentStatus,
      currency: order.currency,
      subtotal: order.subtotal,
    });
  } catch (error) {
    console.error("Order status lookup failed", error);
    return NextResponse.json({ error: "Не вдалося перевірити оплату." }, { status: 503 });
  }
}
