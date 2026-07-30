"use client";

import { FormEvent, useEffect, useMemo, useState } from "react";

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
  art: "ring" | "bands" | "pendant" | "earrings" | "bracelet" | "heirloom" | "bike";
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
    moment: "01 — The Promise",
    title: "Promise Solitaire",
    subtitle: "18k gold · traceable diamond",
    description:
      "A low-set solitaire with a softly rounded band, designed to sit close to the hand and wear beautifully every day.",
    price: 2450,
    art: "ring",
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
    moment: "02 — The Union",
    title: "Union Band",
    subtitle: "18k gold · hand-finished",
    description:
      "A timeless band with a gently softened profile. Made alone or as a pair, and finished individually by hand.",
    price: 980,
    art: "bands",
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
    moment: "03 — The Arrival",
    title: "Arrival Pendant",
    subtitle: "18k gold · brilliant diamond",
    description:
      "A small point of light suspended on a fine chain—made to mark the day a new chapter entered the world.",
    price: 1320,
    art: "pendant",
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
    moment: "04 — The Becoming",
    title: "Becoming Hoops",
    subtitle: "18k gold · sold as a pair",
    description:
      "Lightweight oval hoops with enough presence for every day and enough restraint to remain entirely your own.",
    price: 1180,
    art: "earrings",
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
    moment: "05 — The Gratitude",
    title: "Gratitude Bracelet",
    subtitle: "18k gold · hand-set stone",
    description:
      "A delicate oval link bracelet punctuated by a single diamond—a quiet thank you that stays close.",
    price: 1560,
    art: "bracelet",
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
    moment: "06 — The Legacy",
    title: "Legacy Signet",
    subtitle: "Platinum · made to order",
    description:
      "A weighty signet with a softened face, ready for a mark, monogram, date, or symbol that belongs only to you.",
    price: 2250,
    art: "heirloom",
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
    art: "bike",
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

