import type { Metadata, Viewport } from "next";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL("https://6moments.store"),
  title: {
    default: "6 Moments — Where moments become legacy",
    template: "%s | 6 Moments",
  },
  description:
    "Fine jewellery created to preserve the moments that define us.",
  openGraph: {
    title: "6 Moments — Where moments become legacy",
    description:
      "Meaningful fine jewellery for stories that deserve to live on.",
    images: [{ url: "/og-oled.png", width: 1731, height: 909 }],
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "6 Moments — Where moments become legacy",
    description: "Meaningful fine jewellery for moments worth preserving.",
    images: ["/og-oled.png"],
  },
};

export const viewport: Viewport = {
  colorScheme: "light",
  themeColor: "#ddd7ce",
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="uk">
      <head>
        <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml" />
      </head>
      <body>{children}</body>
    </html>
  );
}
