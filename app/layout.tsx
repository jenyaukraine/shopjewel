import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL("https://katya-dev.duckdns.org"),
  title: {
    default: "6MOMENTS — Де моменти стають спадщиною",
    template: "%s | 6MOMENTS",
  },
  description:
    "Ювелірні вироби зі змістом, створені зберігати моменти, що визначають нас.",
  openGraph: {
    title: "6MOMENTS — Де моменти стають спадщиною",
    description:
      "Особливі речі, створені для людини, яка нестиме їхню історію далі.",
    images: [{ url: "/og-store.png", width: 1680, height: 940 }],
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "6MOMENTS — Де моменти стають спадщиною",
    description: "Особливі речі для моментів, які хочеться зберегти назавжди.",
    images: ["/og-store.png"],
  },
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="uk">
      <body>{children}</body>
    </html>
  );
}
