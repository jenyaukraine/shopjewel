"use client";

import { ChangeEvent, FormEvent, KeyboardEvent as ReactKeyboardEvent, useCallback, useEffect, useId, useMemo, useRef, useState } from "react";
import Image from "next/image";
import Link from "next/link";

type ProductOption = {
  name: string;
  values: string[];
};

type Product = {
  id: string;
  slug: string;
  sku: string;
  category: string;
  moment: string;
  title: string;
  subtitle: string;
  description: string;
  price: number;
  oldPrice?: number;
  image: string;
  metal: string;
  fineness: string;
  stoneType: string;
  availability: "В наявності" | "Під замовлення";
  deliveryDays: number;
  weight: number;
  carat: number;
  stoneCount: number;
  popularity: number;
  isNew?: boolean;
  options: ProductOption[];
  details: string[];
};

type CurrencyCode = "USD" | "EUR" | "CZK" | "UAH";

const currencyRates: Record<CurrencyCode, number> = {
  USD: 1,
  EUR: 0.92,
  CZK: 23.4,
  UAH: 41.2,
};

const ringDiameters: Record<string, string> = {
  "48": "15,3",
  "50": "15,9",
  "52": "16,5",
  "54": "17,2",
  "56": "17,8",
};

function formatMoney(price: number, currency: CurrencyCode) {
  return new Intl.NumberFormat("uk-UA", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(price * currencyRates[currency]);
}

type CartItem = {
  key: string;
  productId: string;
  quantity: number;
  options: Record<string, string>;
};

type AdminOrder = {
  id: string;
  orderNumber: string;
  customerName: string;
  customerEmail: string;
  customerPhone: string;
  address: string;
  city: string;
  postalCode: string;
  country: string;
  deliveryMethod: string;
  currency: CurrencyCode;
  subtotal: number;
  items: Array<{
    productId: string;
    sku: string;
    title: string;
    image: string;
    quantity: number;
    unitAmount: number;
    options: Record<string, string>;
  }>;
  paymentStatus: "unpaid" | "paid" | "failed" | "refunded";
  fulfillmentStatus: "new" | "processing" | "shipped" | "completed" | "cancelled";
  createdAt: string;
};

function formatMinorMoney(amount: number, currency: CurrencyCode) {
  return new Intl.NumberFormat("uk-UA", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(amount / 100);
}

const products: Product[] = [
  {
    id: "promise-solitaire",
    slug: "promise-solitaire",
    sku: "6M-RI-001",
    category: "Кільця",
    moment: "Момент 01 — Обіцянка",
    title: "Солітер «Обіцянка»",
    subtitle: "Золото 18 каратів · діамант із підтвердженим походженням",
    description:
      "Солітер із низькою посадкою та м’яко заокругленою шинкою, створений для зручності й щоденного носіння.",
    price: 2450,
    oldPrice: 2750,
    image: "/products/promise-solitaire.webp",
    metal: "Жовте золото",
    fineness: "750 / 18k",
    stoneType: "Лабораторний діамант",
    availability: "В наявності",
    deliveryDays: 3,
    weight: 2.8,
    carat: 0.5,
    stoneCount: 1,
    popularity: 100,
    isNew: true,
    options: [
      { name: "Метал", values: ["Жовте золото", "Біле золото", "Рожеве золото"] },
      { name: "Розмір каблучки", values: ["48", "50", "52", "54", "56"] },
      { name: "Камінь", values: ["0,30 карата", "0,50 карата", "0,75 карата"] },
    ],
    details: ["Цільне перероблене золото 18 каратів", "Діамант чистоти VS із підтвердженим походженням", "Безкоштовна зміна розміру протягом 30 днів"],
  },
  {
    id: "union-band",
    slug: "union-band",
    sku: "6M-WE-002",
    category: "Обручки",
    moment: "Момент 02 — Союз",
    title: "Обручка «Союз»",
    subtitle: "Золото 18 каратів · ручне оздоблення",
    description:
      "Позачасова обручка з делікатно пом’якшеним профілем. Виготовляється окремо або парою та оздоблюється вручну.",
    price: 980,
    image: "/products/union-band.webp",
    metal: "Жовте золото",
    fineness: "750 / 18k",
    stoneType: "Без каменів",
    availability: "Під замовлення",
    deliveryDays: 10,
    weight: 3.9,
    carat: 0,
    stoneCount: 0,
    popularity: 92,
    options: [
      { name: "Метал", values: ["Жовте золото", "Біле золото", "Рожеве золото"] },
      { name: "Розмір каблучки", values: ["48", "50", "52", "54", "56", "58"] },
      { name: "Ширина", values: ["2 мм", "3 мм", "4 мм"] },
    ],
    details: ["Цільне перероблене золото 18 каратів", "Матова або полірована грань", "Додаткове гравіювання включено"],
  },
  {
    id: "arrival-pendant",
    slug: "arrival-pendant",
    sku: "6M-NE-003",
    category: "Підвіски",
    moment: "Момент 03 — Нова глава",
    title: "Підвіска «Нова глава»",
    subtitle: "Золото 18 каратів · діамант круглого огранювання",
    description:
      "Маленька точка світла на тонкому ланцюжку — створена, щоб зберегти день, коли у світі почалася нова глава.",
    price: 1320,
    oldPrice: 1480,
    image: "/products/arrival-pendant.webp",
    metal: "Жовте золото",
    fineness: "750 / 18k",
    stoneType: "Натуральний діамант",
    availability: "В наявності",
    deliveryDays: 3,
    weight: 2.1,
    carat: 0.1,
    stoneCount: 1,
    popularity: 96,
    options: [
      { name: "Метал", values: ["Жовте золото", "Біле золото"] },
      { name: "Довжина ланцюжка", values: ["40 см", "45 см", "50 см"] },
      { name: "Гравіювання", values: ["Без гравіювання", "Ініціал", "Дата"] },
    ],
    details: ["Цільне перероблене золото 18 каратів", "Діамант 0,10 карата з підтвердженим походженням", "Регульована застібка ланцюжка"],
  },
  {
    id: "becoming-hoops",
    slug: "becoming-hoops",
    sku: "6M-EA-004",
    category: "Сережки",
    moment: "Момент 04 — Становлення",
    title: "Сережки «Становлення»",
    subtitle: "Золото 18 каратів · продаються парою",
    description:
      "Легкі овальні сережки з виразністю для кожного дня та стриманістю, що робить їх по-справжньому вашими.",
    price: 1180,
    image: "/products/becoming-hoops.webp",
    metal: "Жовте золото",
    fineness: "750 / 18k",
    stoneType: "Без каменів",
    availability: "В наявності",
    deliveryDays: 3,
    weight: 4.2,
    carat: 0,
    stoneCount: 0,
    popularity: 88,
    isNew: true,
    options: [
      { name: "Метал", values: ["Жовте золото", "Біле золото", "Рожеве золото"] },
      { name: "Розмір", values: ["Малий", "Середній", "Великий"] },
      { name: "Оздоблення", values: ["Поліроване", "М’яке матове"] },
    ],
    details: ["Цільне перероблене золото 18 каратів", "Надійна шарнірна застібка", "Комфортна легка конструкція"],
  },
  {
    id: "gratitude-bracelet",
    slug: "gratitude-bracelet",
    sku: "6M-BR-005",
    category: "Браслети",
    moment: "Момент 05 — Вдячність",
    title: "Браслет «Вдячність»",
    subtitle: "Золото 18 каратів · камінь закріплений вручну",
    description:
      "Тонкий браслет з овальними ланками та одним діамантом — тихе «дякую», яке завжди поруч.",
    price: 1560,
    oldPrice: 1790,
    image: "/products/gratitude-bracelet.webp",
    metal: "Жовте золото",
    fineness: "750 / 18k",
    stoneType: "Натуральний діамант",
    availability: "В наявності",
    deliveryDays: 3,
    weight: 2.6,
    carat: 0.15,
    stoneCount: 1,
    popularity: 90,
    options: [
      { name: "Метал", values: ["Жовте золото", "Біле золото"] },
      { name: "Довжина", values: ["15 см", "17 см", "19 см"] },
      { name: "Камінь", values: ["Діамант", "Сапфір", "Смарагд"] },
    ],
    details: ["Цільне перероблене золото 18 каратів", "Натуральний камінь із підтвердженим походженням", "Прихована захисна застібка"],
  },
  {
    id: "legacy-signet",
    slug: "legacy-signet",
    sku: "6M-RI-006",
    category: "Кільця",
    moment: "Момент 06 — Спадщина",
    title: "Перстень «Спадщина»",
    subtitle: "Платина · виготовлення під замовлення",
    description:
      "Вагомий перстень із пом’якшеною площиною для знака, монограми, дати або символу, що належить лише вам.",
    price: 2250,
    image: "/products/legacy-signet.webp",
    metal: "Платина",
    fineness: "950",
    stoneType: "Без каменів",
    availability: "Під замовлення",
    deliveryDays: 10,
    weight: 8.4,
    carat: 0,
    stoneCount: 0,
    popularity: 82,
    options: [
      { name: "Матеріал", values: ["Платина", "Жовте золото", "Біле золото"] },
      { name: "Розмір каблучки", values: ["50", "52", "54", "56", "58", "60"] },
      { name: "Лицьова частина", values: ["Без оздоблення", "Монограма", "Символ"] },
    ],
    details: ["Індивідуальне виготовлення під замовлення", "Доступне ручне гравіювання", "Подається у коробці з цільного дуба"],
  },
  {
    id: "first-ride",
    slug: "first-ride",
    sku: "6M-SE-007",
    category: "Спеціальні видання",
    moment: "Спецсерія — Перша поїздка",
    title: "Біговел «Перша поїздка»",
    subtitle: "Ясен · шкіра · сплав",
    description:
      "Довговічна річ для найпершої пригоди. Модель товару підтримує практичні атрибути: діаметр коліс, розмір рами та колір.",
    price: 890,
    image: "/products/first-ride.webp",
    metal: "Сплав",
    fineness: "Не застосовується",
    stoneType: "Без каменів",
    availability: "В наявності",
    deliveryDays: 3,
    weight: 3100,
    carat: 0,
    stoneCount: 0,
    popularity: 76,
    isNew: true,
    options: [
      { name: "Розмір коліс", values: ["12 дюймів", "14 дюймів", "16 дюймів"] },
      { name: "Розмір рами", values: ["Малий", "Середній"] },
      { name: "Колір", values: ["Вівсяний", "Лісовий", "Графітовий"] },
    ],
    details: ["Рама з відповідально заготовленого ясена", "Стійкі до проколів шини", "Регульоване шкіряне сідло"],
  },
];

const routeCopy: Record<string, { eyebrow: string; title: string; copy: string }> = {
  "/collections": {
    eyebrow: "Колекція",
    title: "Речі, що мають причину залишитися.",
    copy: "Сім речей, створених надовго. Оберіть розмір, матеріал і оздоблення, щоб кожна стала саме вашою.",
  },
  "/about": {
    eyebrow: "Наша філософія",
    title: "Життя запам’ятовується моментами.",
    copy: "6MOMENTS створює сучасні реліквії для подій, що формують нас — не лише очікуваних, а й глибоко особистих.",
  },
  "/journal": {
    eyebrow: "Журнал",
    title: "Історії, які варто нести далі.",
    copy: "Нотатки про ритуали, майстерність і людей, які надають речам значення.",
  },
  "/contact": {
    eyebrow: "Приватні консультації",
    title: "Ми поруч у ваш особливий момент.",
    copy: "Поговоріть із нашою майстернею про розмір, камені, гравіювання або прикрасу, створену лише для вас.",
  },
  "/privacy": {
    eyebrow: "Інформація",
    title: "Політика конфіденційності.",
    copy: "Коротко про те, які дані ми збираємо, навіщо вони потрібні та як ви можете керувати ними.",
  },
  "/imprint": {
    eyebrow: "Інформація",
    title: "Юридична інформація.",
    copy: "Контактні дані та відомості про 6MOMENTS для клієнтів і партнерів.",
  },
  "/diamonds": {
    eyebrow: "Освідомлена розкіш",
    title: "Лабораторні чи натуральні діаманти.",
    copy: "Два справжні діаманти з однаковим блиском і різним походженням. Обирайте той, що відповідає саме вашим цінностям.",
  },
};

const heroSlides = [
  {
    id: "signature",
    image: "/hero-6moments-v2.png",
    mobileImage: "/hero-6moments-mobile.png",
    imagePosition: "58% 48%",
    kicker: "Авторська колекція",
    title: "Де моменти стають спадщиною.",
    primaryLabel: "Переглянути колекцію",
    primaryHref: "/collections",
    secondaryLabel: "Дізнатися більше",
    secondaryHref: "/about",
  },
  {
    id: "diamonds",
    image: "/editorial/lab-grown-diamond.png",
    mobileImage: "/editorial/lab-grown-diamond-mobile.png",
    imagePosition: "58% 50%",
    kicker: "Освідомлена розкіш",
    title: "Діамант, обраний із наміром.",
    primaryLabel: "Переглянути прикраси",
    primaryHref: "/products/promise-solitaire",
    secondaryLabel: "Про наші діаманти",
    secondaryHref: "/diamonds",
  },
  {
    id: "craft",
    image: "/editorial/craftsmanship.webp",
    mobileImage: "/editorial/craftsmanship.webp",
    imagePosition: "64% 52%",
    kicker: "Ручна майстерність",
    title: "Створено неквапливо. Збережено назавжди.",
    primaryLabel: "Обрати свою прикрасу",
    primaryHref: "/collections",
    secondaryLabel: "Наша майстерність",
    secondaryHref: "/about",
  },
] as const;

type LineIconName = "home" | "jewel" | "story" | "journal" | "diamond" | "mail" | "certificate" | "shield" | "delivery" | "verified";

function LineIcon({ name }: { name: LineIconName }) {
  const paths: Record<LineIconName, React.ReactNode> = {
    home: <><path d="M3 10.5 12 3l9 7.5" /><path d="M5.5 9.5V21h13V9.5M9.5 21v-6h5v6" /></>,
    jewel: <><path d="m4 8 3-4h10l3 4-8 12Z" /><path d="m4 8 8 12 8-12M7 4l5 4 5-4M4 8h16" /></>,
    story: <><circle cx="12" cy="12" r="9" /><path d="M12 10v6M12 7h.01" /></>,
    journal: <><path d="M5 3.5h11a3 3 0 0 1 3 3V21H8a3 3 0 0 1-3-3Z" /><path d="M8 3.5V18a3 3 0 0 0 3 3M11 8h5M11 12h5" /></>,
    diamond: <><path d="m3.5 8 3.6-4h9.8l3.6 4L12 20Z" /><path d="m7.1 4 4.9 4 4.9-4M3.5 8h17" /></>,
    mail: <><rect x="3" y="5" width="18" height="14" rx="1.5" /><path d="m4 7 8 6 8-6" /></>,
    certificate: <><circle cx="12" cy="10" r="6.5" /><path d="m8 15-1 6 5-2 5 2-1-6M9.5 10l1.6 1.6 3.5-3.5" /></>,
    shield: <><path d="M12 3 20 6v5.5c0 4.5-3.2 7.8-8 9.5-4.8-1.7-8-5-8-9.5V6Z" /><path d="m8.5 12 2.2 2.2 4.8-4.8" /></>,
    delivery: <><path d="M3 6h11v10H3ZM14 9h4l3 3v4h-7Z" /><circle cx="7" cy="18" r="2" /><circle cx="18" cy="18" r="2" /></>,
    verified: <><circle cx="12" cy="12" r="9" /><path d="m8 12 2.6 2.6L16.5 9" /></>,
  };

  return (
    <svg className="line-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      {paths[name]}
    </svg>
  );
}

function Header({
  path,
  count,
  currency,
  onCurrency,
  onOpenCart,
}: {
  path: string;
  count: number;
  currency: CurrencyCode;
  onCurrency: (currency: CurrencyCode) => void;
  onOpenCart: () => void;
}) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const mobileCategories = [
    ["Каблучки", "/products/promise-solitaire.webp"],
    ["Браслети", "/products/gratitude-bracelet.webp"],
    ["Підвіски", "/products/arrival-pendant.webp"],
    ["Сережки", "/products/becoming-hoops.webp"],
    ["Спецпропозиції", "/products/legacy-signet.webp"],
  ];

  useEffect(() => {
    document.body.classList.toggle("menu-open", mobileOpen);
    return () => document.body.classList.remove("menu-open");
  }, [mobileOpen]);

  function closeMobileMenu() {
    setMobileOpen(false);
  }

  return (
    <>
      <div className="announcement">Безкоштовна застрахована доставка та повернення</div>
      <header className="site-header">
        <Link className="wordmark" href="/" aria-label="6MOMENTS — головна">
          6MOMENTS
        </Link>
        <nav className="desktop-nav" aria-label="Основна навігація">
          <Link aria-current={path.startsWith("/collections") || path.startsWith("/products") ? "page" : undefined} href="/collections">
            Каталог
          </Link>
          <Link aria-current={path === "/about" ? "page" : undefined} href="/about">
            Про нас
          </Link>
          <Link aria-current={path === "/journal" ? "page" : undefined} href="/journal">
            Журнал
          </Link>
        </nav>
        <div className="header-actions">
          <label className="currency-control">
            <span className="sr-only">Валюта</span>
            <select value={currency} onChange={(event) => onCurrency(event.target.value as CurrencyCode)}>
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="CZK">CZK</option>
              <option value="UAH">UAH</option>
            </select>
          </label>
          <Link href="/contact">Приватна консультація</Link>
          <button className="bag" type="button" onClick={onOpenCart} aria-label={`Кошик, товарів: ${count}`}>
            Кошик <span>{count}</span>
          </button>
        </div>
        <button
          className="mobile-menu-trigger"
          type="button"
          aria-expanded={mobileOpen}
          aria-controls="mobile-navigation"
          onClick={() => setMobileOpen(true)}
        >
          <span aria-hidden="true">☰</span> Меню
        </button>
      </header>
      <div className={`mobile-drawer-shell ${mobileOpen ? "is-open" : ""}`} aria-hidden={!mobileOpen}>
        <button className="mobile-drawer-backdrop" type="button" aria-label="Закрити меню" onClick={closeMobileMenu} />
        <aside className="mobile-drawer" id="mobile-navigation" aria-label="Мобільна навігація">
          <div className="mobile-drawer-header">
            <strong>Меню</strong>
            <button type="button" onClick={closeMobileMenu} aria-label="Закрити меню">×</button>
          </div>
          <nav className="mobile-category-nav" aria-label="Категорії товарів">
            {mobileCategories.map(([label, image]) => (
              <Link href="/collections" key={label} onClick={closeMobileMenu}>
                <span>
                  <Image src={image} alt="" unoptimized width={92} height={92} sizes="46px" />
                </span>
                {label}
              </Link>
            ))}
          </nav>
          <nav className="mobile-main-nav" aria-label="Розділи сайту">
            <Link href="/" onClick={closeMobileMenu}><span><LineIcon name="home" /></span> Головна</Link>
            <Link href="/collections" onClick={closeMobileMenu}><span><LineIcon name="jewel" /></span> Усі прикраси</Link>
            <Link href="/about" onClick={closeMobileMenu}><span><LineIcon name="story" /></span> Про нас</Link>
            <Link href="/journal" onClick={closeMobileMenu}><span><LineIcon name="journal" /></span> Журнал</Link>
            <Link href="/diamonds" onClick={closeMobileMenu}><span><LineIcon name="diamond" /></span> Про діаманти</Link>
            <Link href="/contact" onClick={closeMobileMenu}><span><LineIcon name="mail" /></span> Контакти</Link>
          </nav>
          <div className="mobile-drawer-controls">
            <label>Валюта
              <select value={currency} onChange={(event) => onCurrency(event.target.value as CurrencyCode)}>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="CZK">CZK</option>
                <option value="UAH">UAH</option>
              </select>
            </label>
            <button
              type="button"
              onClick={() => {
                closeMobileMenu();
                onOpenCart();
              }}
            >
              Кошик <span>{count}</span>
            </button>
          </div>
          <div className="mobile-drawer-contact">
            <a href="mailto:atelier@6moments.store">atelier@6moments.store</a>
            <small>Онлайн-консультації · Пн—Пт, 10:00—18:00</small>
          </div>
        </aside>
      </div>
    </>
  );
}

function defaultOptions(product: Product) {
  return Object.fromEntries(product.options.map((option) => [option.name, option.values[0]]));
}

function ProductGrid({
  products: catalog,
  currency,
  limit,
  onQuickAdd,
}: {
  products: Product[];
  currency: CurrencyCode;
  limit?: number;
  onQuickAdd?: (product: Product, options: Record<string, string>) => void;
}) {
  const visibleProducts = typeof limit === "number" ? catalog.slice(0, limit) : catalog;

  return (
    <div className="collection-grid product-grid">
      {visibleProducts.map((product) => (
        <article className="moment-card product-card" key={product.id}>
          <Link href={`/products/${product.slug}`}>
            <div className="moment-art">
              <Image
                className="product-photo"
                src={product.image}
                alt=""
                unoptimized
                width={1200}
                height={1200}
                sizes="(max-width: 720px) 50vw, 33vw"
              />
              <span className="moment-number">{product.moment.split(" — ")[0]}</span>
              {product.isNew && <span className="product-badge">Новинка</span>}
              <span className="card-arrow" aria-hidden="true">↗</span>
            </div>
            <p className="product-kicker">{product.moment}</p>
            <div className="product-line">
              <h3>{product.title}</h3>
              <span className="card-price">
                {product.oldPrice && <del>{formatMoney(product.oldPrice, currency)}</del>}
                {formatMoney(product.price, currency)}
              </span>
            </div>
            <p>{product.subtitle}</p>
            <dl className="card-specs">
              <div><dt>Артикул</dt><dd>{product.sku}</dd></div>
              <div><dt>Вага</dt><dd>{product.weight >= 100 ? `${(product.weight / 1000).toFixed(1)} кг` : `${product.weight} г`}</dd></div>
              <div><dt>Карати</dt><dd>{product.carat || "—"}</dd></div>
              <div><dt>Камені</dt><dd>{product.stoneCount || "—"}</dd></div>
            </dl>
          </Link>
          <div className="card-footer">
            <span className={`stock-status ${product.availability === "В наявності" ? "is-stocked" : ""}`}>
              {product.availability} · доставка {product.deliveryDays} днів
            </span>
            {onQuickAdd && (
              <button type="button" onClick={() => onQuickAdd(product, defaultOptions(product))}>
                Швидко додати
              </button>
            )}
          </div>
        </article>
      ))}
    </div>
  );
}

function HomePage({
  products: catalog,
  currency,
  onQuickAdd,
}: {
  products: Product[];
  currency: CurrencyCode;
  onQuickAdd: (product: Product, options: Record<string, string>) => void;
}) {
  const [heroSlideIndex, setHeroSlideIndex] = useState(0);
  const [heroPaused, setHeroPaused] = useState(false);
  const heroTouchStartX = useRef<number | null>(null);
  const currentHeroSlide = heroSlides[heroSlideIndex];

  const showHeroSlide = useCallback((index: number) => {
    setHeroSlideIndex((index + heroSlides.length) % heroSlides.length);
  }, []);

  const showPreviousHeroSlide = useCallback(() => {
    setHeroSlideIndex((current) => (current - 1 + heroSlides.length) % heroSlides.length);
  }, []);

  const showNextHeroSlide = useCallback(() => {
    setHeroSlideIndex((current) => (current + 1) % heroSlides.length);
  }, []);

  useEffect(() => {
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    if (heroPaused || reducedMotion.matches) return;

    const interval = window.setInterval(showNextHeroSlide, 6500);
    return () => window.clearInterval(interval);
  }, [heroPaused, showNextHeroSlide]);

  return (
    <main>
      <section
        className="hero"
        aria-roledescription="карусель"
        aria-label="Головні пропозиції"
        onMouseEnter={() => setHeroPaused(true)}
        onMouseLeave={() => setHeroPaused(false)}
        onFocus={() => setHeroPaused(true)}
        onBlur={() => setHeroPaused(false)}
        onKeyDown={(event) => {
          if (event.key === "ArrowLeft") {
            event.preventDefault();
            showPreviousHeroSlide();
          }
          if (event.key === "ArrowRight") {
            event.preventDefault();
            showNextHeroSlide();
          }
        }}
        onTouchStart={(event) => {
          heroTouchStartX.current = event.touches[0]?.clientX ?? null;
          setHeroPaused(true);
        }}
        onTouchEnd={(event) => {
          const startX = heroTouchStartX.current;
          const endX = event.changedTouches[0]?.clientX;
          heroTouchStartX.current = null;
          setHeroPaused(false);

          if (startX === null || endX === undefined || Math.abs(startX - endX) < 45) return;
          if (startX > endX) showNextHeroSlide();
          else showPreviousHeroSlide();
        }}
      >
        <div className="hero-backgrounds" aria-hidden="true">
          {heroSlides.map((slide, index) => (
            <div
              key={slide.id}
              className={`hero-background ${index === heroSlideIndex ? "is-active" : ""}`}
              style={{
                "--hero-image": `url("${slide.image}")`,
                "--hero-mobile-image": `url("${slide.mobileImage}")`,
                backgroundPosition: slide.imagePosition,
              } as React.CSSProperties}
            />
          ))}
        </div>
        <div className="hero-copy" key={currentHeroSlide.id}>
          <p className="hero-kicker">{currentHeroSlide.kicker}</p>
          <h1>{currentHeroSlide.title}</h1>
          <div className="hero-actions">
            <Link className="button button--dark" href={currentHeroSlide.primaryHref}>
              {currentHeroSlide.primaryLabel}
            </Link>
            <Link className="hero-learn" href={currentHeroSlide.secondaryHref}>
              {currentHeroSlide.secondaryLabel} <span aria-hidden="true">→</span>
            </Link>
          </div>
        </div>
        <div className="hero-pagination" aria-label="Оберіть слайд">
          {heroSlides.map((slide, index) => (
            <button
              key={slide.id}
              className={index === heroSlideIndex ? "is-active" : ""}
              type="button"
              aria-label={`Показати слайд ${index + 1}`}
              aria-current={index === heroSlideIndex ? "true" : undefined}
              onClick={() => showHeroSlide(index)}
            />
          ))}
        </div>
        <div className="hero-controls">
          <button type="button" aria-label="Наступний слайд" onClick={showNextHeroSlide}>›</button>
          <button type="button" aria-label="Попередній слайд" onClick={showPreviousHeroSlide}>‹</button>
        </div>
        <p className="sr-only" aria-live="polite">
          Слайд {heroSlideIndex + 1} із {heroSlides.length}: {currentHeroSlide.title}
        </p>
      </section>

      <section className="intro">
        <p className="intro-script" aria-hidden="true">Колекції</p>
        <h2>Позачасові прикраси</h2>
        <p className="intro-copy">
          Сучасні реліквії із золота та діамантів
        </p>
      </section>

      <section className="moments-section editorial-products signature-section" aria-labelledby="products-title">
        <div className="editorial-heading">
          <p className="editorial-script" aria-hidden="true">Особливі прикраси</p>
          <h2 id="products-title">Найбажаніші</h2>
          <p>Позачасові прикраси, до яких повертаються знову і знову</p>
          <Link className="sale-link" href="/collections">SALE</Link>
        </div>
        <ProductGrid products={catalog} currency={currency} limit={3} onQuickAdd={onQuickAdd} />
      </section>

      <section className="benefits-strip" aria-label="Переваги 6MOMENTS">
        <article>
          <span className="benefit-icon"><LineIcon name="certificate" /></span>
          <h3>Сертифіковані діаманти GIA</h3>
          <p>Натуральні діаманти з офіційним підтвердженням походження</p>
        </article>
        <article>
          <span className="benefit-icon"><LineIcon name="shield" /></span>
          <h3>Застрахована доставка</h3>
          <p>Безпечне міжнародне перевезення з повним відстеженням</p>
        </article>
        <article>
          <span className="benefit-icon"><LineIcon name="delivery" /></span>
          <h3>Безкоштовна доставка</h3>
          <p>Компліментарна доставка для замовлень від €1000</p>
        </article>
        <article>
          <span className="benefit-icon"><LineIcon name="verified" /></span>
          <h3>Перевірено перед відправленням</h3>
          <p>Кожну прикрасу особисто оглядають експерти майстерні</p>
        </article>
      </section>

      <section className="moments-section editorial-products archive-section" aria-labelledby="archive-title">
        <div className="editorial-heading">
          <p className="editorial-script" aria-hidden="true">Вибране</p>
          <h2 id="archive-title">Архівна колекція</h2>
          <p>Рідкісні прикраси, доступні протягом обмеженого часу</p>
        </div>
        <ProductGrid products={catalog.slice(3, 6)} currency={currency} onQuickAdd={onQuickAdd} />
        <div className="archive-action">
          <Link className="text-link" href="/collections">Переглянути всю колекцію <span aria-hidden="true">→</span></Link>
        </div>
      </section>

      <section className="craft">
        <Image
          className="craft-image"
          src="/editorial/craftsmanship.webp"
          alt="Ювелір вручну оздоблює золоту каблучку"
          unoptimized
          width={1200}
          height={1200}
          sizes="(max-width: 720px) 100vw, 58vw"
        />
        <div className="craft-copy">
          <p className="eyebrow">Створено пережити сам момент</p>
          <h2>Створено неквапливо.<br />Збережено назавжди.</h2>
          <p>
            Кожна річ продумана з усіх боків, оздоблена вручну й створена ставати дедалі особистішою з часом.
          </p>
          <Link className="text-link" href="/about">Наші матеріали та майстерність <span aria-hidden="true">→</span></Link>
        </div>
      </section>

      <section className="lab-diamond-banner" aria-labelledby="lab-diamond-title">
        <Image
          src="/editorial/lab-grown-diamond.png"
          alt=""
          unoptimized
          width={1792}
          height={896}
          sizes="100vw"
        />
        <div className="lab-diamond-overlay" />
        <div className="lab-diamond-copy">
          <p className="eyebrow">Освідомлена розкіш</p>
          <h2 id="lab-diamond-title">Лабораторні<br />діаманти</h2>
          <p>
            Справжні діаманти з тим самим складом, твердістю та блиском —
            створені в контрольованих умовах і перевірені незалежними лабораторіями.
          </p>
          <div>
            <Link className="button button--light" href="/products/promise-solitaire">Переглянути прикраси</Link>
            <Link className="lab-learn-link" href="/diamonds">Дізнатися більше <span aria-hidden="true">→</span></Link>
          </div>
        </div>
      </section>

      <section className="testimonials" aria-labelledby="testimonials-title">
        <div className="testimonials-heading">
          <div>
            <p className="editorial-script" aria-hidden="true">Відгуки</p>
            <h2 id="testimonials-title">Слова наших клієнтів</h2>
            <p>Історії людей, для яких прикраса стала частиною важливого моменту</p>
          </div>
          <div className="rating-summary" aria-label="Середня оцінка 4,9 з 5">
            <strong>4,9</strong>
            <div>
              <span aria-hidden="true">★★★★★</span>
              <small>128 перевірених відгуків</small>
            </div>
          </div>
        </div>

        <div className="testimonial-grid">
          <article className="testimonial-card">
            <div className="testimonial-stars" aria-label="5 з 5 зірок">★★★★★</div>
            <blockquote>
              «Каблучка виявилася ще витонченішою, ніж на фото. Особливо цінно,
              що команда допомогла обрати розмір і тримала мене в курсі до самої доставки».
            </blockquote>
            <footer>
              <span className="client-mark" aria-hidden="true">ОМ</span>
              <div><strong>Олена М.</strong><small>Київ · Солітер «Обіцянка»</small></div>
              <span className="verified-review">Перевірена покупка</span>
            </footer>
          </article>

          <article className="testimonial-card testimonial-card--featured">
            <div className="testimonial-stars" aria-label="5 з 5 зірок">★★★★★</div>
            <blockquote>
              «Підвіска стала подарунком на народження доньки. Гравіювання,
              пакування й сам діамант — усе відчувається дуже особистим і продуманим».
            </blockquote>
            <footer>
              <span className="client-mark" aria-hidden="true">АС</span>
              <div><strong>Анна С.</strong><small>Львів · Підвіска «Нова глава»</small></div>
              <span className="verified-review">Перевірена покупка</span>
            </footer>
          </article>

          <article className="testimonial-card">
            <div className="testimonial-stars" aria-label="5 з 5 зірок">★★★★★</div>
            <blockquote>
              «Замовляли обручки парою. Нам запропонували ідеальну ширину,
              а ручне оздоблення зробило їх справді нашими. Сервіс бездоганний».
            </blockquote>
            <footer>
              <span className="client-mark" aria-hidden="true">ІД</span>
              <div><strong>Ірина та Данило</strong><small>Прага · Обручки «Союз»</small></div>
              <span className="verified-review">Перевірена покупка</span>
            </footer>
          </article>
        </div>

        <div className="testimonial-facts" aria-label="Сервіс у цифрах">
          <div><strong>128</strong><span>перевірених відгуків</span></div>
          <div><strong>4,9/5</strong><span>середня оцінка</span></div>
          <div><strong>30 днів</strong><span>на безкоштовну зміну розміру</span></div>
          <div><strong>100%</strong><span>застрахованих відправлень</span></div>
        </div>
      </section>

      <section className="brand-story" aria-labelledby="brand-story-title">
        <div className="brand-story-visual">
          <Image
            src="/editorial/journal-heirlooms.webp"
            alt="Прикраси 6MOMENTS у світлій подарунковій композиції"
            unoptimized
            width={1200}
            height={1200}
            sizes="(max-width: 800px) 100vw, 52vw"
          />
          <span>01 · Наша історія</span>
        </div>
        <div className="brand-story-copy">
          <p className="editorial-script" aria-hidden="true">Щоденник</p>
          <h2 id="brand-story-title">Прикраси про те, що справді важливе</h2>
          <p className="brand-story-lead">
            Є моменти, які тихо змінюють усе: перше «так», новий початок,
            особистий вибір або вдячність людині, яка завжди поруч.
          </p>
          <p>
            6MOMENTS створює сучасні реліквії із золота, платини та діамантів.
            Кожна прикраса задумана як частина вашої історії — для щоденного
            носіння, майбутніх спогадів і передачі наступному поколінню.
          </p>
          <dl className="brand-story-values">
            <div><dt>01</dt><dd><strong>Особистий сенс</strong><span>Розмір, метал і гравіювання обираєте ви</span></dd></div>
            <div><dt>02</dt><dd><strong>Чесні матеріали</strong><span>Перероблене золото та камені з підтвердженим походженням</span></dd></div>
            <div><dt>03</dt><dd><strong>Ручна робота</strong><span>Фінальне оздоблення й перевірка кожної прикраси в майстерні</span></dd></div>
          </dl>
          <Link className="button story-button" href="/about">Читати нашу історію <span aria-hidden="true">→</span></Link>
        </div>
      </section>

      <section className="inspiration-gallery" aria-labelledby="gallery-title">
        <div className="gallery-heading">
          <p className="editorial-script" aria-hidden="true">Натхнення</p>
          <h2 id="gallery-title">Галерея 6MOMENTS</h2>
          <p>Матеріали, деталі та історії, з яких народжуються майбутні реліквії</p>
        </div>
        <div className="gallery-grid">
          <Link className="gallery-tile gallery-tile--large" href="/journal" aria-label="Читати про архітектуру каблучки">
            <Image src="/editorial/journal-ring-architecture.webp" alt="" unoptimized width={1200} height={1200} sizes="(max-width: 700px) 100vw, 40vw" />
            <span>Архітектура світла</span>
          </Link>
          <Link className="gallery-tile" href="/collections" aria-label="Переглянути сережки">
            <Image src="/products/becoming-hoops.webp" alt="" unoptimized width={1200} height={1200} sizes="(max-width: 700px) 50vw, 20vw" />
            <span>Щоденний ритуал</span>
          </Link>
          <Link className="gallery-tile" href="/journal" aria-label="Читати про патину дорогоцінних металів">
            <Image src="/editorial/journal-patina.webp" alt="" unoptimized width={1200} height={1200} sizes="(max-width: 700px) 50vw, 20vw" />
            <span>Краса часу</span>
          </Link>
          <Link className="gallery-tile gallery-tile--wide" href="/products/arrival-pendant" aria-label="Переглянути підвіску Нова глава">
            <Image src="/products/arrival-pendant.webp" alt="" unoptimized width={1200} height={1200} sizes="(max-width: 700px) 100vw, 40vw" />
            <span>Нова глава</span>
          </Link>
        </div>
        <Link className="gallery-link" href="/journal">Відкрити журнал <span aria-hidden="true">↗</span></Link>
        <div className="instagram-callout">
          <div>
            <p className="eyebrow">Instagram</p>
            <h3>Щоденне натхнення 6MOMENTS</h3>
            <p>Нові прикраси, деталі майстерні та особливі релізи — в офіційному профілі бренду.</p>
          </div>
          <a
            className="button button--light"
            href="https://www.instagram.com/6moments_jewelry?igsh=MTdnaHg4eWo0YzlrNQ=="
            target="_blank"
            rel="noreferrer"
          >
            @6moments_jewelry <span aria-hidden="true">↗</span>
          </a>
        </div>
      </section>

      <section className="home-journal" aria-labelledby="home-journal-title">
        <div className="home-journal-heading">
          <div>
            <p className="editorial-script" aria-hidden="true">Журнал</p>
            <h2 id="home-journal-title">Останні історії</h2>
            <p>Нотатки про майстерність, матеріали та моменти, які надають прикрасам особистого значення.</p>
          </div>
          <Link className="text-link" href="/journal">Усі статті <span aria-hidden="true">→</span></Link>
        </div>
        <div className="home-journal-grid">
          {[
            ["Польова нотатка 01", "Тиха архітектура каблучки", "Про посадку, м’який край і пропорції, які залишаються зручними щодня.", "/editorial/journal-ring-architecture.webp"],
            ["Польова нотатка 02", "Краса часу на металі", "Чому патина, догляд і сліди носіння роблять благородний метал по-справжньому особистим.", "/editorial/journal-patina.webp"],
            ["Польова нотатка 03", "Нова мова сімейних реліквій", "Як сучасні ритуали стають історіями, які хочеться передати наступному поколінню.", "/editorial/journal-heirlooms.webp"],
          ].map(([label, title, copy, image]) => (
            <article key={title}>
              <Link href="/journal">
                <div className="home-journal-art">
                  <Image
                    src={image}
                    alt=""
                    unoptimized
                    width={1200}
                    height={1200}
                    sizes="(max-width: 720px) 100vw, 33vw"
                  />
                  <span aria-hidden="true">↗</span>
                </div>
                <p className="eyebrow">{label}</p>
                <h3>{title}</h3>
                <p>{copy}</p>
              </Link>
            </article>
          ))}
        </div>
      </section>
    </main>
  );
}

function ProductDetail({
  product,
  related,
  currency,
  onAdd,
  onOpenCart,
}: {
  product: Product;
  related: Product[];
  currency: CurrencyCode;
  onAdd: (product: Product, options: Record<string, string>) => void;
  onOpenCart: () => void;
}) {
  const initialOptions = defaultOptions(product);
  const [selected, setSelected] = useState<Record<string, string>>(initialOptions);
  const [added, setAdded] = useState(false);
  const [engraving, setEngraving] = useState("");
  const [hintSent, setHintSent] = useState(false);
  const [zoomOpen, setZoomOpen] = useState(false);

  useEffect(() => {
    if (!zoomOpen) return;

    const closeOnEscape = (event: globalThis.KeyboardEvent) => {
      if (event.key === "Escape") setZoomOpen(false);
    };

    document.body.classList.add("product-zoom-is-open");
    window.addEventListener("keydown", closeOnEscape);
    return () => {
      document.body.classList.remove("product-zoom-is-open");
      window.removeEventListener("keydown", closeOnEscape);
    };
  }, [zoomOpen]);

  function addProduct() {
    onAdd(product, engraving ? { ...selected, Гравіювання: engraving } : selected);
    setAdded(true);
  }

  return (
    <main>
      <div className="breadcrumbs">
        <Link href="/collections">Колекція</Link><span aria-hidden="true">/</span><span>{product.title}</span>
      </div>
      <section className="product-detail">
        <div
          className="product-visual"
          onPointerMove={(event) => {
            const bounds = event.currentTarget.getBoundingClientRect();
            const x = ((event.clientX - bounds.left) / bounds.width) * 100;
            const y = ((event.clientY - bounds.top) / bounds.height) * 100;
            event.currentTarget.style.setProperty("--zoom-x", `${x}%`);
            event.currentTarget.style.setProperty("--zoom-y", `${y}%`);
          }}
        >
          <button
            aria-label={`Роздивитися ${product.title} зблизька`}
            className="product-zoom-trigger"
            onClick={() => setZoomOpen(true)}
            type="button"
          >
            <Image
              className="product-photo product-photo--detail"
              src={product.image}
              alt={product.title}
              unoptimized
              width={1200}
              height={1200}
              sizes="(max-width: 900px) 100vw, 58vw"
              priority
            />
            <span className="product-zoom-hint" aria-hidden="true">
              <span>＋</span> Роздивитися деталі
            </span>
          </button>
          <span className="product-visual-caption">{product.moment}</span>
        </div>
        <div className="product-copy">
          <p className="eyebrow">{product.moment}</p>
          <h1>{product.title}</h1>
          <div className="product-status-line">
            <strong className={product.availability === "В наявності" ? "is-stocked" : ""}>{product.availability}</strong>
            <span>Доставка: {product.deliveryDays} днів</span>
            <span>Артикул {product.sku}</span>
          </div>
          <p className="product-price">
            {product.oldPrice && <del>{formatMoney(product.oldPrice, currency)}</del>}
            {formatMoney(product.price, currency)}
          </p>
          <p className="product-description">{product.description}</p>

          <div className="option-groups">
            {product.options.map((option) => (
              <fieldset className="option-group" key={option.name}>
                <legend>
                  {option.name}:
                  <strong>
                    {selected[option.name]}
                    {option.name === "Розмір каблучки" && ringDiameters[selected[option.name]]
                      ? ` · Ø ${ringDiameters[selected[option.name]]} мм`
                      : ""}
                  </strong>
                </legend>
                <div
                  className={`option-values ${
                    option.name === "Розмір каблучки"
                      ? "option-values--sizes"
                      : option.name === "Метал"
                        ? "option-values--metal"
                        : option.name === "Камінь"
                          ? "option-values--stone"
                          : ""
                  }`}
                >
                  {option.values.map((value) => {
                    const diameter = option.name === "Розмір каблучки" ? ringDiameters[value] : undefined;
                    return (
                      <button
                        aria-label={diameter ? `Розмір ${value}, внутрішній діаметр ${diameter} міліметра` : undefined}
                        aria-pressed={selected[option.name] === value}
                        key={value}
                        onClick={() => {
                          setSelected((current) => ({ ...current, [option.name]: value }));
                          setAdded(false);
                        }}
                        type="button"
                      >
                        <span>{value}</span>
                        {diameter && <small>Ø {diameter} мм</small>}
                      </button>
                    );
                  })}
                </div>
              </fieldset>
            ))}
          </div>

          <label className="engraving-field">
            Додаткове гравіювання
            <input
              maxLength={24}
              onChange={(event) => {
                setEngraving(event.target.value);
                setAdded(false);
              }}
              placeholder="Ініціали або пам’ятна дата"
              value={engraving}
            />
            <small>{engraving.length}/24 символи · майстерня підтвердить напис перед виготовленням</small>
          </label>

          <button className="button button--dark add-button" type="button" onClick={addProduct}>
            {added ? "Додано до кошика" : `Додати до кошика — ${formatMoney(product.price, currency)}`}
          </button>
          {added && (
            <button className="view-bag-link" type="button" onClick={onOpenCart}>Переглянути кошик →</button>
          )}
          <ul className="product-details-list">
            {product.details.map((detail) => <li key={detail}>{detail}</li>)}
          </ul>
          <div className="specification-block">
            <h2>Характеристики товару</h2>
            <dl>
              <div><dt>Категорія</dt><dd>{product.category}</dd></div>
              <div><dt>Колір металу</dt><dd>{product.metal}</dd></div>
              <div><dt>Проба</dt><dd>{product.fineness}</dd></div>
              <div><dt>Камінь</dt><dd>{product.stoneType}</dd></div>
              <div><dt>Вага</dt><dd>{product.weight >= 100 ? `${(product.weight / 1000).toFixed(1)} кг` : `${product.weight} г`}</dd></div>
              <div><dt>Загальна каратність</dt><dd>{product.carat ? `${product.carat} карата` : "Без каменів"}</dd></div>
              <div><dt>Кількість каменів</dt><dd>{product.stoneCount}</dd></div>
            </dl>
          </div>
          {["Кільця", "Обручки"].includes(product.category) && (
            <details className="product-accordion">
              <summary>Як визначити розмір каблучки</summary>
              <p>Виміряйте внутрішній діаметр каблучки, яка вам пасує. 15,3 / 15,9 / 16,5 / 17,2 / 17,8 мм відповідають європейським розмірам 48 / 50 / 52 / 54 / 56.</p>
            </details>
          )}
          <details className="product-accordion">
            <summary>Натякнути про подарунок</summary>
            <form
              className="hint-form"
              onSubmit={(event) => {
                event.preventDefault();
                setHintSent(true);
                event.currentTarget.reset();
              }}
            >
              <label>Ваше ім’я<input name="sender" required /></label>
              <label>Email отримувача<input name="recipient" type="email" required /></label>
              <label>Повідомлення<textarea name="message" rows={3} defaultValue={`Я побачив(-ла) ${product.title} і подумав(-ла) про тебе.`} /></label>
              <button className="button button--dark" type="submit">Надіслати делікатний натяк</button>
              {hintSent && <p className="form-success" role="status">Натяк готовий подарувати приємну мить.</p>}
            </form>
          </details>
        </div>
      </section>
      {zoomOpen && (
        <div
          aria-label={`Збільшене фото ${product.title}`}
          aria-modal="true"
          className="product-zoom-dialog"
          onClick={(event) => {
            if (event.target === event.currentTarget) setZoomOpen(false);
          }}
          role="dialog"
        >
          <button
            aria-label="Закрити збільшене фото"
            className="product-zoom-close"
            onClick={() => setZoomOpen(false)}
            type="button"
          >
            ×
          </button>
          <div className="product-zoom-stage">
            <Image
              alt={`${product.title}, збільшений вигляд`}
              className="product-zoom-image"
              height={1600}
              priority
              src={product.image}
              unoptimized
              width={1600}
            />
          </div>
          <p>Натисніть поза фото або Esc, щоб закрити</p>
        </div>
      )}
      {related[0] && (
        <section className="bundle-section">
          <div>
            <p className="eyebrow">Разом вигідніше</p>
            <h2>Продуманий комплект зі знижкою 10%.</h2>
            <p>{product.title} і {related[0].title} будуть оформлені разом у нашому фірмовому пакуванні.</p>
          </div>
          <div className="bundle-price">
            <del>{formatMoney(product.price + related[0].price, currency)}</del>
            <strong>{formatMoney((product.price + related[0].price) * 0.9, currency)}</strong>
            <button
              className="button button--light"
              type="button"
              onClick={() => {
                onAdd(product, selected);
                onAdd(related[0], defaultOptions(related[0]));
                onOpenCart();
              }}
            >
              Додати комплект
            </button>
          </div>
        </section>
      )}
      {related.length > 0 && (
        <section className="moments-section recommendations">
          <div className="section-heading">
            <div><p className="eyebrow">Вам також може сподобатися</p><h2>Обрано для вашого моменту</h2></div>
          </div>
          <ProductGrid products={related} currency={currency} limit={3} onQuickAdd={onAdd} />
        </section>
      )}
    </main>
  );
}

function FilterSelect({
  label,
  value,
  options,
  onChange,
  formatOption,
}: {
  label: string;
  value: string;
  options: string[];
  onChange: (value: string) => void;
  formatOption?: (option: string) => string;
}) {
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);
  const optionRefs = useRef<Array<HTMLButtonElement | null>>([]);
  const listId = useId();
  const selectedIndex = Math.max(0, options.indexOf(value));
  const displayOption = (option: string) => formatOption?.(option) ?? option;

  useEffect(() => {
    function closeOnOutsideClick(event: PointerEvent) {
      if (!rootRef.current?.contains(event.target as Node)) setOpen(false);
    }

    document.addEventListener("pointerdown", closeOnOutsideClick);
    return () => document.removeEventListener("pointerdown", closeOnOutsideClick);
  }, []);

  useEffect(() => {
    if (!open) return;
    requestAnimationFrame(() => optionRefs.current[selectedIndex]?.focus());
  }, [open, selectedIndex]);

  function moveFocus(event: ReactKeyboardEvent<HTMLDivElement>) {
    if (event.key === "Escape") {
      event.preventDefault();
      setOpen(false);
      triggerRef.current?.focus();
      return;
    }

    if (!open || !["ArrowDown", "ArrowUp", "Home", "End"].includes(event.key)) return;
    event.preventDefault();
    const currentIndex = optionRefs.current.findIndex((option) => option === document.activeElement);
    const nextIndex = event.key === "Home"
      ? 0
      : event.key === "End"
        ? options.length - 1
        : event.key === "ArrowDown"
          ? Math.min((currentIndex < 0 ? selectedIndex : currentIndex) + 1, options.length - 1)
          : Math.max((currentIndex < 0 ? selectedIndex : currentIndex) - 1, 0);
    optionRefs.current[nextIndex]?.focus();
  }

  return (
    <div
      className={`filter-field${value !== "Усі" ? " is-active" : ""}${open ? " is-open" : ""}`}
      onKeyDown={moveFocus}
      ref={rootRef}
    >
      <span className="filter-label">{label}</span>
      <button
        aria-controls={listId}
        aria-expanded={open}
        aria-haspopup="listbox"
        className="filter-select-trigger"
        onClick={() => setOpen((current) => !current)}
        ref={triggerRef}
        type="button"
      >
        <span>{displayOption(value)}</span>
        <i aria-hidden="true" />
      </button>
      {open && (
        <div aria-label={label} className="filter-options" id={listId} role="listbox">
          {options.map((option, index) => (
            <button
              aria-selected={option === value}
              className={option === value ? "is-selected" : ""}
              key={option}
              onClick={() => {
                onChange(option);
                setOpen(false);
                requestAnimationFrame(() => triggerRef.current?.focus());
              }}
              ref={(element) => { optionRefs.current[index] = element; }}
              role="option"
              tabIndex={-1}
              type="button"
            >
              <span>{displayOption(option)}</span>
              {option === value && <span aria-hidden="true" className="filter-option-check">✓</span>}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

function CatalogPage({
  products: catalog,
  currency,
  onQuickAdd,
}: {
  products: Product[];
  currency: CurrencyCode;
  onQuickAdd: (product: Product, options: Record<string, string>) => void;
}) {
  const highestPrice = Math.ceil(Math.max(...catalog.map((product) => product.price), 1000) / 100) * 100;
  const [query, setQuery] = useState("");
  const [category, setCategory] = useState("Усі");
  const [moment, setMoment] = useState("Усі");
  const [metal, setMetal] = useState("Усі");
  const [stone, setStone] = useState("Усі");
  const [availability, setAvailability] = useState("Усі");
  const [delivery, setDelivery] = useState("Усі");
  const [maxPrice, setMaxPrice] = useState(highestPrice);
  const [sort, setSort] = useState("popular");
  const [visibleCount, setVisibleCount] = useState(6);
  const [filtersOpen, setFiltersOpen] = useState(false);

  useEffect(() => {
    setFiltersOpen(!window.matchMedia("(max-width: 680px)").matches);
  }, []);

  const unique = (values: string[]) => ["Усі", ...Array.from(new Set(values))];
  const categories = unique(catalog.map((product) => product.category));
  const moments = unique(catalog.map((product) => product.moment));
  const metals = unique(catalog.map((product) => product.metal));
  const stones = unique(catalog.map((product) => product.stoneType));
  const activeFilterCount = [
    category !== "Усі",
    moment !== "Усі",
    metal !== "Усі",
    stone !== "Усі",
    availability !== "Усі",
    delivery !== "Усі",
    maxPrice < highestPrice,
  ].filter(Boolean).length;

  const filtered = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase();
    return catalog
      .filter((product) => (
        (!normalizedQuery
          || product.title.toLowerCase().includes(normalizedQuery)
          || product.sku.toLowerCase().includes(normalizedQuery)
          || product.category.toLowerCase().includes(normalizedQuery)
          || product.moment.toLowerCase().includes(normalizedQuery))
        && (category === "Усі" || product.category === category)
        && (moment === "Усі" || product.moment === moment)
        && (metal === "Усі" || product.metal === metal)
        && (stone === "Усі" || product.stoneType === stone)
        && (availability === "Усі" || product.availability === availability)
        && (delivery === "Усі" || product.deliveryDays === Number(delivery))
        && product.price <= maxPrice
      ))
      .sort((a, b) => (
        sort === "price-asc" ? a.price - b.price
          : sort === "price-desc" ? b.price - a.price
            : sort === "new" ? Number(Boolean(b.isNew)) - Number(Boolean(a.isNew))
              : b.popularity - a.popularity
      ));
  }, [availability, catalog, category, delivery, maxPrice, metal, moment, query, sort, stone]);

  function resetFilters() {
    setQuery("");
    setCategory("Усі");
    setMoment("Усі");
    setMetal("Усі");
    setStone("Усі");
    setAvailability("Усі");
    setDelivery("Усі");
    setMaxPrice(highestPrice);
    setSort("popular");
    setVisibleCount(6);
  }

  return (
    <section className="catalog-shell">
      <div className="catalog-toolbar">
        <label className="catalog-search">
          <span className="sr-only">Пошук у каталозі</span>
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Пошук за назвою, артикулом, категорією або моментом" />
        </label>
        <label className="catalog-sort"><span>Сортування</span>
          <select value={sort} onChange={(event) => setSort(event.target.value)}>
            <option value="popular">За популярністю</option>
            <option value="price-asc">Ціна: від нижчої</option>
            <option value="price-desc">Ціна: від вищої</option>
            <option value="new">Спочатку новинки</option>
          </select>
        </label>
      </div>
      <div className="catalog-layout">
        <details
          className="catalog-filters"
          open={filtersOpen}
          onToggle={(event) => setFiltersOpen(event.currentTarget.open)}
        >
          <summary>
            <span className="filter-summary-title">
              Фільтри
              {activeFilterCount > 0 && <b>{activeFilterCount}</b>}
            </span>
            <span className="filter-result-count">{filtered.length} виробів</span>
          </summary>
          <div className="filter-fields">
            {[
              ["Тип прикраси", category, setCategory, categories],
              ["Момент", moment, setMoment, moments],
              ["Метал", metal, setMetal, metals],
              ["Камінь", stone, setStone, stones],
              ["Наявність", availability, setAvailability, ["Усі", "В наявності", "Під замовлення"]],
              ["Доставка", delivery, setDelivery, ["Усі", "3", "10"]],
            ].map(([label, value, setter, options]) => (
              <FilterSelect
                formatOption={(option) => (
                  label === "Доставка" && option !== "Усі" ? `${option} днів` : option
                )}
                key={label as string}
                label={label as string}
                onChange={setter as (value: string) => void}
                options={options as string[]}
                value={value as string}
              />
            ))}
            <label className={`price-filter${maxPrice < highestPrice ? " is-active" : ""}`}>
              <span className="filter-label">Максимальна ціна</span>
              <strong>{formatMoney(maxPrice, currency)}</strong>
              <input
                aria-label="Максимальна ціна"
                min="500"
                max={highestPrice}
                style={{ "--range-progress": `${((maxPrice - 500) / (highestPrice - 500)) * 100}%` } as React.CSSProperties}
                step="50"
                type="range"
                value={Math.min(maxPrice, highestPrice)}
                onChange={(event) => setMaxPrice(Number(event.target.value))}
              />
              <span className="price-range-labels">
                <span>{formatMoney(500, currency)}</span>
                <span>{formatMoney(highestPrice, currency)}</span>
              </span>
            </label>
            <button
              className="reset-filters"
              disabled={activeFilterCount === 0 && !query}
              type="button"
              onClick={resetFilters}
            >
              <span aria-hidden="true">×</span>
              Скинути всі фільтри
            </button>
          </div>
        </details>
        <div className="catalog-results">
          <div className="results-heading">
            <p><strong>{filtered.length}</strong> виробів знайдено</p>
          </div>
          {filtered.length ? (
            <>
              <ProductGrid products={filtered.slice(0, visibleCount)} currency={currency} onQuickAdd={onQuickAdd} />
              {visibleCount < filtered.length && (
                <button className="button load-more" type="button" onClick={() => setVisibleCount((count) => count + 6)}>
                  Показати ще
                </button>
              )}
            </>
          ) : (
            <div className="no-results">
              <h2>За цими фільтрами нічого не знайдено.</h2>
              <p>Спробуйте інший момент, матеріал або ціну.</p>
              <button className="button" type="button" onClick={resetFilters}>Очистити фільтри</button>
            </div>
          )}
        </div>
      </div>
    </section>
  );
}

function parseCsvRows(text: string) {
  return text
    .replace(/^\uFEFF/, "")
    .split(/\r?\n/)
    .filter((line) => line.trim())
    .map((line) => {
      const cells: string[] = [];
      let cell = "";
      let quoted = false;
      for (let index = 0; index < line.length; index += 1) {
        const character = line[index];
        if (character === '"') {
          if (quoted && line[index + 1] === '"') {
            cell += '"';
            index += 1;
          } else {
            quoted = !quoted;
          }
        } else if (character === "," && !quoted) {
          cells.push(cell.trim());
          cell = "";
        } else {
          cell += character;
        }
      }
      cells.push(cell.trim());
      return cells;
    });
}

const csvColumns = [
  "id", "slug", "sku", "title", "category", "moment", "price", "old_price", "metal", "fineness",
  "stone_type", "availability", "delivery_days", "weight", "carat", "stone_count", "image", "subtitle", "description",
];

function CatalogManager({
  importedProducts,
  onImport,
  onReset,
}: {
  importedProducts: Product[];
  onImport: (products: Product[]) => void;
  onReset: () => void;
}) {
  const [preview, setPreview] = useState<Product[]>([]);
  const [message, setMessage] = useState("");

  async function readCsv(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    if (!file) return;
    const rows = parseCsvRows(await file.text());
    const [headers, ...records] = rows;
    const missing = csvColumns.filter((column) => !headers.includes(column));
    if (missing.length) {
      setMessage(`Відсутні стовпці: ${missing.join(", ")}`);
      setPreview([]);
      return;
    }
    const index = Object.fromEntries(headers.map((header, position) => [header, position]));
    const parsed = records.flatMap((record, position) => {
      const read = (column: string) => record[index[column]] ?? "";
      const title = read("title");
      const price = Number(read("price"));
      if (!title || !price) return [];
      const category = read("category") || "Кільця";
      const metal = read("metal") || "Жовте золото";
      const availabilityValue = read("availability").toLowerCase();
      const imported: Product = {
        id: read("id") || `csv-${Date.now()}-${position}`,
        slug: read("slug") || title.toLowerCase().normalize("NFKD").replace(/[^\p{L}\p{N}]+/gu, "-").replace(/^-|-$/g, ""),
        sku: read("sku") || `CSV-${String(position + 1).padStart(3, "0")}`,
        title,
        category,
        moment: read("moment") || "Момент 06 — Спадщина",
        price,
        oldPrice: Number(read("old_price")) || undefined,
        metal,
        fineness: read("fineness") || "750 / 18k",
        stoneType: read("stone_type") || "Без каменів",
        availability: availabilityValue.includes("order") || availabilityValue.includes("замов") ? "Під замовлення" : "В наявності",
        deliveryDays: Number(read("delivery_days")) || 3,
        weight: Number(read("weight")) || 1,
        carat: Number(read("carat")) || 0,
        stoneCount: Number(read("stone_count")) || 0,
        image: read("image") || "/products/promise-solitaire.webp",
        subtitle: read("subtitle") || `${metal} · ${read("stone_type") || "ювелірний виріб"}`,
        description: read("description") || "Продуманий виріб, імпортований до каталогу 6MOMENTS.",
        popularity: 50,
        isNew: true,
        options: [
          { name: "Метал", values: [metal] },
          ...(["Кільця", "Обручки"].includes(category) ? [{ name: "Розмір каблучки", values: ["48", "50", "52", "54", "56"] }] : []),
        ],
        details: [`Артикул ${read("sku") || `CSV-${position + 1}`}`, `${read("availability") || "В наявності"}`, `Доставка: ${Number(read("delivery_days")) || 3} днів`],
      };
      return [imported];
    });
    setPreview(parsed);
    setMessage(parsed.length ? `${parsed.length} коректних товарів готові до імпорту.` : "Коректних товарів не знайдено.");
  }

  function downloadTemplate() {
    const sample = [
      csvColumns.join(","),
      'sample-ring,sample-ring,6M-RI-100,Зразок каблучки,Кільця,Момент 01 — Обіцянка,1450,1650,Жовте золото,750 / 18k,Лабораторний діамант,В наявності,3,2.4,0.3,1,/products/promise-solitaire.webp,Золото 18 каратів · діамант,Вишуканий зразок товару для імпорту з CSV.',
    ].join("\n");
    const url = window.URL.createObjectURL(new Blob([sample], { type: "text/csv;charset=utf-8" }));
    const anchor = document.createElement("a");
    anchor.href = url;
    anchor.download = "6moments-products-template.csv";
    anchor.click();
    window.URL.revokeObjectURL(url);
  }

  return (
    <main className="admin-page">
      <section className="admin-hero">
        <p className="eyebrow">Робочий простір каталогу</p>
        <h1>Імпортуйте товари без редагування коду.</h1>
        <p>Завантажте структурований CSV, перевірте записи та опублікуйте їх у робочому каталозі цього браузера. Формат готовий до підключення виробничого API.</p>
      </section>
      <section className="admin-panel">
        <div className="admin-upload">
          <div>
            <p className="eyebrow">Крок 01</p>
            <h2>Підготуйте файл</h2>
            <p>Використовуйте CSV у кодуванні UTF-8 із полями, розділеними комами. Slug та ID товарів мають бути унікальними.</p>
          </div>
          <button className="button" type="button" onClick={downloadTemplate}>Завантажити шаблон CSV</button>
        </div>
        <label className="csv-drop">
          <span>Оберіть CSV-файл</span>
          <small>Обов’язкові стовпці перевіряються перед імпортом.</small>
          <input type="file" accept=".csv,text/csv" onChange={readCsv} />
        </label>
        {message && <p className="import-message" role="status">{message}</p>}
        {preview.length > 0 && (
          <>
            <div className="import-preview">
              <table>
                <thead><tr><th>Артикул</th><th>Товар</th><th>Категорія</th><th>Ціна</th><th>Статус</th></tr></thead>
                <tbody>
                  {preview.slice(0, 8).map((product) => (
                    <tr key={product.id}>
                      <td>{product.sku}</td><td>{product.title}</td><td>{product.category}</td>
                      <td>{formatMoney(product.price, "USD")}</td><td>{product.availability}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <button
              className="button button--dark publish-import"
              type="button"
              onClick={() => {
                onImport(preview);
                setMessage(`${preview.length} товарів додано до каталогу вітрини.`);
                setPreview([]);
              }}
            >
              Опублікувати товарів: {preview.length}
            </button>
          </>
        )}
        <div className="imported-summary">
          <div><strong>{importedProducts.length}</strong><span>товарів із CSV збережено</span></div>
          <Link className="text-link" href="/collections">Переглянути вітрину →</Link>
          {importedProducts.length > 0 && <button type="button" onClick={onReset}>Видалити імпортовані товари</button>}
        </div>
      </section>
    </main>
  );
}

function InteriorPage({
  path,
  products: catalog,
  currency,
  onQuickAdd,
}: {
  path: string;
  products: Product[];
  currency: CurrencyCode;
  onQuickAdd: (product: Product, options: Record<string, string>) => void;
}) {
  const page = routeCopy[path] ?? routeCopy["/collections"];
  const [contactSent, setContactSent] = useState(false);

  return (
    <main>
      <section className={`interior-hero interior-hero--${path.slice(1) || "collections"}`}>
        <div>
          <p className="eyebrow">{page.eyebrow}</p>
          <h1>{page.title}</h1>
          <p>{page.copy}</p>
        </div>
        <span className="interior-mark" aria-hidden="true">
          {path === "/about" ? "06" : path === "/journal" ? "J" : path === "/contact" ? "C" : "∞"}
        </span>
      </section>

      {path === "/collections" ? (
        <CatalogPage products={catalog} currency={currency} onQuickAdd={onQuickAdd} />
      ) : path === "/about" ? (
        <>
          <section className="story-panel">
            <p className="story-number">6</p>
            <div>
              <p className="eyebrow">Ідея</p>
              <h2>Одна мова. Багато форм.</h2>
              <p className="chapter-note">Одна мова. Шість розділів.</p>
              <p>
                Наші речі починаються з пропорції та стриманості. Їхній остаточний сенс — і кожна обрана деталь — належать вам.
              </p>
            </div>
          </section>
          <section className="values-grid">
            <article><span>01</span><h3>Менше, але краще</h3><p>Ми створюємо невелику постійну колекцію, а не нескінченну зміну сезонів.</p></article>
            <article><span>02</span><h3>Чесні матеріали</h3><p>Перероблене золото, платина та камені з підтвердженим походженням — для носіння впродовж усього життя.</p></article>
            <article><span>03</span><h3>Створено особистим</h3><p>Масштаб, оздоблення, гравіювання та пропорції підбираються під конкретну людину.</p></article>
          </section>
          <section className="about-quote">
            <p>«Річ — це лише початок.<br />Спогад робить її дорогоцінною».</p>
            <Link className="button button--light" href="/collections">Переглянути колекцію</Link>
          </section>
        </>
      ) : path === "/journal" ? (
        <section className="journal-grid">
          {[
            ["Польова нотатка 01", "Тиха архітектура каблучки", "Чому низька посадка, м’який край і точні пропорції важливі ще довго після першого враження.", "/editorial/journal-ring-architecture.webp"],
            ["Польова нотатка 02", "Речі, що вбирають життя", "Розмова про патину, ремонт і сліди, які перетворюють благородні матеріали на особисті.", "/editorial/journal-patina.webp"],
            ["Польова нотатка 03", "Нова мова сімейних реліквій", "Традиція може мати багато форм. Ми досліджуємо ритуали, які люди обирають для себе.", "/editorial/journal-heirlooms.webp"],
          ].map(([label, title, copy, image]) => (
            <article key={title}>
              <Image
                className="journal-art"
                src={image}
                alt=""
                unoptimized
                width={1200}
                height={1200}
                sizes="(max-width: 720px) 100vw, 33vw"
              />
              <p className="eyebrow">{label}</p>
              <h2>{title}</h2>
              <p>{copy}</p>
              <Link href="/contact">Отримати повну нотатку <span aria-hidden="true">↗</span></Link>
            </article>
          ))}
        </section>
      ) : path === "/diamonds" ? (
        <>
          <section className="diamond-intro">
            <div>
              <p className="eyebrow">Однакова краса</p>
              <h2>Різне походження.<br />Справжня цінність.</h2>
            </div>
            <p>
              Лабораторний діамант не є імітацією: він має ту саму кристалічну
              структуру, оптичні властивості й твердість, що й природний.
              Відмінність полягає у способі та часі формування.
            </p>
          </section>
          <section className="diamond-comparison" aria-labelledby="comparison-title">
            <div className="editorial-heading">
              <p className="editorial-script" aria-hidden="true">Порівняння</p>
              <h2 id="comparison-title">Оберіть свій діамант</h2>
              <p>Обидва варіанти оцінюють за однаковими параметрами 4C: огранювання, колір, чистота й каратність.</p>
            </div>
            <div className="comparison-table">
              <div className="comparison-row comparison-row--head"><strong>Характеристика</strong><strong>Натуральний</strong><strong>Лабораторний</strong></div>
              <div className="comparison-row"><span>Походження</span><span>Сформований природою протягом мільярдів років</span><span>Вирощений у контрольованих умовах за кілька тижнів</span></div>
              <div className="comparison-row"><span>Вигляд і склад</span><span>Діамант із природними особливостями</span><span>Хімічно й оптично ідентичний діамант</span></div>
              <div className="comparison-row"><span>Сертифікація</span><span>Незалежний сертифікат для обраних каменів</span><span>Незалежний сертифікат із позначкою Lab-grown</span></div>
              <div className="comparison-row"><span>Вартість</span><span>Вища через рідкість і видобуток</span><span>Доступніша за однакових характеристик</span></div>
              <div className="comparison-row"><span>Найкраще для</span><span>Традиції, природної рідкості та колекційної цінності</span><span>Більшої каратності в заданому бюджеті та сучасного вибору</span></div>
            </div>
          </section>
          <section className="diamond-faq">
            <div>
              <p className="eyebrow">Часті запитання</p>
              <h2>Коротко про головне</h2>
            </div>
            <div>
              <details open><summary>Лабораторний діамант — це підробка?</summary><p>Ні. Це справжній діамант із тією самою кристалічною структурою та фізичними властивостями. Фіаніт і муасаніт — інші матеріали.</p></details>
              <details><summary>Чи можна відрізнити його на око?</summary><p>Ні. Надійно визначити походження може гемологічна лабораторія за допомогою спеціального обладнання.</p></details>
              <details><summary>Який варіант вигідніший?</summary><p>Лабораторний камінь зазвичай дає змогу обрати більшу каратність або вищі характеристики в тому самому бюджеті. Натуральний цінують за рідкість і природне походження.</p></details>
            </div>
          </section>
          <section className="diamond-cta">
            <div><p className="eyebrow">Готові обрати?</p><h2>Знайдіть діамант для свого моменту.</h2></div>
            <Link className="button button--light" href="/products/promise-solitaire">Переглянути лабораторні діаманти</Link>
          </section>
        </>
      ) : path === "/privacy" ? (
        <section className="contact-layout">
          <div className="contact-details">
            <p className="eyebrow">Ваші дані</p>
            <h2>Прозоро й<br />з повагою.</h2>
            <p>Ми використовуємо контактні дані лише для обробки замовлень, консультацій, доставки та повідомлень, на які ви погодилися.</p>
          </div>
          <div className="contact-details">
            <dl>
              <div><dt>Що зберігаємо</dt><dd>Контактні дані, адресу доставки, склад замовлення та обрані налаштування.</dd></div>
              <div><dt>Файли cookie</dt><dd>Вони допомагають зберегти кошик, валюту та покращити роботу сайту.</dd></div>
              <div><dt>Ваші права</dt><dd>Ви можете попросити доступ, виправлення або видалення даних через atelier@6moments.store.</dd></div>
            </dl>
          </div>
        </section>
      ) : path === "/imprint" ? (
        <section className="contact-layout">
          <div className="contact-details">
            <p className="eyebrow">6MOMENTS</p>
            <h2>Де моменти<br />стають спадщиною.</h2>
            <p>Ювелірний бренд і онлайн-майстерня. Детальні реєстраційні та податкові відомості будуть додані до запуску продажів.</p>
          </div>
          <div className="contact-details">
            <dl>
              <div><dt>Email</dt><dd><a href="mailto:atelier@6moments.store">atelier@6moments.store</a></dd></div>
              <div><dt>Консультації</dt><dd>Онлайн · у всьому світі</dd></div>
              <div><dt>Відповідальність за зміст</dt><dd>6MOMENTS Jewelry</dd></div>
            </dl>
          </div>
        </section>
      ) : (
        <section className="contact-layout">
          <div className="contact-details">
            <p className="eyebrow">Майстерня</p>
            <h2>Особиста розмова<br />у вашому темпі.</h2>
            <p>
              Розкажіть, яку подію ви хочете зберегти, або просто з чого бажаєте почати. Ми особисто відповімо протягом двох робочих днів.
            </p>
            <dl>
              <div><dt>Email</dt><dd><a href="mailto:atelier@6moments.store">atelier@6moments.store</a></dd></div>
              <div><dt>Консультації</dt><dd>Онлайн · у всьому світі</dd></div>
              <div><dt>Години роботи</dt><dd>Понеділок—п’ятниця · 10:00—18:00</dd></div>
            </dl>
          </div>
          <form
            className="contact-form"
            onSubmit={(event) => {
              event.preventDefault();
              setContactSent(true);
              event.currentTarget.reset();
            }}
          >
            <label>Ім’я<input name="name" autoComplete="name" required /></label>
            <label>Email<input name="email" type="email" autoComplete="email" required /></label>
            <label>Чим ми можемо допомогти?
              <select name="subject" defaultValue="Приватна консультація">
                <option>Приватна консультація</option>
                <option>Порада щодо товару та розміру</option>
                <option>Гравіювання та персоналізація</option>
                <option>Догляд і ремонт</option>
              </select>
            </label>
            <label>Розкажіть про свій момент<textarea name="message" rows={5} /></label>
            <button className="button button--dark" type="submit">
              {contactSent ? "Повідомлення отримано" : "Написати майстерні"}
            </button>
            {contactSent && (
              <p className="form-success" role="status">
                Дякуємо. Майстерня особисто відповість протягом двох робочих днів.
              </p>
            )}
          </form>
        </section>
      )}
    </main>
  );
}

function CheckoutResultPage({
  success,
  onClear,
}: {
  success: boolean;
  onClear: () => void;
}) {
  const [status, setStatus] = useState<"loading" | "paid" | "pending" | "error">(
    success ? "loading" : "pending",
  );
  const [orderNumber, setOrderNumber] = useState("");

  useEffect(() => {
    if (!success) return;
    const sessionId = new URLSearchParams(window.location.search).get("session_id");
    if (!sessionId) {
      const timer = window.setTimeout(() => setStatus("error"), 0);
      return () => window.clearTimeout(timer);
    }

    let active = true;
    fetch(`/api/order-status?session_id=${encodeURIComponent(sessionId)}`, { cache: "no-store" })
      .then(async (response) => {
        const data = await response.json() as {
          orderNumber?: string;
          paymentStatus?: string;
          error?: string;
        };
        if (!response.ok) throw new Error(data.error ?? "Не вдалося перевірити оплату.");
        if (!active) return;
        setOrderNumber(data.orderNumber ?? "");
        if (data.paymentStatus === "paid") {
          onClear();
          setStatus("paid");
        } else {
          setStatus("pending");
        }
      })
      .catch(() => {
        if (active) setStatus("error");
      });

    return () => { active = false; };
  }, [onClear, success]);

  return (
    <main className="checkout-result-page">
      <section className="checkout-result-card">
        <span className={`checkout-result-icon ${success ? "" : "is-cancelled"}`} aria-hidden="true">
          {success ? "✓" : "←"}
        </span>
        <p className="eyebrow">
          {success ? "Безпечна оплата Stripe" : "Оплату не завершено"}
        </p>
        <h1>
          {!success
            ? "Ваш вибір збережено."
            : status === "paid"
              ? "Ваш момент уже наш."
              : "Перевіряємо оплату…"}
        </h1>
        {!success ? (
          <p>Кошик залишився без змін. Ви можете повернутися до нього й оплатити, коли будете готові.</p>
        ) : status === "paid" ? (
          <p>
            Замовлення <strong>{orderNumber}</strong> оплачено. Ми надішлемо підтвердження та деталі
            доставки електронною поштою.
          </p>
        ) : status === "error" ? (
          <p>Оплата могла пройти, але підтвердження ще не отримано. Напишіть нам — ми перевіримо її вручну.</p>
        ) : (
          <p>Зазвичай це займає кілька секунд. Підтвердження також надійде електронною поштою.</p>
        )}
        <div className="checkout-result-actions">
          <Link className="button button--dark" href="/collections">Продовжити покупки</Link>
          <Link className="text-link" href="/contact">Потрібна допомога →</Link>
        </div>
      </section>
    </main>
  );
}

function AdminOrdersPage() {
  const [orders, setOrders] = useState<AdminOrder[]>([]);
  const [authenticated, setAuthenticated] = useState<boolean | null>(null);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState("");
  const [expanded, setExpanded] = useState<string | null>(null);

  async function loadOrders() {
    setLoading(true);
    setMessage("");
    try {
      const response = await fetch("/api/admin/orders", { cache: "no-store" });
      const data = await response.json() as {
        orders?: AdminOrder[];
        error?: string;
        authenticated?: boolean;
      };
      if (response.status === 401) {
        setAuthenticated(false);
        setOrders([]);
        return;
      }
      if (!response.ok) throw new Error(data.error ?? "Не вдалося завантажити замовлення.");
      setAuthenticated(true);
      setOrders(data.orders ?? []);
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Не вдалося завантажити замовлення.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    const timer = window.setTimeout(() => void loadOrders(), 0);
    return () => window.clearTimeout(timer);
  }, []);

  async function login(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage("");
    const form = new FormData(event.currentTarget);
    const response = await fetch("/api/admin/login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ password: form.get("password") }),
    });
    const data = await response.json() as { error?: string };
    if (!response.ok) {
      setMessage(data.error ?? "Не вдалося увійти.");
      return;
    }
    await loadOrders();
  }

  async function updateStatus(id: string, status: AdminOrder["fulfillmentStatus"]) {
    const response = await fetch("/api/admin/orders", {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id, status }),
    });
    const data = await response.json() as { order?: AdminOrder; error?: string };
    if (!response.ok || !data.order) {
      setMessage(data.error ?? "Не вдалося змінити статус.");
      return;
    }
    setOrders((current) => current.map((order) => order.id === id ? data.order! : order));
  }

  async function logout() {
    await fetch("/api/admin/logout", { method: "POST" });
    setAuthenticated(false);
    setOrders([]);
  }

  if (authenticated === false) {
    return (
      <main className="admin-page admin-orders-page">
        <section className="admin-login">
          <p className="eyebrow">6MOMENTS · приватна зона</p>
          <h1>Вхід до замовлень</h1>
          <p>Ця сторінка містить персональні дані покупців і доступна лише команді магазину.</p>
          <form onSubmit={login}>
            <label>Пароль адміністратора<input name="password" type="password" autoComplete="current-password" required /></label>
            {message && <p className="admin-error" role="alert">{message}</p>}
            <button className="button button--dark" type="submit">Увійти</button>
          </form>
          {window.location.hostname === "localhost" && (
            <small>Локальний MVP-пароль: sixmoments-demo</small>
          )}
        </section>
      </main>
    );
  }

  return (
    <main className="admin-page admin-orders-page">
      <section className="admin-hero admin-orders-hero">
        <div>
          <p className="eyebrow">Комерційна панель</p>
          <h1>Замовлення 6MOMENTS</h1>
          <p>Оплати Stripe, клієнти, доставка та робочі статуси в одному місці.</p>
        </div>
        <div className="admin-hero-actions">
          <button type="button" className="button" onClick={() => void loadOrders()}>Оновити</button>
          <button type="button" className="text-link" onClick={() => void logout()}>Вийти</button>
        </div>
      </section>
      <section className="orders-panel">
        <div className="orders-summary">
          <article><strong>{orders.length}</strong><span>Усього замовлень</span></article>
          <article><strong>{orders.filter((order) => order.paymentStatus === "paid").length}</strong><span>Оплачено</span></article>
          <article><strong>{orders.filter((order) => order.fulfillmentStatus === "new").length}</strong><span>Нові</span></article>
          <article>
            <strong>{formatMinorMoney(
              orders.filter((order) => order.paymentStatus === "paid" && order.currency === "UAH")
                .reduce((sum, order) => sum + order.subtotal, 0),
              "UAH",
            )}</strong>
            <span>Оплачено в UAH</span>
          </article>
        </div>
        {message && <p className="admin-error" role="alert">{message}</p>}
        {loading ? (
          <div className="orders-empty">Завантажуємо замовлення…</div>
        ) : orders.length === 0 ? (
          <div className="orders-empty">
            <h2>Поки що тихо.</h2>
            <p>Перше створене замовлення одразу з’явиться тут.</p>
          </div>
        ) : (
          <div className="orders-list">
            {orders.map((order) => (
              <article className="order-card" key={order.id}>
                <button
                  className="order-card-main"
                  type="button"
                  onClick={() => setExpanded((current) => current === order.id ? null : order.id)}
                >
                  <span>
                    <strong>{order.orderNumber}</strong>
                    <small>{new Intl.DateTimeFormat("uk-UA", { dateStyle: "medium", timeStyle: "short" }).format(new Date(order.createdAt))}</small>
                  </span>
                  <span><strong>{order.customerName}</strong><small>{order.customerEmail}</small></span>
                  <span><strong>{formatMinorMoney(order.subtotal, order.currency)}</strong><small>{order.items.reduce((sum, item) => sum + item.quantity, 0)} вироби</small></span>
                  <span className={`status-pill payment-${order.paymentStatus}`}>
                    {order.paymentStatus === "paid" ? "Оплачено" : order.paymentStatus === "unpaid" ? "Очікує оплати" : order.paymentStatus === "failed" ? "Помилка оплати" : "Повернено"}
                  </span>
                  <span aria-hidden="true">{expanded === order.id ? "−" : "+"}</span>
                </button>
                {expanded === order.id && (
                  <div className="order-card-details">
                    <div className="order-detail-block">
                      <p className="eyebrow">Склад замовлення</p>
                      {order.items.map((item) => (
                        <div className="admin-order-item" key={`${order.id}-${item.productId}-${JSON.stringify(item.options)}`}>
                          <Image src={item.image} alt="" width={72} height={72} unoptimized />
                          <span>
                            <strong>{item.title} × {item.quantity}</strong>
                            <small>{item.sku} · {Object.values(item.options).join(" · ")}</small>
                          </span>
                          <strong>{formatMinorMoney(item.unitAmount * item.quantity, order.currency)}</strong>
                        </div>
                      ))}
                    </div>
                    <div className="order-detail-block">
                      <p className="eyebrow">Покупець і доставка</p>
                      <address>
                        <strong>{order.customerName}</strong><br />
                        {order.customerEmail}<br />
                        {order.customerPhone && <>{order.customerPhone}<br /></>}
                        {order.address}<br />
                        {order.postalCode}, {order.city}<br />
                        {order.country}
                      </address>
                      <p>{order.deliveryMethod}</p>
                    </div>
                    <div className="order-detail-block">
                      <label>Статус виконання
                        <select
                          value={order.fulfillmentStatus}
                          onChange={(event) => void updateStatus(order.id, event.target.value as AdminOrder["fulfillmentStatus"])}
                        >
                          <option value="new">Нове</option>
                          <option value="processing">У роботі</option>
                          <option value="shipped">Відправлено</option>
                          <option value="completed">Завершено</option>
                          <option value="cancelled">Скасовано</option>
                        </select>
                      </label>
                    </div>
                  </div>
                )}
              </article>
            ))}
          </div>
        )}
      </section>
    </main>
  );
}

function CartDrawer({
  open,
  items,
  currency,
  onClose,
  onQuantity,
  onRemove,
}: {
  open: boolean;
  items: Array<CartItem & { product: Product }>;
  currency: CurrencyCode;
  onClose: () => void;
  onQuantity: (key: string, quantity: number) => void;
  onRemove: (key: string) => void;
}) {
  const [checkout, setCheckout] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [checkoutError, setCheckoutError] = useState("");
  const subtotal = items.reduce((total, item) => total + item.product.price * item.quantity, 0);

  useEffect(() => {
    if (open) document.body.classList.add("cart-open");
    else document.body.classList.remove("cart-open");
    return () => document.body.classList.remove("cart-open");
  }, [open]);

  async function placeOrder(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setCheckoutError("");
    const form = new FormData(event.currentTarget);
    try {
      const response = await fetch("/api/checkout", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          currency,
          items: items.map((item) => ({
            productId: item.productId,
            quantity: item.quantity,
            options: item.options,
          })),
          customer: {
            name: form.get("name"),
            email: form.get("email"),
            phone: form.get("phone"),
            address: form.get("address"),
            city: form.get("city"),
            postalCode: form.get("postal"),
            country: form.get("country"),
            deliveryMethod: form.get("delivery"),
          },
        }),
      });
      const data = await response.json() as { url?: string; error?: string };
      if (!response.ok || !data.url) {
        throw new Error(data.error ?? "Не вдалося відкрити безпечну оплату.");
      }
      window.location.assign(data.url);
    } catch (error) {
      setCheckoutError(error instanceof Error ? error.message : "Не вдалося відкрити безпечну оплату.");
      setSubmitting(false);
    }
  }

  function closeDrawer() {
    onClose();
    window.setTimeout(() => {
      setCheckout(false);
      setCheckoutError("");
      setSubmitting(false);
    }, 250);
  }

  return (
    <>
      <button className={`cart-overlay ${open ? "is-open" : ""}`} aria-label="Закрити кошик" onClick={closeDrawer} type="button" />
      <aside className={`cart-drawer ${open ? "is-open" : ""}`} aria-hidden={!open} aria-label="Кошик">
        <div className="cart-header">
          <div>
            <p className="eyebrow">{checkout ? "Безпечне оформлення" : "Ваш вибір"}</p>
            <h2>{checkout ? "Дані для доставки" : "Кошик"}</h2>
          </div>
          <button type="button" onClick={closeDrawer} aria-label="Закрити кошик">Закрити</button>
        </div>

        {checkout ? (
          <form className="checkout-form" onSubmit={placeOrder}>
            <label>Ім’я та прізвище<input name="name" autoComplete="name" required /></label>
            <label>Email<input name="email" type="email" autoComplete="email" required /></label>
            <label>Телефон<input name="phone" type="tel" autoComplete="tel" placeholder="+380…" /></label>
            <label>Адреса доставки<input name="address" autoComplete="street-address" required /></label>
            <div className="checkout-row">
              <label>Місто<input name="city" autoComplete="address-level2" required /></label>
              <label>Поштовий індекс<input name="postal" autoComplete="postal-code" required /></label>
            </div>
            <label>Країна
              <select name="country" defaultValue="Україна">
                <option>Україна</option>
                <option>Європейський Союз</option>
                <option>Велика Британія</option>
                <option>Сполучені Штати</option>
                <option>Інші країни</option>
              </select>
            </label>
            <label>Служба доставки
              <select name="delivery" defaultValue="DHL Express · застраховано">
                <option>DHL Express · застраховано</option>
                <option>DPD Classic · застраховано</option>
              </select>
            </label>
            <label className="payment-choice">
              <input type="radio" name="payment" defaultChecked />
              <span><strong>Безпечна оплата карткою</strong><small>Stripe · Visa · Mastercard · Apple Pay · Google Pay</small></span>
            </label>
            <div className="checkout-total"><span>Разом</span><strong>{formatMoney(subtotal, currency)}</strong></div>
            {checkoutError && <p className="checkout-error" role="alert">{checkoutError}</p>}
            <button className="button button--dark checkout-button" type="submit" disabled={submitting}>
              {submitting ? "Переходимо до Stripe…" : "Оплатити безпечно карткою"}
            </button>
            <p className="checkout-security">Дані картки вводяться лише на захищеній сторінці Stripe і не зберігаються на 6MOMENTS.</p>
            <button className="back-button" type="button" onClick={() => setCheckout(false)}>← Повернутися до кошика</button>
          </form>
        ) : items.length === 0 ? (
          <div className="empty-cart">
            <p>Ваш кошик чекає на річ зі змістом.</p>
            <Link className="button button--dark" href="/collections">Переглянути колекцію</Link>
          </div>
        ) : (
          <>
            <div className="cart-items">
              {items.map((item) => (
                <article className="cart-item" key={item.key}>
                  <div className="cart-item-art">
                    <Image
                      className="product-photo product-photo--cart"
                      src={item.product.image}
                      alt=""
                      unoptimized
                      width={240}
                      height={240}
                      sizes="120px"
                    />
                  </div>
                  <div className="cart-item-copy">
                    <div className="cart-item-title">
                      <h3>{item.product.title}</h3>
                      <strong>{formatMoney(item.product.price * item.quantity, currency)}</strong>
                    </div>
                    <p>{Object.entries(item.options).map(([name, value]) => `${name}: ${value}`).join(" · ")}</p>
                    <div className="cart-item-actions">
                      <div className="quantity" aria-label={`Кількість товару ${item.product.title}`}>
                        <button type="button" onClick={() => onQuantity(item.key, item.quantity - 1)} aria-label="Зменшити кількість">−</button>
                        <span>{item.quantity}</span>
                        <button type="button" onClick={() => onQuantity(item.key, item.quantity + 1)} aria-label="Збільшити кількість">+</button>
                      </div>
                      <button className="remove-item" type="button" onClick={() => onRemove(item.key)}>Видалити</button>
                    </div>
                  </div>
                </article>
              ))}
            </div>
            <div className="cart-summary">
              <div><span>Проміжний підсумок</span><strong>{formatMoney(subtotal, currency)}</strong></div>
              <p>Застрахована доставка та повернення — безкоштовні.</p>
              <button className="button button--dark checkout-button" type="button" onClick={() => setCheckout(true)}>Перейти до оформлення</button>
            </div>
          </>
        )}
      </aside>
    </>
  );
}

function Footer() {
  const [subscribed, setSubscribed] = useState(false);

  return (
    <footer className="site-footer">
      <div className="footer-signup">
        <div className="footer-signup-inner">
          <div className="footer-signup-heading">
            <p className="eyebrow">
              <span aria-hidden="true" />
              Приватний список 6MOMENTS
            </p>
            <h2>
              Ваш перший момент — <em>особливий.</em>
            </h2>
            <p className="footer-signup-copy">
              Приєднуйтеся до нашого приватного списку й отримайте 15% на перше замовлення.
              Лише нові колекції, історії з майстерні та обрані пропозиції.
            </p>
          </div>

          <div className="footer-signup-card">
            <div className="footer-offer-heading">
              <span className="footer-mail-icon" aria-hidden="true">✉︎</span>
              <div className="footer-offer-copy">
                <p>Привітальний подарунок</p>
                <span>На перше замовлення</span>
              </div>
              <strong>−15%</strong>
            </div>
            <form
              onSubmit={(event) => {
                event.preventDefault();
                setSubscribed(true);
                event.currentTarget.reset();
              }}
            >
              <label className="sr-only" htmlFor="email">Електронна адреса</label>
              <input id="email" name="email" placeholder="Ваша електронна адреса" type="email" required />
              <button type="submit">
                <span>{subscribed ? "Готово" : "Приєднатися"}</span>
                <span aria-hidden="true">{subscribed ? "✓" : "↗"}</span>
              </button>
            </form>
            <p className="footer-signup-note">
              Персональний код надійде на вашу пошту. Відписатися можна будь-коли.
            </p>
            {subscribed && <p className="signup-success" role="status">Дякуємо. Ваш персональний код уже в дорозі.</p>}
          </div>
        </div>
      </div>

      <div className="footer-main">
        <div className="footer-brand">
          <Link className="wordmark wordmark--footer" href="/">6MOMENTS</Link>
          <p>Сучасні реліквії із золота, платини та діамантів для моментів, які хочеться зберегти.</p>
          <ul>
            <li><span aria-hidden="true">◇</span> Приватні консультації онлайн</li>
            <li><span aria-hidden="true">◎</span> Застрахована доставка по світу</li>
            <li><span aria-hidden="true">✉</span> <a href="mailto:atelier@6moments.store">atelier@6moments.store</a></li>
          </ul>
          <div className="footer-socials" aria-label="Соціальні мережі">
            <a href="https://www.instagram.com/6moments_jewelry?igsh=MTdnaHg4eWo0YzlrNQ==" target="_blank" rel="noreferrer" aria-label="Instagram 6MOMENTS">IG</a>
            <a href="https://www.pinterest.com/" target="_blank" rel="noreferrer" aria-label="Pinterest">P</a>
            <Link href="/journal" aria-label="Журнал 6MOMENTS">J</Link>
          </div>
        </div>

        <nav className="footer-column" aria-label="Про 6MOMENTS">
          <h3>Про 6MOMENTS</h3>
          <Link href="/about">Наша історія</Link>
          <Link href="/about">Матеріали й майстерність</Link>
          <Link href="/diamonds">Лабораторні діаманти</Link>
          <Link href="/journal">Журнал</Link>
          <Link href="/contact">Приватна консультація</Link>
        </nav>
        <nav className="footer-column" aria-label="Допомога покупцям">
          <h3>Допомога</h3>
          <Link href="/contact">Доставка й повернення</Link>
          <Link href="/contact">Розмір каблучки</Link>
          <Link href="/contact">Догляд за прикрасами</Link>
          <Link href="/contact">Поширені запитання</Link>
        </nav>
        <nav className="footer-column" aria-label="Каталог">
          <h3>Каталог</h3>
          <Link href="/collections">Каблучки</Link>
          <Link href="/collections">Підвіски</Link>
          <Link href="/collections">Сережки</Link>
          <Link href="/collections">Усі прикраси</Link>
        </nav>
      </div>

      <div className="footer-bottom">
        <p>© {new Date().getFullYear()} 6MOMENTS. Усі права захищені.</p>
        <nav aria-label="Юридична інформація">
          <Link href="/privacy">Конфіденційність</Link>
          <Link href="/imprint">Юридична інформація</Link>
        </nav>
        <p>Створено для моментів, що залишаються</p>
      </div>
    </footer>
  );
}

function WelcomeOverlays() {
  const [newsletterOpen, setNewsletterOpen] = useState(false);
  const [cookieOpen, setCookieOpen] = useState(false);
  const [neverShowAgain, setNeverShowAgain] = useState(false);
  const [subscribed, setSubscribed] = useState(false);

  useEffect(() => {
    const cookieAccepted = window.localStorage.getItem("6moments-cookie-consent") === "accepted";
    const newsletterDismissed = window.localStorage.getItem("6moments-early-access-dismissed") === "true";
    const newsletterCompleted = window.localStorage.getItem("6moments-early-access-complete") === "true";

    const cookieTimer = window.setTimeout(() => setCookieOpen(!cookieAccepted), 0);
    const newsletterTimer = !newsletterDismissed && !newsletterCompleted
      ? window.setTimeout(() => setNewsletterOpen(true), 350)
      : undefined;
    return () => {
      window.clearTimeout(cookieTimer);
      if (newsletterTimer) window.clearTimeout(newsletterTimer);
    };
  }, []);

  useEffect(() => {
    if (!newsletterOpen) return;

    document.body.classList.add("welcome-open");
    const closeOnEscape = (event: globalThis.KeyboardEvent) => {
      if (event.key === "Escape") closeNewsletter();
    };
    window.addEventListener("keydown", closeOnEscape);
    return () => {
      document.body.classList.remove("welcome-open");
      window.removeEventListener("keydown", closeOnEscape);
    };
  });

  function closeNewsletter() {
    if (neverShowAgain) {
      window.localStorage.setItem("6moments-early-access-dismissed", "true");
    }
    setNewsletterOpen(false);
  }

  function submitNewsletter(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    window.localStorage.setItem("6moments-early-access-complete", "true");
    setSubscribed(true);
    window.setTimeout(() => setNewsletterOpen(false), 1200);
  }

  function acceptCookies() {
    window.localStorage.setItem("6moments-cookie-consent", "accepted");
    setCookieOpen(false);
  }

  return (
    <>
      {newsletterOpen && (
        <div
          className="welcome-overlay"
          role="presentation"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) closeNewsletter();
          }}
        >
          <section
            className="welcome-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="welcome-title"
          >
            <button
              className="welcome-close"
              type="button"
              onClick={closeNewsletter}
              aria-label="Закрити запрошення до раннього доступу"
            >
              <span aria-hidden="true">×</span>
            </button>
            <div className="welcome-image" aria-hidden="true" />
            <div className="welcome-copy">
              <h2 id="welcome-title">Відкрийте ранній доступ</h2>
              <p>
                Дізнавайтеся першими про нові колекції, ексклюзивні релізи
                та приватні пропозиції для нашої спільноти. Отримайте{" "}
                <strong>безкоштовну доставку</strong> для першої покупки.
              </p>
              {subscribed ? (
                <p className="welcome-success" role="status">
                  Ласкаво просимо до нашого кола.
                </p>
              ) : (
                <form className="welcome-form" onSubmit={submitNewsletter}>
                  <label className="sr-only" htmlFor="welcome-email">Електронна адреса</label>
                  <input
                    id="welcome-email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    placeholder="Електронна адреса"
                    autoFocus
                    required
                  />
                  <button type="submit" aria-label="Приєднатися до списку раннього доступу">
                    <span aria-hidden="true">↗</span>
                  </button>
                </form>
              )}
            </div>
            <label className="welcome-never">
              <input
                type="checkbox"
                checked={neverShowAgain}
                onChange={(event) => setNeverShowAgain(event.target.checked)}
              />
              <span>Більше не показувати</span>
            </label>
          </section>
        </div>
      )}

      {cookieOpen && (
        <aside className="cookie-banner" aria-label="Повідомлення про файли cookie">
          <span className="cookie-icon" aria-hidden="true">●</span>
          <p>
            Ми використовуємо файли cookie та подібні технології, щоб покращити
            роботу сайту й ваш досвід перегляду. Докладніше — у нашій{" "}
            <Link href="/privacy">Політиці конфіденційності</Link>.
          </p>
          <button type="button" onClick={acceptCookies} aria-label="Прийняти файли cookie">OK</button>
        </aside>
      )}
    </>
  );
}

