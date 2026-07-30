import { index, integer, sqliteTable, text, uniqueIndex } from "drizzle-orm/sqlite-core";

export const orders = sqliteTable("orders", {
  id: text("id").primaryKey(),
  orderNumber: text("order_number").notNull(),
  stripeSessionId: text("stripe_session_id"),
  stripePaymentIntentId: text("stripe_payment_intent_id"),
  customerName: text("customer_name").notNull(),
  customerEmail: text("customer_email").notNull(),
  customerPhone: text("customer_phone"),
  address: text("address").notNull(),
  city: text("city").notNull(),
  postalCode: text("postal_code").notNull(),
  country: text("country").notNull(),
  deliveryMethod: text("delivery_method").notNull(),
  currency: text("currency").notNull(),
  subtotal: integer("subtotal").notNull(),
  itemsJson: text("items_json").notNull(),
  paymentStatus: text("payment_status").notNull().default("unpaid"),
  fulfillmentStatus: text("fulfillment_status").notNull().default("new"),
  createdAt: text("created_at").notNull(),
  updatedAt: text("updated_at").notNull(),
}, (table) => [
  uniqueIndex("orders_order_number_unique").on(table.orderNumber),
  uniqueIndex("orders_stripe_session_id_unique").on(table.stripeSessionId),
  index("orders_created_at_idx").on(table.createdAt),
  index("orders_payment_status_idx").on(table.paymentStatus, table.fulfillmentStatus),
]);

export const createOrdersTableSql = `
  CREATE TABLE IF NOT EXISTS orders (
    id TEXT PRIMARY KEY,
    order_number TEXT NOT NULL UNIQUE,
    stripe_session_id TEXT UNIQUE,
    stripe_payment_intent_id TEXT,
    customer_name TEXT NOT NULL,
    customer_email TEXT NOT NULL,
    customer_phone TEXT,
    address TEXT NOT NULL,
    city TEXT NOT NULL,
    postal_code TEXT NOT NULL,
    country TEXT NOT NULL,
    delivery_method TEXT NOT NULL,
    currency TEXT NOT NULL,
    subtotal INTEGER NOT NULL,
    items_json TEXT NOT NULL,
    payment_status TEXT NOT NULL DEFAULT 'unpaid',
    fulfillment_status TEXT NOT NULL DEFAULT 'new',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
  )
`;

export const createOrdersCreatedAtIndexSql =
  "CREATE INDEX IF NOT EXISTS orders_created_at_idx ON orders (created_at DESC)";

export const createOrdersPaymentStatusIndexSql =
  "CREATE INDEX IF NOT EXISTS orders_payment_status_idx ON orders (payment_status, fulfillment_status)";
