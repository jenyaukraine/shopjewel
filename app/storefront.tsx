"use client";

import { FormEvent, useEffect, useMemo, useState } from "react";
import Image from "next/image";
import Link from "next/link";

type ProductOption = {
  name: string;
  values: string[];
};

type Product = {
  id: string;
  slug: string;
  moment: string;
  title: string;
  subtitle: string;
  description: string;
  price: number;
  image: string;
  options: ProductOption[];
  details: string[];
};

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
    moment: "Moment 01 — The Promise",
    title: "Promise Solitaire",
    subtitle: "18k gold · traceable diamond",
    description:
      "A low-set solitaire with a softly rounded band, designed to sit close to the hand and wear beautifully every day.",
    price: 2450,
    image: "/products/promise-solitaire.webp",
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
    moment: "Moment 02 — The Union",
    title: "Union Band",
    subtitle: "18k gold · hand-finished",
    description:
      "A timeless band with a gently softened profile. Made alone or as a pair, and finished individually by hand.",
    price: 980,
    image: "/products/union-band.webp",
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
    moment: "Moment 03 — The Arrival",
    title: "Arrival Pendant",
    subtitle: "18k gold · brilliant diamond",
    description:
      "A small point of light suspended on a fine chain—made to mark the day a new chapter entered the world.",
    price: 1320,
    image: "/products/arrival-pendant.webp",
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
    moment: "Moment 04 — The Becoming",
    title: "Becoming Hoops",
    subtitle: "18k gold · sold as a pair",
    description:
      "Lightweight oval hoops with enough presence for every day and enough restraint to remain entirely your own.",
    price: 1180,
    image: "/products/becoming-hoops.webp",
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
    moment: "Moment 05 — The Gratitude",
    title: "Gratitude Bracelet",
    subtitle: "18k gold · hand-set stone",
    description:
      "A delicate oval link bracelet punctuated by a single diamond—a quiet thank you that stays close.",
    price: 1560,
    image: "/products/gratitude-bracelet.webp",
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
    moment: "Moment 06 — The Legacy",
    title: "Legacy Signet",
    subtitle: "Platinum · made to order",
    description:
      "A weighty signet with a softened face, ready for a mark, monogram, date, or symbol that belongs only to you.",
    price: 2250,
    image: "/products/legacy-signet.webp",
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
    moment: "Special edition — The First Ride",
    title: "First Ride Balance Bike",
    subtitle: "Ash wood · leather · alloy",
    description:
      "A lasting object for a very first adventure. The same product model supports practical attributes such as wheel diameter, frame size and colour.",
    price: 890,
    image: "/products/first-ride.webp",
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

const money = new Intl.NumberFormat("en-US", {
  style: "currency",
  currency: "USD",
  maximumFractionDigits: 0,
});

function Header({
  path,
  count,
  onOpenCart,
}: {
  path: string;
  count: number;
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
            <Link href="/contact">Private appointment</Link>
            <button type="button" onClick={onOpenCart}>Bag ({count})</button>
          </nav>
        </details>
      </header>
    </>
  );
}

function ProductGrid({ limit }: { limit?: number }) {
  const visibleProducts = typeof limit === "number" ? products.slice(0, limit) : products;

  return (
    <div className="collection-grid product-grid">
      {visibleProducts.map((product) => (
        <Link className="moment-card product-card" href={`/products/${product.slug}`} key={product.id}>
          <div className="moment-art">
            <Image
              className="product-photo"
              src={product.image}
              alt=""
              width={1200}
              height={1200}
              sizes="(max-width: 720px) 50vw, 33vw"
            />
            <span className="moment-number">{product.moment.split(" — ")[0]}</span>
            <span className="card-arrow" aria-hidden="true">↗</span>
          </div>
          <p className="product-kicker">{product.moment}</p>
          <div className="product-line">
            <h3>{product.title}</h3>
            <span>{money.format(product.price)}</span>
          </div>
          <p>{product.subtitle}</p>
        </Link>
      ))}
    </div>
  );
}

