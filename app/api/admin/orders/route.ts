import { NextResponse } from "next/server";
import { isAdminRequest } from "../../../../lib/admin-auth";
import {
  listOrders,
  updateFulfillmentStatus,
  type FulfillmentStatus,
} from "../../../../lib/db";

const statuses: FulfillmentStatus[] = [
  "new",
  "processing",
  "shipped",
  "completed",
  "cancelled",
];

export async function GET(request: Request) {
  if (!await isAdminRequest(request)) {
    return NextResponse.json({ error: "Потрібен вхід.", authenticated: false }, { status: 401 });
  }
  try {
    return NextResponse.json({ authenticated: true, orders: await listOrders() });
  } catch (error) {
    console.error("Orders list failed", error);
    return NextResponse.json({ error: "Не вдалося завантажити замовлення." }, { status: 503 });
  }
}

export async function PATCH(request: Request) {
  if (!await isAdminRequest(request)) {
    return NextResponse.json({ error: "Потрібен вхід." }, { status: 401 });
  }
  const body = await request.json().catch(() => ({})) as { id?: unknown; status?: unknown };
  if (
    typeof body.id !== "string" ||
    typeof body.status !== "string" ||
    !statuses.includes(body.status as FulfillmentStatus)
  ) {
    return NextResponse.json({ error: "Некоректний статус." }, { status: 400 });
  }

  const order = await updateFulfillmentStatus(body.id, body.status as FulfillmentStatus);
  if (!order) return NextResponse.json({ error: "Замовлення не знайдено." }, { status: 404 });
  return NextResponse.json({ order });
}