function ProductArt({ variant }: { variant: Product["art"] }) {
  return (
    <div className={`jewelry-art jewelry-art--${variant}`} aria-hidden="true">
      <span className="jewel jewel--one" />
      <span className="jewel jewel--two" />
      <span className="jewel jewel--three" />
      {variant === "bike" && (
        <>
          <span className="bike-frame" />
          <span className="bike-seat" />
          <span className="bike-bar" />
        </>
      )}
    </div>
  );
}

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
        <a className="wordmark" href="/" aria-label="6MOMENTS home">
          6MOMENTS
        </a>
        <nav className="desktop-nav" aria-label="Primary navigation">
          <a aria-current={path.startsWith("/collections") || path.startsWith("/products") ? "page" : undefined} href="/collections">
            Shop
          </a>
          <a aria-current={path === "/about" ? "page" : undefined} href="/about">
            Our story
          </a>
          <a aria-current={path === "/journal" ? "page" : undefined} href="/journal">
            Journal
          </a>
        </nav>
        <div className="header-actions">
          <a href="/contact">Private appointment</a>
          <button className="bag" type="button" onClick={onOpenCart} aria-label={`Shopping bag, ${count} items`}>
            Bag <span>{count}</span>
          </button>
        </div>
        <details className="mobile-menu">
          <summary aria-label="Open navigation">Menu</summary>
          <nav aria-label="Mobile navigation">
            <a href="/collections">Shop</a>
            <a href="/about">Our story</a>
            <a href="/journal">Journal</a>
            <a href="/contact">Private appointment</a>
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
        <a className="moment-card product-card" href={`/products/${product.slug}`} key={product.id}>
          <div className="moment-art">
            <span className="moment-number">{product.moment.split(" — ")[0]}</span>
            <ProductArt variant={product.art} />
            <span className="card-arrow" aria-hidden="true">↗</span>
          </div>
          <p className="product-kicker">{product.moment}</p>
          <div className="product-line">
            <h3>{product.title}</h3>
            <span>{money.format(product.price)}</span>
          </div>
          <p>{product.subtitle}</p>
        </a>
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
            <a className="button button--dark" href="/collections">Shop the collection</a>
            <a className="text-link" href="/about">Our philosophy <span aria-hidden="true">→</span></a>
          </div>
        </div>
      </section>

      <section className="intro">
        <p className="eyebrow">Made personal</p>
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
          <a className="text-link" href="/collections">View all products <span aria-hidden="true">→</span></a>
        </div>
        <ProductGrid limit={6} />
      </section>

      <section className="craft">
        <div className="craft-image" role="img" aria-label="Goldsmith tools and a fine gold ring">
          <div className="craft-ring" />
        </div>
        <div className="craft-copy">
          <p className="eyebrow">Made to outlive the moment</p>
          <h2>Crafted slowly.<br />Kept forever.</h2>
          <p>
            Every piece is considered from every angle, finished by hand, and made to grow more personal with time.
          </p>
          <a className="text-link" href="/about">Our materials and craft <span aria-hidden="true">→</span></a>
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
        <a href="/collections">Collection</a><span aria-hidden="true">/</span><span>{product.title}</span>
      </div>
      <section className="product-detail">
        <div className="product-visual">
          <ProductArt variant={product.art} />
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

  return (
    <main>
      <section className="interior-hero">
        <p className="eyebrow">{page.eyebrow}</p>
        <h1>{page.title}</h1>
        <p>{page.copy}</p>
        {path === "/contact" && (
          <a className="button button--dark" href="mailto:atelier@6moments.store">Contact the atelier</a>
        )}
      </section>

      {path === "/about" ? (
        <section className="story-panel">
          <p className="story-number">6</p>
          <div>
            <h2>One language. Many forms.</h2>
            <p>
              Our pieces begin with proportion and restraint. Their final meaning—and every detail chosen along the way—is yours to give.
            </p>
          </div>
        </section>
      ) : (
        <section className="moments-section interior-grid">
          <ProductGrid />
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
            <a className="button button--dark" href="/collections">Explore the collection</a>
          </div>
        ) : (
          <>
            <div className="cart-items">
              {items.map((item) => (
                <article className="cart-item" key={item.key}>
                  <div className="cart-item-art"><ProductArt variant={item.product.art} /></div>
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
  return (
    <footer className="site-footer">
      <div className="footer-signup">
        <p className="eyebrow">Letters from the atelier</p>
        <h2>For the moments ahead.</h2>
        <form action="/contact" method="get">
          <label className="sr-only" htmlFor="email">Email address</label>
          <input id="email" name="email" placeholder="Email address" type="email" required />
          <button type="submit">Subscribe</button>
        </form>
      </div>
      <div className="footer-bottom">
        <a className="wordmark wordmark--footer" href="/">6MOMENTS</a>
        <nav aria-label="Footer navigation">
          <a href="/collections">Shop</a>
          <a href="/about">Our story</a>
          <a href="/contact">Contact</a>
        </nav>
        <p>© {new Date().getFullYear()} 6MOMENTS</p>
      </div>
    </footer>
  );
}

export function Storefront({ path }: { path: string }) {
  const [cart, setCart] = useState<CartItem[]>([]);
  const [cartOpen, setCartOpen] = useState(false);

  useEffect(() => {
    try {
      const saved = window.localStorage.getItem("6moments-cart");
      if (saved) setCart(JSON.parse(saved) as CartItem[]);
    } catch {
      window.localStorage.removeItem("6moments-cart");
    }
  }, []);

  useEffect(() => {
    window.localStorage.setItem("6moments-cart", JSON.stringify(cart));
  }, [cart]);

  const detailedItems = useMemo(
    () =>
      cart.flatMap((item) => {
        const product = products.find((candidate) => candidate.id === item.productId);
        return product ? [{ ...item, product }] : [];
      }),
    [cart],
  );
  const count = cart.reduce((total, item) => total + item.quantity, 0);
  const product = path.startsWith("/products/")
    ? products.find((candidate) => `/products/${candidate.slug}` === path)
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
