import { NextResponse } from "next/server";
import {
  adminConfigurationReady,
  createAdminCookie,
  verifyAdminPassword,
} from "../../../../lib/admin-auth";

export async function POST(request: Request) {
  if (!adminConfigurationReady(request)) {
    return NextResponse.json(
      { error: "Вхід до адмінки ще не налаштовано на сервері." },
      { status: 503 },
    );
  }

  const body = await request.json().catch(() => ({})) as { password?: unknown };
  const password = typeof body.password === "string" ? body.password : "";
  if (!await verifyAdminPassword(password, request)) {
    return NextResponse.json({ error: "Невірний пароль." }, { status: 401 });
  }

  return NextResponse.json(
    { ok: true },
    { headers: { "Set-Cookie": await createAdminCookie(request) } },
  );
}