export function Storefront({ path }: { path: string }) {
  const [cart, setCart] = useState<CartItem[]>([]);
  const [cartOpen, setCartOpen] = useState(false);
  const [cartLoaded, setCartLoaded] = useState(false);
  const [currency, setCurrency] = useState<CurrencyCode>("UAH");
  const [importedProducts, setImportedProducts] = useState<Product[]>([]);
  const [catalogLoaded, setCatalogLoaded] = useState(false);
  const clearCart = useCallback(() => setCart([]), []);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      try {
        const saved = window.localStorage.getItem("6moments-cart");
        if (saved) setCart(JSON.parse(saved) as CartItem[]);
        const savedCurrency = window.localStorage.getItem("6moments-currency") as CurrencyCode | null;
        if (savedCurrency && savedCurrency in currencyRates) setCurrency(savedCurrency);
        const savedProducts = window.localStorage.getItem("6moments-imported-products");
        if (savedProducts) setImportedProducts(JSON.parse(savedProducts) as Product[]);
      } catch {
        window.localStorage.removeItem("6moments-cart");
        window.localStorage.removeItem("6moments-imported-products");
      }
      setCartLoaded(true);
      setCatalogLoaded(true);
    }, 0);
    return () => window.clearTimeout(timer);
  }, []);

  useEffect(() => {
    if (cartLoaded) window.localStorage.setItem("6moments-cart", JSON.stringify(cart));
  }, [cart, cartLoaded]);

  useEffect(() => {
    window.localStorage.setItem("6moments-currency", currency);
  }, [currency]);

  useEffect(() => {
    if (catalogLoaded) window.localStorage.setItem("6moments-imported-products", JSON.stringify(importedProducts));
  }, [catalogLoaded, importedProducts]);

  const catalog = useMemo(() => {
    const importedIds = new Set(importedProducts.map((product) => product.id));
    return [...products.filter((product) => !importedIds.has(product.id)), ...importedProducts];
  }, [importedProducts]);

  const detailedItems = useMemo(
    () =>
      cart.flatMap((item) => {
        const product = catalog.find((candidate) => candidate.id === item.productId);
        return product ? [{ ...item, product }] : [];
      }),
    [cart, catalog],
  );
  const count = cart.reduce((total, item) => total + item.quantity, 0);
  const legacyProductPaths: Record<string, string> = {
    "/collections/the-promise": "promise-solitaire",
    "/collections/the-union": "union-band",
    "/collections/the-arrival": "arrival-pendant",
    "/collections/the-becoming": "becoming-hoops",
    "/collections/the-gratitude": "gratitude-bracelet",
    "/collections/the-legacy": "legacy-signet",
  };
  const requestedProductSlug = path.startsWith("/products/")
    ? path.replace("/products/", "")
    : legacyProductPaths[path];
  const product = requestedProductSlug
    ? catalog.find((candidate) => candidate.slug === requestedProductSlug)
    : undefined;

  function addToCart(selectedProduct: Product, options: Record<string, string>) {
    const key = `${selectedProduct.id}:${Object.entries(options).map(([name, value]) => `${name}=${value}`).join("|")}`;
    setCart((current) => {
      const existing = current.find((item) => item.key === key);
      if (existing) {
        return current.map((item) => item.key === key ? { ...item, quantity: item.quantity + 1 } : item);
      }
      return [...current, { key, productId: selectedProduct.id, quantity: 1, options }];
    });
  }

  return (
    <div className="site-shell">
      <Header
        path={path}
        count={count}
        currency={currency}
        onCurrency={setCurrency}
        onOpenCart={() => setCartOpen(true)}
      />
      {product ? (
        <ProductDetail
          product={product}
          related={catalog.filter((candidate) => candidate.id !== product.id && (candidate.moment === product.moment || candidate.category !== product.category)).slice(0, 3)}
          currency={currency}
          onAdd={addToCart}
          onOpenCart={() => setCartOpen(true)}
        />
      ) : path === "/" ? (
        <HomePage products={catalog} currency={currency} onQuickAdd={addToCart} />
      ) : path === "/checkout/success" ? (
        <CheckoutResultPage success onClear={clearCart} />
      ) : path === "/checkout/cancelled" ? (
        <CheckoutResultPage success={false} onClear={clearCart} />
      ) : path === "/admin/orders" ? (
        <AdminOrdersPage />
      ) : path === "/admin/catalog" ? (
        <CatalogManager
          importedProducts={importedProducts}
          onImport={(incoming) => {
            setImportedProducts((current) => {
              const incomingIds = new Set(incoming.map((product) => product.id));
              return [...current.filter((product) => !incomingIds.has(product.id)), ...incoming];
            });
          }}
          onReset={() => setImportedProducts([])}
        />
      ) : (
        <InteriorPage path={path} products={catalog} currency={currency} onQuickAdd={addToCart} />
      )}
      <Footer />
      <CartDrawer
        open={cartOpen}
        items={detailedItems}
        currency={currency}
        onClose={() => setCartOpen(false)}
        onQuantity={(key, quantity) => {
          if (quantity < 1) setCart((current) => current.filter((item) => item.key !== key));
          else setCart((current) => current.map((item) => item.key === key ? { ...item, quantity } : item));
        }}
        onRemove={(key) => setCart((current) => current.filter((item) => item.key !== key))}
      />
      <WelcomeOverlays />
    </div>
  );
}
