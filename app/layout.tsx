import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL("https://katya-dev.duckdns.org"),
  title: {
    default: "NOVERAILE — Де моменти стають спадщиною",
    template: "%s | NOVERAILE",
  },
  description:
    "Ювелірні вироби зі змістом, створені зберігати моменти, що визначають нас.",
  openGraph: {
    title: "NOVERAILE — Де моменти стають спадщиною",
    description:
      "Особливі речі, створені для людини, яка нестиме їхню історію далі.",
    images: [{ url: "/og-store.png", width: 1728, height: 910 }],
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "NOVERAILE — Де моменти стають спадщиною",
    description: "Особливі речі для моментів, які хочеться зберегти назавжди.",
    images: ["/og-store.png"],
  },
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
