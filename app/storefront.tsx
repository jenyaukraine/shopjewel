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
  availability: "In stock" | "Made to order";
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
  return new Intl.NumberFormat(currency === "CZK" ? "cs-CZ" : currency === "UAH" ? "uk-UA" : "en-US", {
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
    category: "Rings",
    moment: "Moment 01 — The Promise",
    title: "Promise Solitaire",
    subtitle: "18k gold · traceable diamond",
    description:
      "A low-set solitaire with a softly rounded band, designed to sit close to the hand and wear beautifully every day.",
    price: 2450,
    oldPrice: 2750,
    image: "/products/promise-solitaire.webp",
    metal: "Yellow gold",
    fineness: "750 / 18k",
    stoneType: "Lab-grown diamond",
    availability: "In stock",
    deliveryDays: 3,
    weight: 2.8,
    carat: 0.5,
    stoneCount: 1,
    popularity: 100,
    isNew: true,
    options: [
      { name: "Metal", values: ["Yellow gold", "White gold", "Rose gold"] },
      { name: "Ring size", values: ["48", "50", "52", "54", "56"] },
      { name: "Stone", values: ["0.30 ct", "0.50 ct", "0.75 ct"] },
    ],
    details: ["Solid recycled 18k gold", "VS clarity traceable diamond", "Complimentary resizing within 30 days"],
  },
  {
    id: "union-band",
    slug: "union-band",
    sku: "6M-WE-002",
    category: "Wedding rings",
    moment: "Moment 02 — The Union",
    title: "Union Band",
    subtitle: "18k gold · hand-finished",
    description:
      "A timeless band with a gently softened profile. Made alone or as a pair, and finished individually by hand.",
    price: 980,
    image: "/products/union-band.webp",
    metal: "Yellow gold",
    fineness: "750 / 18k",
    stoneType: "Without stones",
    availability: "Made to order",
    deliveryDays: 10,
    weight: 3.9,
    carat: 0,
    stoneCount: 0,
    popularity: 92,
    options: [
      { name: "Metal", values: ["Yellow gold", "White gold", "Rose gold"] },
      { name: "Ring size", values: ["48", "50", "52", "54", "56", "58"] },
      { name: "Width", values: ["2 mm", "3 mm", "4 mm"] },
    ],
    details: ["Solid recycled 18k gold", "Satin or polished edge", "Optional engraving included"],
  },
  {
    id: "arrival-pendant",
    slug: "arrival-pendant",
    sku: "6M-NE-003",
    category: "Necklaces",
    moment: "Moment 03 — The Arrival",
    title: "Arrival Pendant",
    subtitle: "18k gold · brilliant diamond",
    description:
      "A small point of light suspended on a fine chain—made to mark the day a new chapter entered the world.",
    price: 1320,
    oldPrice: 1480,
    image: "/products/arrival-pendant.webp",
    metal: "Yellow gold",
    fineness: "750 / 18k",
    stoneType: "Natural diamond",
    availability: "In stock",
    deliveryDays: 3,
    weight: 2.1,
    carat: 0.1,
    stoneCount: 1,
    popularity: 96,
    options: [
      { name: "Metal", values: ["Yellow gold", "White gold"] },
      { name: "Chain length", values: ["40 cm", "45 cm", "50 cm"] },
      { name: "Engraving", values: ["Without engraving", "Initial", "Date"] },
    ],
    details: ["Solid recycled 18k gold", "0.10 ct traceable diamond", "Adjustable chain fastening"],
  },
  {
    id: "becoming-hoops",
    slug: "becoming-hoops",
    sku: "6M-EA-004",
    category: "Earrings",
    moment: "Moment 04 — The Becoming",
    title: "Becoming Hoops",
    subtitle: "18k gold · sold as a pair",
    description:
      "Lightweight oval hoops with enough presence for every day and enough restraint to remain entirely your own.",
    price: 1180,
    image: "/products/becoming-hoops.webp",
    metal: "Yellow gold",
    fineness: "750 / 18k",
    stoneType: "Without stones",
    availability: "In stock",
    deliveryDays: 3,
    weight: 4.2,
    carat: 0,
    stoneCount: 0,
    popularity: 88,
    isNew: true,
    options: [
      { name: "Metal", values: ["Yellow gold", "White gold", "Rose gold"] },
      { name: "Size", values: ["Small", "Medium", "Large"] },
      { name: "Finish", values: ["Polished", "Soft satin"] },
    ],
    details: ["Solid recycled 18k gold", "Secure hinged closure", "Comfort-weight construction"],
  },
  {
    id: "gratitude-bracelet",
    slug: "gratitude-bracelet",
    sku: "6M-BR-005",
    category: "Bracelets",
    moment: "Moment 05 — The Gratitude",
    title: "Gratitude Bracelet",
    subtitle: "18k gold · hand-set stone",
    description:
      "A delicate oval link bracelet punctuated by a single diamond—a quiet thank you that stays close.",
    price: 1560,
    oldPrice: 1790,
    image: "/products/gratitude-bracelet.webp",
    metal: "Yellow gold",
    fineness: "750 / 18k",
    stoneType: "Natural diamond",
    availability: "In stock",
    deliveryDays: 3,
    weight: 2.6,
    carat: 0.15,
    stoneCount: 1,
    popularity: 90,
    options: [
      { name: "Metal", values: ["Yellow gold", "White gold"] },
      { name: "Length", values: ["15 cm", "17 cm", "19 cm"] },
      { name: "Stone", values: ["Diamond", "Sapphire", "Emerald"] },
    ],
    details: ["Solid recycled 18k gold", "Traceable natural stone", "Hidden safety fastening"],
  },
  {
    id: "legacy-signet",
    slug: "legacy-signet",
    sku: "6M-RI-006",
    category: "Rings",
    moment: "Moment 06 — The Legacy",
    title: "Legacy Signet",
    subtitle: "Platinum · made to order",
    description:
      "A weighty signet with a softened face, ready for a mark, monogram, date, or symbol that belongs only to you.",
    price: 2250,
    image: "/products/legacy-signet.webp",
    metal: "Platinum",
    fineness: "950",
    stoneType: "Without stones",
    availability: "Made to order",
    deliveryDays: 10,
    weight: 8.4,
    carat: 0,
    stoneCount: 0,
    popularity: 82,
    options: [
      { name: "Material", values: ["Platinum", "Yellow gold", "White gold"] },
      { name: "Ring size", values: ["50", "52", "54", "56", "58", "60"] },
      { name: "Face", values: ["Plain", "Monogram", "Symbol"] },
    ],
    details: ["Made individually to order", "Hand-engraving available", "Presented in a solid oak box"],
  },
  {
    id: "first-ride",
    slug: "first-ride",
    sku: "6M-SE-007",
    category: "Special editions",
    moment: "Special edition — The First Ride",
    title: "First Ride Balance Bike",
    subtitle: "Ash wood · leather · alloy",
    description:
      "A lasting object for a very first adventure. The same product model supports practical attributes such as wheel diameter, frame size and colour.",
    price: 890,
    image: "/products/first-ride.webp",
    metal: "Alloy",
    fineness: "Not applicable",
    stoneType: "Without stones",
    availability: "In stock",
    deliveryDays: 3,
    weight: 3100,
    carat: 0,
    stoneCount: 0,
    popularity: 76,
    isNew: true,
    options: [
      { name: "Wheel size", values: ["12 inch", "14 inch", "16 inch"] },
      { name: "Frame size", values: ["Small", "Medium"] },
      { name: "Colour", values: ["Oat", "Forest", "Ink"] },
    ],
    details: ["Responsibly sourced ash frame", "Puncture-resistant tyres", "Adjustable leather saddle"],
  },
];

