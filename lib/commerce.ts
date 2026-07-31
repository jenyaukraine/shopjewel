export const supportedCurrencies = ["USD", "EUR", "CZK", "UAH"] as const;
export type CheckoutCurrency = (typeof supportedCurrencies)[number];

const currencyRates: Record<CheckoutCurrency, number> = {
  USD: 1,
  EUR: 0.92,
  CZK: 23.4,
  UAH: 41.2,
};

type SecureCatalogProduct = {
  id: string;
  sku: string;
  title: string;
  subtitle: string;
  image: string;
  priceUsd: number;
};

export const secureCatalog: Record<string, SecureCatalogProduct> = {
  "promise-solitaire": {
    id: "promise-solitaire",
    sku: "NVR-RI-001",
    title: "Солітер «Обіцянка»",
    subtitle: "Золото 18 каратів · діамант із підтвердженим походженням",
    image: "/products/promise-solitaire.webp",
    priceUsd: 2450,
  },
  "union-band": {
    id: "union-band",
    sku: "NVR-WE-002",
    title: "Обручка «Союз»",
    subtitle: "Золото 18 каратів · ручне оздоблення",
    image: "/products/union-band.webp",
    priceUsd: 980,
  },
  "arrival-pendant": {
    id: "arrival-pendant",
    sku: "NVR-NE-003",
    title: "Підвіска «Нова глава»",
    subtitle: "Золото 18 каратів · діамант круглого огранювання",
    image: "/products/arrival-pendant.webp",
    priceUsd: 1320,
  },
  "becoming-hoops": {
    id: "becoming-hoops",
    sku: "NVR-EA-004",
    title: "Сережки «Становлення»",
    subtitle: "Золото 18 каратів · продаються парою",
    image: "/products/becoming-hoops.webp",
    priceUsd: 1180,
  },
  "gratitude-bracelet": {
    id: "gratitude-bracelet",
    sku: "NVR-BR-005",
    title: "Браслет «Вдячність»",
    subtitle: "Золото 18 каратів · діамантове паве",
    image: "/products/gratitude-bracelet.webp",
    priceUsd: 1560,
  },
  "legacy-signet": {
    id: "legacy-signet",
    sku: "NVR-RI-006",
    title: "Перстень «Спадщина»",
    subtitle: "Золото 18 каратів · гравіювання включено",
    image: "/products/legacy-signet.webp",
    priceUsd: 2250,
  },
  "first-ride": {
    id: "first-ride",
    sku: "NVR-SE-007",
    title: "Біговел «Перша поїздка»",
    subtitle: "Пам’ятний предмет для першої великої пригоди",
    image: "/products/first-ride.webp",
    priceUsd: 890,
  },
};

export type CheckoutLineInput = {
  productId: string;
  quantity: number;
  options?: Record<string, string>;
};

export type OrderLine = {
  productId: string;
  sku: string;
  title: string;
  image: string;
  quantity: number;
  unitAmount: number;
  options: Record<string, string>;
};

export function isCheckoutCurrency(value: unknown): value is CheckoutCurrency {
  return typeof value === "string" && supportedCurrencies.includes(value as CheckoutCurrency);
}

export function priceInMinorUnits(priceUsd: number, currency: CheckoutCurrency): number {
  return Math.round(priceUsd * currencyRates[currency] * 100);
}

export function resolveOrderLines(
  items: CheckoutLineInput[],
  currency: CheckoutCurrency,
): OrderLine[] {
  if (!Array.isArray(items) || items.length === 0 || items.length > 20) {
    throw new Error("Кошик порожній або містить забагато позицій.");
  }

  return items.map((item) => {
    const product = secureCatalog[item.productId];
    const quantity = Number(item.quantity);
    if (!product || !Number.isInteger(quantity) || quantity < 1 || quantity > 10) {
      throw new Error("Один із товарів у кошику більше недоступний.");
    }

    return {
      productId: product.id,
      sku: product.sku,
      title: product.title,
      image: product.image,
      quantity,
      unitAmount: priceInMinorUnits(product.priceUsd, currency),
      options: sanitizeOptions(item.options),
    };
  });
}

function sanitizeOptions(options: unknown): Record<string, string> {
  if (!options || typeof options !== "object" || Array.isArray(options)) return {};
  return Object.fromEntries(
    Object.entries(options)
      .slice(0, 8)
      .map(([key, value]) => [String(key).slice(0, 60), String(value).slice(0, 120)]),
  );
}
