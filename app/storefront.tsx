"use client";

import { ChangeEvent, FormEvent, useEffect, useMemo, useState } from "react";
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
};

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
        <details className="mobile-menu">
          <summary aria-label="Відкрити навігацію">Меню</summary>
          <nav aria-label="Мобільна навігація">
            <Link href="/collections">Каталог</Link>
            <Link href="/about">Про нас</Link>
            <Link href="/journal">Журнал</Link>
            <Link href="/admin/catalog">Керування каталогом</Link>
            <Link href="/contact">Приватна консультація</Link>
            <label className="mobile-currency">Валюта
              <select value={currency} onChange={(event) => onCurrency(event.target.value as CurrencyCode)}>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="CZK">CZK</option>
                <option value="UAH">UAH</option>
              </select>
            </label>
            <button type="button" onClick={onOpenCart}>Кошик ({count})</button>
          </nav>
        </details>
      </header>
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
  return (
    <main>
      <section className="hero" aria-labelledby="hero-title">
        <div className="hero-copy">
          <p className="eyebrow">Вишукані речі для життя зі змістом</p>
          <h1 id="hero-title">Де моменти стають спадщиною.</h1>
          <p>
            Сім знакових речей із довговічних матеріалів, створених для людини, яка нестиме їхню історію далі.
          </p>
          <div className="hero-actions">
            <Link className="button button--dark" href="/collections">Переглянути колекцію</Link>
            <Link className="text-link" href="/about">Наша філософія <span aria-hidden="true">→</span></Link>
          </div>
        </div>
      </section>

      <section className="intro">
        <p className="eyebrow">Шість моментів · одне спеціальне видання</p>
        <h2>Оберіть деталі, що зроблять річ вашою.</h2>
        <p className="intro-copy">
          Оберіть матеріал, розмір, оздоблення або практичну характеристику. Кожен вибір збережеться в кошику та замовленні.
        </p>
      </section>

      <section className="moments-section" aria-labelledby="products-title">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Колекція</p>
            <h2 id="products-title">Речі, які залишаються</h2>
          </div>
          <Link className="text-link" href="/collections">Переглянути всі товари <span aria-hidden="true">→</span></Link>
        </div>
        <ProductGrid products={catalog} currency={currency} limit={6} onQuickAdd={onQuickAdd} />
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
        <div className="product-visual">
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
                <legend>{option.name}: <strong>{selected[option.name]}</strong></legend>
                <div className="option-values">
                  {option.values.map((value) => (
                    <button
                      aria-pressed={selected[option.name] === value}
                      key={value}
                      onClick={() => {
                        setSelected((current) => ({ ...current, [option.name]: value }));
                        setAdded(false);
                      }}
                      type="button"
                    >
                      {value}
                    </button>
                  ))}
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
  const [category, setCategory] = useState("All");
  const [moment, setMoment] = useState("All");
  const [metal, setMetal] = useState("All");
  const [stone, setStone] = useState("All");
  const [availability, setAvailability] = useState("All");
  const [delivery, setDelivery] = useState("All");
  const [maxPrice, setMaxPrice] = useState(highestPrice);
  const [sort, setSort] = useState("popular");
  const [visibleCount, setVisibleCount] = useState(6);

  const unique = (values: string[]) => ["All", ...Array.from(new Set(values))];
  const categories = unique(catalog.map((product) => product.category));
  const moments = unique(catalog.map((product) => product.moment));
  const metals = unique(catalog.map((product) => product.metal));
  const stones = unique(catalog.map((product) => product.stoneType));

  const filtered = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase();
    return catalog
      .filter((product) => (
        (!normalizedQuery
          || product.title.toLowerCase().includes(normalizedQuery)
          || product.sku.toLowerCase().includes(normalizedQuery)
          || product.category.toLowerCase().includes(normalizedQuery)
          || product.moment.toLowerCase().includes(normalizedQuery))
        && (category === "All" || product.category === category)
        && (moment === "All" || product.moment === moment)
        && (metal === "All" || product.metal === metal)
        && (stone === "All" || product.stoneType === stone)
        && (availability === "All" || product.availability === availability)
        && (delivery === "All" || product.deliveryDays === Number(delivery))
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
    setCategory("All");
    setMoment("All");
    setMetal("All");
    setStone("All");
    setAvailability("All");
    setDelivery("All");
    setMaxPrice(highestPrice);
    setSort("popular");
    setVisibleCount(6);
  }

  return (
    <section className="catalog-shell">
      <div className="catalog-toolbar">
        <label className="catalog-search">
          <span className="sr-only">Search catalog</span>
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search by name, SKU, category or moment" />
        </label>
        <label className="catalog-sort">Sort
          <select value={sort} onChange={(event) => setSort(event.target.value)}>
            <option value="popular">Most popular</option>
            <option value="price-asc">Price: low to high</option>
            <option value="price-desc">Price: high to low</option>
            <option value="new">Newest first</option>
          </select>
        </label>
      </div>
      <div className="catalog-layout">
        <details className="catalog-filters" open>
          <summary>Filters <span>{filtered.length} pieces</span></summary>
          <div className="filter-fields">
            {[
              ["Jewelry type", category, setCategory, categories],
              ["Moment", moment, setMoment, moments],
              ["Metal", metal, setMetal, metals],
              ["Stone", stone, setStone, stones],
              ["Availability", availability, setAvailability, ["All", "In stock", "Made to order"]],
              ["Delivery", delivery, setDelivery, ["All", "3", "10"]],
            ].map(([label, value, setter, options]) => (
              <label key={label as string}>{label as string}
                <select
                  value={value as string}
                  onChange={(event) => (setter as (value: string) => void)(event.target.value)}
                >
                  {(options as string[]).map((option) => (
                    <option value={option} key={option}>
                      {label === "Delivery" && option !== "All" ? `${option} days` : option}
                    </option>
                  ))}
                </select>
              </label>
            ))}
            <label className="price-filter">Maximum price
              <strong>{formatMoney(maxPrice, currency)}</strong>
              <input
                min="500"
                max={highestPrice}
                step="50"
                type="range"
                value={Math.min(maxPrice, highestPrice)}
                onChange={(event) => setMaxPrice(Number(event.target.value))}
              />
            </label>
            <button className="reset-filters" type="button" onClick={resetFilters}>Reset all filters</button>
          </div>
        </details>
        <div className="catalog-results">
          <div className="results-heading">
            <p><strong>{filtered.length}</strong> pieces found</p>
            <Link href="/admin/catalog">Import or manage catalog →</Link>
          </div>
          {filtered.length ? (
            <>
              <ProductGrid products={filtered.slice(0, visibleCount)} currency={currency} onQuickAdd={onQuickAdd} />
              {visibleCount < filtered.length && (
                <button className="button load-more" type="button" onClick={() => setVisibleCount((count) => count + 6)}>
                  Show more
                </button>
              )}
            </>
          ) : (
            <div className="no-results">
              <h2>No pieces match these filters.</h2>
              <p>Try a different moment, material or price.</p>
              <button className="button" type="button" onClick={resetFilters}>Clear filters</button>
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
      setMessage(`Missing columns: ${missing.join(", ")}`);
      setPreview([]);
      return;
    }
    const index = Object.fromEntries(headers.map((header, position) => [header, position]));
    const parsed = records.flatMap((record, position) => {
      const read = (column: string) => record[index[column]] ?? "";
      const title = read("title");
      const price = Number(read("price"));
      if (!title || !price) return [];
      const category = read("category") || "Rings";
      const metal = read("metal") || "Yellow gold";
      const imported: Product = {
        id: read("id") || `csv-${Date.now()}-${position}`,
        slug: read("slug") || title.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, ""),
        sku: read("sku") || `CSV-${String(position + 1).padStart(3, "0")}`,
        title,
        category,
        moment: read("moment") || "Moment 06 — The Legacy",
        price,
        oldPrice: Number(read("old_price")) || undefined,
        metal,
        fineness: read("fineness") || "750 / 18k",
        stoneType: read("stone_type") || "Without stones",
        availability: read("availability").toLowerCase().includes("order") ? "Made to order" : "In stock",
        deliveryDays: Number(read("delivery_days")) || 3,
        weight: Number(read("weight")) || 1,
        carat: Number(read("carat")) || 0,
        stoneCount: Number(read("stone_count")) || 0,
        image: read("image") || "/products/promise-solitaire.webp",
        subtitle: read("subtitle") || `${metal} · ${read("stone_type") || "fine jewelry"}`,
        description: read("description") || "A considered piece imported into the 6MOMENTS catalog.",
        popularity: 50,
        isNew: true,
        options: [
          { name: "Metal", values: [metal] },
          ...(category.includes("Ring") ? [{ name: "Ring size", values: ["48", "50", "52", "54", "56"] }] : []),
        ],
        details: [`SKU ${read("sku") || `CSV-${position + 1}`}`, `${read("availability") || "In stock"}`, `Delivery in ${Number(read("delivery_days")) || 3} days`],
      };
      return [imported];
    });
    setPreview(parsed);
    setMessage(parsed.length ? `${parsed.length} valid products are ready to import.` : "No valid products were found.");
  }

  function downloadTemplate() {
    const sample = [
      csvColumns.join(","),
      'sample-ring,sample-ring,6M-RI-100,Sample Ring,Rings,Moment 01 — The Promise,1450,1650,Yellow gold,750 / 18k,Lab-grown diamond,In stock,3,2.4,0.3,1,/products/promise-solitaire.webp,18k gold · diamond,A refined sample product imported from CSV.',
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
        <p className="eyebrow">Catalog workspace</p>
        <h1>Import products without touching code.</h1>
        <p>Upload a structured CSV, verify every record, and publish it to this browser’s working catalog. The format is ready to connect to the production API.</p>
      </section>
      <section className="admin-panel">
        <div className="admin-upload">
          <div>
            <p className="eyebrow">Step 01</p>
            <h2>Prepare the file</h2>
            <p>Use UTF-8 CSV with comma-separated fields. Product slugs and IDs must be unique.</p>
          </div>
          <button className="button" type="button" onClick={downloadTemplate}>Download CSV template</button>
        </div>
        <label className="csv-drop">
          <span>Choose a CSV file</span>
          <small>Required columns are checked before import.</small>
          <input type="file" accept=".csv,text/csv" onChange={readCsv} />
        </label>
        {message && <p className="import-message" role="status">{message}</p>}
        {preview.length > 0 && (
          <>
            <div className="import-preview">
              <table>
                <thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Price</th><th>Status</th></tr></thead>
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
                setMessage(`${preview.length} products were added to the storefront catalog.`);
                setPreview([]);
              }}
            >
              Publish {preview.length} products
            </button>
          </>
        )}
        <div className="imported-summary">
          <div><strong>{importedProducts.length}</strong><span>CSV products currently stored</span></div>
          <Link className="text-link" href="/collections">View storefront →</Link>
          {importedProducts.length > 0 && <button type="button" onClick={onReset}>Remove imported products</button>}
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
              <p className="eyebrow">The idea</p>
              <h2>One language. Many forms.</h2>
              <p className="chapter-note">One language. Six chapters.</p>
              <p>
                Our pieces begin with proportion and restraint. Their final meaning—and every detail chosen along the way—is yours to give.
              </p>
            </div>
          </section>
          <section className="values-grid">
            <article><span>01</span><h3>Fewer, better</h3><p>We design a small permanent collection, not a stream of seasons.</p></article>
            <article><span>02</span><h3>Honest materials</h3><p>Recycled gold, platinum and traceable stones chosen for a lifetime of wear.</p></article>
            <article><span>03</span><h3>Made personal</h3><p>Scale, finish, engraving and proportion are resolved around the person.</p></article>
          </section>
          <section className="about-quote">
            <p>“The object is only the beginning.<br />The memory is what makes it precious.”</p>
            <Link className="button button--light" href="/collections">Explore the collection</Link>
          </section>
        </>
      ) : path === "/journal" ? (
        <section className="journal-grid">
          {[
            ["Field note 01", "The quiet architecture of a ring", "Why a low setting, softened edge and careful proportion matter long after the first impression.", "/editorial/journal-ring-architecture.webp"],
            ["Field note 02", "Objects that gather a life", "A conversation about patina, repair and the marks that turn fine materials into personal ones.", "/editorial/journal-patina.webp"],
            ["Field note 03", "A new language for heirlooms", "Tradition can hold many shapes. We look at the rituals people are choosing for themselves.", "/editorial/journal-heirlooms.webp"],
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
              <Link href="/contact">Request the full note <span aria-hidden="true">↗</span></Link>
            </article>
          ))}
        </section>
      ) : (
        <section className="contact-layout">
          <div className="contact-details">
            <p className="eyebrow">The atelier</p>
            <h2>A private conversation,<br />at your pace.</h2>
            <p>
              Tell us what you are marking, or simply where you would like to begin. We reply personally within two working days.
            </p>
            <dl>
              <div><dt>Email</dt><dd><a href="mailto:atelier@6moments.store">atelier@6moments.store</a></dd></div>
              <div><dt>Consultations</dt><dd>Online · Worldwide</dd></div>
              <div><dt>Hours</dt><dd>Monday—Friday · 10:00—18:00</dd></div>
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
            <label>Name<input name="name" autoComplete="name" required /></label>
            <label>Email<input name="email" type="email" autoComplete="email" required /></label>
            <label>What can we help with?
              <select name="subject" defaultValue="A private appointment">
                <option>A private appointment</option>
                <option>Product and sizing advice</option>
                <option>Engraving and personalisation</option>
                <option>Aftercare and repair</option>
              </select>
            </label>
            <label>Tell us about your moment<textarea name="message" rows={5} /></label>
            <button className="button button--dark" type="submit">
              {contactSent ? "Message received" : "Write to the atelier"}
            </button>
            {contactSent && (
              <p className="form-success" role="status">
                Thank you. The atelier will reply personally within two working days.
              </p>
            )}
          </form>
        </section>
      )}
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
  onClear,
}: {
  open: boolean;
  items: Array<CartItem & { product: Product }>;
  currency: CurrencyCode;
  onClose: () => void;
  onQuantity: (key: string, quantity: number) => void;
  onRemove: (key: string) => void;
  onClear: () => void;
}) {
  const [checkout, setCheckout] = useState(false);
  const [orderNumber, setOrderNumber] = useState("");
  const subtotal = items.reduce((total, item) => total + item.product.price * item.quantity, 0);

  useEffect(() => {
    if (open) document.body.classList.add("cart-open");
    else document.body.classList.remove("cart-open");
    return () => document.body.classList.remove("cart-open");
  }, [open]);

  function placeOrder(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setOrderNumber(`6M-${Math.random().toString(36).slice(2, 8).toUpperCase()}`);
    onClear();
  }

  function closeDrawer() {
    onClose();
    window.setTimeout(() => {
      setCheckout(false);
      setOrderNumber("");
    }, 250);
  }

  return (
    <>
      <button className={`cart-overlay ${open ? "is-open" : ""}`} aria-label="Close shopping bag" onClick={closeDrawer} type="button" />
      <aside className={`cart-drawer ${open ? "is-open" : ""}`} aria-hidden={!open} aria-label="Shopping bag">
        <div className="cart-header">
          <div>
            <p className="eyebrow">{checkout ? "Secure checkout" : "Your selection"}</p>
            <h2>{checkout ? "Delivery details" : "Shopping bag"}</h2>
          </div>
          <button type="button" onClick={closeDrawer} aria-label="Close shopping bag">Close</button>
        </div>

        {orderNumber ? (
          <div className="order-success">
            <span aria-hidden="true">✓</span>
            <p className="eyebrow">Order confirmed</p>
            <h3>Thank you for your moment.</h3>
            <p>Your order request <strong>{orderNumber}</strong> has been created. The atelier will confirm availability and payment details by email.</p>
            <button className="button button--dark" type="button" onClick={closeDrawer}>Continue shopping</button>
          </div>
        ) : checkout ? (
          <form className="checkout-form" onSubmit={placeOrder}>
            <label>Full name<input name="name" autoComplete="name" required /></label>
            <label>Email<input name="email" type="email" autoComplete="email" required /></label>
            <label>Delivery address<input name="address" autoComplete="street-address" required /></label>
            <div className="checkout-row">
              <label>City<input name="city" autoComplete="address-level2" required /></label>
              <label>Postal code<input name="postal" autoComplete="postal-code" required /></label>
            </div>
            <label>Country
              <select name="country" defaultValue="European Union">
                <option>European Union</option>
                <option>Ukraine</option>
                <option>United Kingdom</option>
                <option>United States</option>
                <option>Rest of world</option>
              </select>
            </label>
            <label>Delivery service
              <select name="delivery" defaultValue="DHL Express">
                <option>DHL Express · insured</option>
                <option>DPD Classic · insured</option>
              </select>
            </label>
            <label className="payment-choice">
              <input type="radio" name="payment" defaultChecked />
              <span><strong>Secure card payment</strong><small>Stripe · Visa · Mastercard · Apple Pay · Google Pay</small></span>
            </label>
            <div className="checkout-total"><span>Total</span><strong>{formatMoney(subtotal, currency)}</strong></div>
            <button className="button button--dark checkout-button" type="submit">Confirm test order</button>
            <button className="back-button" type="button" onClick={() => setCheckout(false)}>← Back to bag</button>
          </form>
        ) : items.length === 0 ? (
          <div className="empty-cart">
            <p>Your bag is waiting for a piece with meaning.</p>
            <Link className="button button--dark" href="/collections">Explore the collection</Link>
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
                      <div className="quantity" aria-label={`Quantity for ${item.product.title}`}>
                        <button type="button" onClick={() => onQuantity(item.key, item.quantity - 1)} aria-label="Decrease quantity">−</button>
                        <span>{item.quantity}</span>
                        <button type="button" onClick={() => onQuantity(item.key, item.quantity + 1)} aria-label="Increase quantity">+</button>
                      </div>
                      <button className="remove-item" type="button" onClick={() => onRemove(item.key)}>Remove</button>
                    </div>
                  </div>
                </article>
              ))}
            </div>
            <div className="cart-summary">
              <div><span>Subtotal</span><strong>{formatMoney(subtotal, currency)}</strong></div>
              <p>Insured delivery and returns are complimentary.</p>
              <button className="button button--dark checkout-button" type="button" onClick={() => setCheckout(true)}>Continue to checkout</button>
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
        <p className="eyebrow">Letters from the atelier</p>
        <h2>For the moments ahead.</h2>
        <form
          onSubmit={(event) => {
            event.preventDefault();
            setSubscribed(true);
            event.currentTarget.reset();
          }}
        >
          <label className="sr-only" htmlFor="email">Email address</label>
          <input id="email" name="email" placeholder="Email address" type="email" required />
          <button type="submit">{subscribed ? "Welcome to 6MOMENTS" : "Subscribe"}</button>
        </form>
        {subscribed && <p className="signup-success" role="status">Your private letters are confirmed.</p>}
      </div>
      <div className="footer-bottom">
        <Link className="wordmark wordmark--footer" href="/">6MOMENTS</Link>
        <nav aria-label="Footer navigation">
          <Link href="/collections">Shop</Link>
          <Link href="/about">Our story</Link>
          <Link href="/journal">Journal</Link>
          <Link href="/contact">Contact</Link>
          <Link href="/admin/catalog">Catalog manager</Link>
        </nav>
        <p>Modern heirlooms · © {new Date().getFullYear()}</p>
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

    setCookieOpen(!cookieAccepted);
    if (!newsletterDismissed && !newsletterCompleted) {
      const timer = window.setTimeout(() => setNewsletterOpen(true), 350);
      return () => window.clearTimeout(timer);
    }
  }, []);

  useEffect(() => {
    if (!newsletterOpen) return;

    document.body.classList.add("welcome-open");
    const closeOnEscape = (event: KeyboardEvent) => {
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
              aria-label="Close early access invitation"
            >
              <span aria-hidden="true">×</span>
            </button>
            <div className="welcome-image" aria-hidden="true" />
            <div className="welcome-copy">
              <h2 id="welcome-title">Unlock early access</h2>
              <p>
                Be among the first to explore new collections, exclusive releases,
                and private offers reserved for our community. Enjoy{" "}
                <strong>complimentary shipping</strong> on your first purchase.
              </p>
              {subscribed ? (
                <p className="welcome-success" role="status">
                  Welcome to the inner circle.
                </p>
              ) : (
                <form className="welcome-form" onSubmit={submitNewsletter}>
                  <label className="sr-only" htmlFor="welcome-email">Email address</label>
                  <input
                    id="welcome-email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    placeholder="Email address"
                    autoFocus
                    required
                  />
                  <button type="submit" aria-label="Join the early access list">
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
              <span>Don&apos;t show again</span>
            </label>
          </section>
        </div>
      )}

      {cookieOpen && (
        <aside className="cookie-banner" aria-label="Cookie notice">
          <span className="cookie-icon" aria-hidden="true">●</span>
          <p>
            We use cookies and other similar technologies to improve your browsing
            experience and the functionality of our site. Learn more in our{" "}
            <Link href="/privacy">Privacy Policy</Link>.
          </p>
          <button type="button" onClick={acceptCookies} aria-label="Accept cookies">OK</button>
        </aside>
      )}
    </>
  );
}

export function Storefront({ path }: { path: string }) {
  const [cart, setCart] = useState<CartItem[]>([]);
  const [cartOpen, setCartOpen] = useState(false);
  const [cartLoaded, setCartLoaded] = useState(false);
  const [currency, setCurrency] = useState<CurrencyCode>("USD");
  const [importedProducts, setImportedProducts] = useState<Product[]>([]);
  const [catalogLoaded, setCatalogLoaded] = useState(false);

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
        onClear={() => setCart([])}
      />
      <WelcomeOverlays />
    </div>
  );
}
