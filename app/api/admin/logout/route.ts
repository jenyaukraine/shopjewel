import { NextResponse } from "next/server";
import { clearAdminCookie } from "../../../../lib/admin-auth";

export async function POST(request: Request) {
  return NextResponse.json(
    { ok: true },
    { headers: { "Set-Cookie": clearAdminCookie(request) } },
  );
}
