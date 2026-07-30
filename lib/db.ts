import {
  createOrdersCreatedAtIndexSql,
  createOrdersPaymentStatusIndexSql,
  createOrdersTableSql,
} from "../db/schema";
import type { CheckoutCurrency, OrderLine } from "./commerce";
import { runtimeEnv } from "./runtime-env";

export type PaymentStatus = "unpaid" | "paid" | "failed" | "refunded";
export type FulfillmentStatus = "new" | "processing" | "shipped" | "completed" | "cancelled";

export type StoredOrder = {
  id: string;
  orderNumber: string;
  stripeSessionId: string | null;
  stripePaymentIntentId: string | null;
  customerName: string;
  customerEmail: string;
  customerPhone: string;
  address: string;
  city: string;
  postalCode: string;
  country: string;
  deliveryMethod: string;
  currency: CheckoutCurrency;
  subtotal: number;
  items: OrderLine[];
  paymentStatus: PaymentStatus;
  fulfillmentStatus: FulfillmentStatus;
  createdAt: string;
  updatedAt: string;
};

type NewOrder = Omit<
  StoredOrder,
  "stripeSessionId" | "stripePaymentIntentId" | "paymentStatus" | "fulfillmentStatus" | "createdAt" | "updatedAt"
>;

let schemaPromise: Promise<void> | null = null;

function database(): D1Database {
  const env = runtimeEnv();
  if (!env.DB) throw new Error("Сховище замовлень не налаштовано.");
  return env.DB;
}

async function ensureSchema(): Promise<void> {
  if (!schemaPromise) {
    const db = database();
    schemaPromise = db.batch([
      db.prepare(createOrdersTableSql),
      db.prepare(createOrdersCreatedAtIndexSql),
      db.prepare(createOrdersPaymentStatusIndexSql),
    ]).then(() => undefined).catch((error) => {
      schemaPromise = null;
      throw error;
    });
  }
  return schemaPromise;
}

export async function createPendingOrder(order: NewOrder): Promise<StoredOrder> {
  await ensureSchema();
  const now = new Date().toISOString();
  await database().prepare(`
    INSERT INTO orders (
      id, order_number, customer_name, customer_email, customer_phone,
      address, city, postal_code, country, delivery_method, currency,
      subtotal, items_json, payment_status, fulfillment_status, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', 'new', ?, ?)
  `).bind(
    order.id,
    order.orderNumber,
    order.customerName,
    order.customerEmail,
    order.customerPhone,
    order.address,
    order.city,
    order.postalCode,
    order.country,
    order.deliveryMethod,
    order.currency,
    order.subtotal,
    JSON.stringify(order.items),
    now,
    now,
  ).run();
  return (await getOrderById(order.id))!;
}

export async function attachStripeSession(orderId: string, sessionId: string): Promise<void> {
  await ensureSchema();
  await database().prepare(
    "UPDATE orders SET stripe_session_id = ?, updated_at = ? WHERE id = ?",
  ).bind(sessionId, new Date().toISOString(), orderId).run();
}

export async function markOrderPaid(
  sessionId: string,
  paymentIntentId: string | null,
  customer?: { name?: string | null; email?: string | null; phone?: string | null },
): Promise<void> {
  await ensureSchema();
  await database().prepare(`
    UPDATE orders SET
      payment_status = 'paid',
      stripe_payment_intent_id = COALESCE(?, stripe_payment_intent_id),
      customer_name = COALESCE(NULLIF(?, ''), customer_name),
      customer_email = COALESCE(NULLIF(?, ''), customer_email),
      customer_phone = COALESCE(NULLIF(?, ''), customer_phone),
      updated_at = ?
    WHERE stripe_session_id = ?
  `).bind(
    paymentIntentId,
    customer?.name ?? "",
    customer?.email ?? "",
    customer?.phone ?? "",
    new Date().toISOString(),
    sessionId,
  ).run();
}

export async function markOrderPaymentFailed(sessionId: string): Promise<void> {
  await ensureSchema();
  await database().prepare(
    "UPDATE orders SET payment_status = 'failed', updated_at = ? WHERE stripe_session_id = ? AND payment_status != 'paid'",
  ).bind(new Date().toISOString(), sessionId).run();
}

export async function getOrderById(id: string): Promise<StoredOrder | null> {
  await ensureSchema();
  const row = await database().prepare("SELECT * FROM orders WHERE id = ?").bind(id).first<Record<string, unknown>>();
  return row ? mapOrder(row) : null;
}

export async function getOrderBySession(sessionId: string): Promise<StoredOrder | null> {
  await ensureSchema();
  const row = await database().prepare("SELECT * FROM orders WHERE stripe_session_id = ?").bind(sessionId).first<Record<string, unknown>>();
  return row ? mapOrder(row) : null;
}

export async function listOrders(limit = 100): Promise<StoredOrder[]> {
  await ensureSchema();
  const result = await database().prepare(
    "SELECT * FROM orders ORDER BY created_at DESC LIMIT ?",
  ).bind(Math.min(Math.max(limit, 1), 250)).all<Record<string, unknown>>();
  return (result.results ?? []).map(mapOrder);
}

export async function updateFulfillmentStatus(id: string, status: FulfillmentStatus): Promise<StoredOrder | null> {
  await ensureSchema();
  await database().prepare(
    "UPDATE orders SET fulfillment_status = ?, updated_at = ? WHERE id = ?",
  ).bind(status, new Date().toISOString(), id).run();
  return getOrderById(id);
}

function mapOrder(row: Record<string, unknown>): StoredOrder {
  return {
    id: String(row.id),
    orderNumber: String(row.order_number),
    stripeSessionId: row.stripe_session_id ? String(row.stripe_session_id) : null,
    stripePaymentIntentId: row.stripe_payment_intent_id ? String(row.stripe_payment_intent_id) : null,
    customerName: String(row.customer_name),
    customerEmail: String(row.customer_email),
    customerPhone: String(row.customer_phone ?? ""),
    address: String(row.address),
    city: String(row.city),
    postalCode: String(row.postal_code),
    country: String(row.country),
    deliveryMethod: String(row.delivery_method),
    currency: String(row.currency) as CheckoutCurrency,
    subtotal: Number(row.subtotal),
    items: JSON.parse(String(row.items_json)) as OrderLine[],
    paymentStatus: String(row.payment_status) as PaymentStatus,
    fulfillmentStatus: String(row.fulfillment_status) as FulfillmentStatus,
    createdAt: String(row.created_at),
    updatedAt: String(row.updated_at),
  };
}
