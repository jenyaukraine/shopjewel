import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL("https://katya-dev.duckdns.org"),
  title: {
    default: "6MOMENTS — Where moments become legacy",
    template: "%s | 6MOMENTS",
  },
  description:
    "Fine jewelry in gold and diamonds, created to preserve the moments that define us.",
  openGraph: {
    title: "6MOMENTS — Where moments become legacy",
    description:
      "Timeless fine jewelry for the six moments that become your legacy.",
    images: [{ url: "/og.png", width: 1680, height: 940 }],
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "6MOMENTS — Where moments become legacy",
    description: "Fine jewelry for moments worth keeping forever.",
    images: ["/og.png"],
  },
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