const routeCopy: Record<string, { eyebrow: string; title: string; copy: string }> = {
  "/collections": {
    eyebrow: "The collection",
    title: "Objects with a reason to remain.",
    copy: "Seven made-to-keep pieces, each available in the size, material and finish that makes it yours.",
  },
  "/about": {
    eyebrow: "Our philosophy",
    title: "A life is remembered in moments.",
    copy: "6MOMENTS creates modern heirlooms for the passages that shape us—not only the expected ones, but the deeply personal ones too.",
  },
  "/journal": {
    eyebrow: "The journal",
    title: "Stories worth carrying forward.",
    copy: "Notes on ritual, craft, and the people who give objects their meaning.",
  },
  "/contact": {
    eyebrow: "Private appointments",
    title: "We are here for your moment.",
    copy: "Speak with our atelier about sizing, stones, engraving, or a piece made only for you.",
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
      <div className="announcement">Complimentary insured delivery and returns</div>
      <header className="site-header">
        <Link className="wordmark" href="/" aria-label="6MOMENTS home">
          6MOMENTS
        </Link>
        <nav className="desktop-nav" aria-label="Primary navigation">
          <Link aria-current={path.startsWith("/collections") || path.startsWith("/products") ? "page" : undefined} href="/collections">
            Shop
          </Link>
          <Link aria-current={path === "/about" ? "page" : undefined} href="/about">
            Our story
          </Link>
          <Link aria-current={path === "/journal" ? "page" : undefined} href="/journal">
            Journal
          </Link>
        </nav>
        <div className="header-actions">
          <label className="currency-control">
            <span className="sr-only">Currency</span>
            <select value={currency} onChange={(event) => onCurrency(event.target.value as CurrencyCode)}>
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="CZK">CZK</option>
              <option value="UAH">UAH</option>
            </select>
          </label>
          <Link href="/contact">Private appointment</Link>
          <button className="bag" type="button" onClick={onOpenCart} aria-label={`Shopping bag, ${count} items`}>
            Bag <span>{count}</span>
          </button>
        </div>
        <details className="mobile-menu">
          <summary aria-label="Open navigation">Menu</summary>
          <nav aria-label="Mobile navigation">
            <Link href="/collections">Shop</Link>
            <Link href="/about">Our story</Link>
            <Link href="/journal">Journal</Link>
            <Link href="/admin/catalog">Catalog manager</Link>
            <Link href="/contact">Private appointment</Link>
            <label className="mobile-currency">Currency
              <select value={currency} onChange={(event) => onCurrency(event.target.value as CurrencyCode)}>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="CZK">CZK</option>
                <option value="UAH">UAH</option>
              </select>
            </label>
            <button type="button" onClick={onOpenCart}>Bag ({count})</button>
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
              {product.isNew && <span className="product-badge">New</span>}
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
              <div><dt>SKU</dt><dd>{product.sku}</dd></div>
              <div><dt>Weight</dt><dd>{product.weight >= 100 ? `${(product.weight / 1000).toFixed(1)} kg` : `${product.weight} g`}</dd></div>
              <div><dt>Carat</dt><dd>{product.carat || "—"}</dd></div>
              <div><dt>Stones</dt><dd>{product.stoneCount || "—"}</dd></div>
            </dl>
          </Link>
          <div className="card-footer">
            <span className={`stock-status ${product.availability === "In stock" ? "is-stocked" : ""}`}>
              {product.availability} · {product.deliveryDays} days
            </span>
            {onQuickAdd && (
              <button type="button" onClick={() => onQuickAdd(product, defaultOptions(product))}>
                Quick add
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
          <p className="eyebrow">Fine pieces for a life well lived</p>
          <h1 id="hero-title">Where moments become legacy.</h1>
          <p>
            Seven defining keepsakes, made in enduring materials and configured for the person who will carry them forward.
          </p>
          <div className="hero-actions">
            <Link className="button button--dark" href="/collections">Shop the collection</Link>
            <Link className="text-link" href="/about">Our philosophy <span aria-hidden="true">→</span></Link>
          </div>
        </div>
      </section>

      <section className="intro">
        <p className="eyebrow">The six moments · one special edition</p>
        <h2>Choose the details that make it yours.</h2>
        <p className="intro-copy">
          Select the right material, size, finish or practical specification. Every choice follows the piece into your bag and order.
        </p>
      </section>

      <section className="moments-section" aria-labelledby="products-title">
        <div className="section-heading">
          <div>
            <p className="eyebrow">The collection</p>
            <h2 id="products-title">Pieces to keep</h2>
          </div>
          <Link className="text-link" href="/collections">View all products <span aria-hidden="true">→</span></Link>
        </div>
        <ProductGrid products={catalog} currency={currency} limit={6} onQuickAdd={onQuickAdd} />
      </section>

      <section className="craft">
        <Image
          className="craft-image"
          src="/editorial/craftsmanship.webp"
          alt="Goldsmith finishing a fine gold ring by hand"
          unoptimized
          width={1200}
          height={1200}
          sizes="(max-width: 720px) 100vw, 58vw"
        />
        <div className="craft-copy">
          <p className="eyebrow">Made to outlive the moment</p>
          <h2>Crafted slowly.<br />Kept forever.</h2>
          <p>
            Every piece is considered from every angle, finished by hand, and made to grow more personal with time.
          </p>
          <Link className="text-link" href="/about">Our materials and craft <span aria-hidden="true">→</span></Link>
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
    onAdd(product, engraving ? { ...selected, Engraving: engraving } : selected);
    setAdded(true);
  }

  return (
    <main>
      <div className="breadcrumbs">
        <Link href="/collections">Collection</Link><span aria-hidden="true">/</span><span>{product.title}</span>
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
            <strong className={product.availability === "In stock" ? "is-stocked" : ""}>{product.availability}</strong>
            <span>Delivery in {product.deliveryDays} days</span>
            <span>SKU {product.sku}</span>
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
            Optional engraving
            <input
              maxLength={24}
              onChange={(event) => {
                setEngraving(event.target.value);
                setAdded(false);
              }}
              placeholder="Initials or a meaningful date"
              value={engraving}
            />
            <small>{engraving.length}/24 characters · confirmed by the atelier before production</small>
          </label>

          <button className="button button--dark add-button" type="button" onClick={addProduct}>
            {added ? "Added to bag" : `Add to bag — ${formatMoney(product.price, currency)}`}
          </button>
          {added && (
            <button className="view-bag-link" type="button" onClick={onOpenCart}>View bag →</button>
          )}
          <ul className="product-details-list">
            {product.details.map((detail) => <li key={detail}>{detail}</li>)}
          </ul>
          <div className="specification-block">
            <h2>Product specifications</h2>
            <dl>
              <div><dt>Category</dt><dd>{product.category}</dd></div>
              <div><dt>Metal colour</dt><dd>{product.metal}</dd></div>
              <div><dt>Fineness</dt><dd>{product.fineness}</dd></div>
              <div><dt>Stone</dt><dd>{product.stoneType}</dd></div>
              <div><dt>Weight</dt><dd>{product.weight >= 100 ? `${(product.weight / 1000).toFixed(1)} kg` : `${product.weight} g`}</dd></div>
              <div><dt>Total carat</dt><dd>{product.carat ? `${product.carat} ct` : "Without stones"}</dd></div>
              <div><dt>Stone count</dt><dd>{product.stoneCount}</dd></div>
            </dl>
          </div>
          {product.category.includes("Ring") && (
            <details className="product-accordion">
              <summary>Find your ring size</summary>
              <p>Measure the inside diameter of a ring that already fits. 15.3 / 15.9 / 16.5 / 17.2 / 17.8 mm correspond to EU sizes 48 / 50 / 52 / 54 / 56.</p>
            </details>
          )}
          <details className="product-accordion">
            <summary>Hint about this gift</summary>
            <form
              className="hint-form"
              onSubmit={(event) => {
                event.preventDefault();
                setHintSent(true);
                event.currentTarget.reset();
              }}
            >
              <label>Your name<input name="sender" required /></label>
              <label>Recipient email<input name="recipient" type="email" required /></label>
              <label>Message<textarea name="message" rows={3} defaultValue={`I found ${product.title} and thought of you.`} /></label>
              <button className="button button--dark" type="submit">Send a discreet hint</button>
              {hintSent && <p className="form-success" role="status">Your hint is ready to make their day.</p>}
            </form>
          </details>
        </div>
      </section>
      {related[0] && (
        <section className="bundle-section">
          <div>
            <p className="eyebrow">Better together</p>
            <h2>A considered pair, 10% less.</h2>
            <p>{product.title} and {related[0].title} arrive together in our signature presentation.</p>
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
              Add the set
            </button>
          </div>
        </section>
      )}
      {related.length > 0 && (
        <section className="moments-section recommendations">
          <div className="section-heading">
            <div><p className="eyebrow">You may also like</p><h2>Chosen for your moment</h2></div>
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
    </div>
  );
}
