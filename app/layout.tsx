import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL("https://katya-dev.duckdns.org"),
  title: {
    default: "6MOMENTS — Where moments become legacy",
    template: "%s | 6MOMENTS",
  },
  description:
    "Meaningful, configurable pieces made to preserve the moments that define us.",
  openGraph: {
    title: "6MOMENTS — Where moments become legacy",
    description:
      "Meaningful pieces, configured for the person who will carry them forward.",
    images: [{ url: "/og-store.png", width: 1680, height: 940 }],
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "6MOMENTS — Where moments become legacy",
    description: "Meaningful pieces for moments worth keeping forever.",
    images: ["/og-store.png"],
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