function HomePage() {
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
        <ProductGrid limit={6} />
      </section>

      <section className="craft">
        <Image
          className="craft-image"
          src="/editorial/craftsmanship.webp"
          alt="Goldsmith finishing a fine gold ring by hand"
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
  onAdd,
  onOpenCart,
}: {
  product: Product;
  onAdd: (product: Product, options: Record<string, string>) => void;
  onOpenCart: () => void;
}) {
  const initialOptions = Object.fromEntries(product.options.map((option) => [option.name, option.values[0]]));
  const [selected, setSelected] = useState<Record<string, string>>(initialOptions);
  const [added, setAdded] = useState(false);

  function addProduct() {
    onAdd(product, selected);
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
          <p className="product-price">{money.format(product.price)}</p>
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

          <button className="button button--dark add-button" type="button" onClick={addProduct}>
            {added ? "Added to bag" : `Add to bag — ${money.format(product.price)}`}
          </button>
          {added && (
            <button className="view-bag-link" type="button" onClick={onOpenCart}>View bag →</button>
          )}
          <ul className="product-details-list">
            {product.details.map((detail) => <li key={detail}>{detail}</li>)}
          </ul>
        </div>
      </section>
    </main>
  );
}

function InteriorPage({ path }: { path: string }) {
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
        <section className="moments-section interior-grid">
          <div className="collection-intro">
            <p>01—07</p>
            <p>Recycled precious metals, traceable stones and considered objects for the chapters that matter.</p>
          </div>
          <ProductGrid />
        </section>
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
  onClose,
  onQuantity,
  onRemove,
  onClear,
}: {
  open: boolean;
  items: Array<CartItem & { product: Product }>;
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
            <p>Your demonstration order <strong>{orderNumber}</strong> has been created.</p>
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
            <label className="payment-choice">
              <input type="radio" name="payment" defaultChecked />
              <span><strong>Pay on delivery</strong><small>No card details are collected in this demonstration.</small></span>
            </label>
            <div className="checkout-total"><span>Total</span><strong>{money.format(subtotal)}</strong></div>
            <button className="button button--dark checkout-button" type="submit">Place demonstration order</button>
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
                      width={240}
                      height={240}
                      sizes="120px"
                    />
                  </div>
                  <div className="cart-item-copy">
                    <div className="cart-item-title">
                      <h3>{item.product.title}</h3>
                      <strong>{money.format(item.product.price * item.quantity)}</strong>
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
              <div><span>Subtotal</span><strong>{money.format(subtotal)}</strong></div>
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

  useEffect(() => {
    const timer = window.setTimeout(() => {
      try {
        const saved = window.localStorage.getItem("6moments-cart");
        if (saved) setCart(JSON.parse(saved) as CartItem[]);
      } catch {
        window.localStorage.removeItem("6moments-cart");
      }
      setCartLoaded(true);
    }, 0);
    return () => window.clearTimeout(timer);
  }, []);

  useEffect(() => {
    if (cartLoaded) window.localStorage.setItem("6moments-cart", JSON.stringify(cart));
  }, [cart, cartLoaded]);

  const detailedItems = useMemo(
    () =>
      cart.flatMap((item) => {
        const product = products.find((candidate) => candidate.id === item.productId);
        return product ? [{ ...item, product }] : [];
      }),
    [cart],
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
    ? products.find((candidate) => candidate.slug === requestedProductSlug)
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
      <Header path={path} count={count} onOpenCart={() => setCartOpen(true)} />
      {product ? (
        <ProductDetail product={product} onAdd={addToCart} onOpenCart={() => setCartOpen(true)} />
      ) : path === "/" ? (
        <HomePage />
      ) : (
        <InteriorPage path={path} />
      )}
      <Footer />
      <CartDrawer
        open={cartOpen}
        items={detailedItems}
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
