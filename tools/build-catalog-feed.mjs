// Converts the supplier product export into the compact feed the OpenCart
// module imports. The supplier ships one CSV row per purchasable combination
// (articul x gold caratage x diamond quality); the storefront sells one product
// per articul with the combination chosen as an option, so the rows are grouped
// here and every price is kept verbatim.
//
//   node tools/build-catalog-feed.mjs "path/to/products.csv"
//
// Writes opencart/noveraile/data/catalog-feed.json.

import { createHash } from "node:crypto";
import { readFileSync, writeFileSync, mkdirSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const HERE = dirname(fileURLToPath(import.meta.url));
const REPO = resolve(HERE, "..");
const OUTPUT = resolve(REPO, "opencart/noveraile/data/catalog-feed.json");

const LANGUAGES = ["en-gb", "de-de", "cs-cz", "ru-ru", "uk-ua"];

const IMAGE_BASE = "https://kazkaj.s3.eu-central-1.amazonaws.com/products/images/";
const IMAGE_SUFFIX = "/large.webp";
const ASSET_BASE = "https://kazkaj.s3.eu-central-1.amazonaws.com/";

// Gold caratage doubles as the hallmark fineness the storefront filters on.
const GOLD = {
  CT_9: { karat: 9, fineness: "375" },
  CT_14: { karat: 14, fineness: "585" },
  CT_18: { karat: 18, fineness: "750" },
};

// Ascending by price, which is also the order the option is presented in.
const QUALITY = {
  LAB: { label: "LAB", origin: "lab-grown" },
  G_SI: { label: "G/SI", origin: "natural" },
  G_VS2: { label: "G/VS2", origin: "natural" },
  F_VS2: { label: "F/VS2", origin: "natural" },
  D_VVS2: { label: "D/VVS2", origin: "natural" },
};

const CATEGORIES = {
  RING: {
    type: "rings",
    slug: "rings",
    name: ["Rings", "Ringe", "Prsteny", "Кольца", "Каблучки"],
  },
  EAR_RING: {
    type: "earrings",
    slug: "earrings",
    name: ["Earrings", "Ohrringe", "Náušnice", "Серьги", "Сережки"],
  },
  NECKLACE: {
    type: "necklaces",
    slug: "necklaces",
    name: ["Necklaces", "Halsketten", "Náhrdelníky", "Подвески", "Підвіски"],
  },
  BRACELET: {
    type: "bracelets",
    slug: "bracelets",
    name: ["Bracelets", "Armbänder", "Náramky", "Браслеты", "Браслети"],
  },
};

// The supplier's 16 product kinds become the second navigation level. `moment`
// maps a kind onto one of the six storefront moments so the curated home page
// tiles and the moment filter resolve to real products; it is editorial and can
// be retuned here without touching the importer.
const KINDS = {
  SOLITAIRE_RING: {
    category: "RING",
    slug: "solitaire-rings",
    moment: "engagement",
    style: "solitaire",
    name: ["Solitaire Ring", "Solitärring", "Solitérní prsten", "Кольцо Солитер", "Каблучка Солітер"],
  },
  SOLITAIRE_ETERNITY_RING: {
    category: "RING",
    slug: "solitaire-eternity-rings",
    moment: "engagement",
    style: "eternity",
    name: [
      "Solitaire Eternity Ring",
      "Solitär-Eternity-Ring",
      "Solitérní eternity prsten",
      "Кольцо Солитер Вечность",
      "Каблучка Солітер Вічність",
    ],
  },
  ETERNITY_RING: {
    category: "RING",
    slug: "eternity-rings",
    moment: "wedding",
    style: "eternity",
    name: ["Eternity Ring", "Eternity-Ring", "Eternity prsten", "Кольцо Вечность", "Каблучка Вічність"],
  },
  ILLUSION_RING: {
    category: "RING",
    slug: "illusion-rings",
    moment: "self-purchase",
    style: "illusion",
    name: ["Illusion Ring", "Illusion-Ring", "Prsten Illusion", "Кольцо Иллюзия", "Каблучка Ілюзія"],
  },
  MINIMALISM_RING: {
    category: "RING",
    slug: "minimalism-rings",
    moment: "milestone",
    style: "minimalism",
    name: [
      "Minimalism Ring",
      "Minimalismus-Ring",
      "Minimalistický prsten",
      "Кольцо Минимализм",
      "Каблучка Мінімалізм",
    ],
  },
  SOLITAIRE_BUTTERFLY_EAR_RING: {
    category: "EAR_RING",
    slug: "solitaire-butterfly-earrings",
    moment: "career",
    style: "studs",
    name: [
      "Solitaire Butterfly Earrings",
      "Solitär-Ohrringe Butterfly",
      "Solitérní náušnice Motýl",
      "Серьги Солитер Бабочка",
      "Сережки Солітер Метелик",
    ],
  },
  SOLITAIRE_ENGLISH_LOCK_EAR_RING: {
    category: "EAR_RING",
    slug: "solitaire-english-lock-earrings",
    moment: "career",
    style: "studs",
    name: [
      "Solitaire Earrings with English Lock",
      "Solitär-Ohrringe mit englischem Verschluss",
      "Solitérní náušnice s anglickým zapínáním",
      "Серьги Солитер с английским замком",
      "Сережки Солітер з англійським замком",
    ],
  },
  SOLITAIRE_ETERNITY_EAR_RING: {
    category: "EAR_RING",
    slug: "solitaire-eternity-earrings",
    moment: "career",
    style: "eternity",
    name: [
      "Solitaire Eternity Earrings",
      "Solitär-Eternity-Ohrringe",
      "Solitérní eternity náušnice",
      "Серьги Солитер Вечность",
      "Сережки Солітер Вічність",
    ],
  },
  ETERNITY_EAR_RING: {
    category: "EAR_RING",
    slug: "eternity-earrings",
    moment: "career",
    style: "eternity",
    name: [
      "Eternity Earrings",
      "Eternity-Ohrringe",
      "Eternity náušnice",
      "Серьги Вечность",
      "Сережки Вічність",
    ],
  },
  ILLUSION_EAR_RING: {
    category: "EAR_RING",
    slug: "illusion-earrings",
    moment: "career",
    style: "illusion",
    name: [
      "Illusion Earrings",
      "Illusion-Ohrringe",
      "Náušnice Illusion",
      "Серьги Иллюзия",
      "Сережки Ілюзія",
    ],
  },
  MINIMALISM_EAR_RING: {
    category: "EAR_RING",
    slug: "minimalism-earrings",
    moment: "career",
    style: "minimalism",
    name: [
      "Minimalism Earrings",
      "Minimalismus-Ohrringe",
      "Minimalistické náušnice",
      "Серьги Минимализм",
      "Сережки Мінімалізм",
    ],
  },
  SOLITAIRE_NECKLACE: {
    category: "NECKLACE",
    slug: "solitaire-necklaces",
    moment: "motherhood",
    style: "pendant",
    name: [
      "Solitaire Necklace",
      "Solitär-Halskette",
      "Solitérní náhrdelník",
      "Ожерелье Солитер",
      "Кольє Солітер",
    ],
  },
  SOLITAIRE_ETERNITY_NECKLACE: {
    category: "NECKLACE",
    slug: "solitaire-eternity-necklaces",
    moment: "motherhood",
    style: "eternity",
    name: [
      "Solitaire Eternity Necklace",
      "Solitär-Eternity-Halskette",
      "Solitérní eternity náhrdelník",
      "Ожерелье Солитер Вечность",
      "Кольє Солітер Вічність",
    ],
  },
  ILLUSION_NECKLACE: {
    category: "NECKLACE",
    slug: "illusion-necklaces",
    moment: "motherhood",
    style: "illusion",
    name: [
      "Illusion Necklace",
      "Illusion-Halskette",
      "Náhrdelník Illusion",
      "Ожерелье Иллюзия",
      "Кольє Ілюзія",
    ],
  },
  MINIMALISM_NECKLACE: {
    category: "NECKLACE",
    slug: "minimalism-necklaces",
    moment: "motherhood",
    style: "minimalism",
    name: [
      "Minimalism Necklace",
      "Minimalismus-Halskette",
      "Minimalistický náhrdelník",
      "Ожерелье Минимализм",
      "Кольє Мінімалізм",
    ],
  },
  MINIMALISM_BRACELET: {
    category: "BRACELET",
    slug: "minimalism-bracelets",
    moment: "self-purchase",
    style: "chain-bracelet",
    name: [
      "Minimalism Bracelet",
      "Minimalismus-Armband",
      "Minimalistický náramek",
      "Браслет Минимализм",
      "Браслет Мінімалізм",
    ],
  },
};

// Keys match the storefront's stone-shape filter slugs.
const SHAPES = {
  ROUND: { slug: "round", name: ["Round", "Rund", "Kulatý", "Круглая", "Кругла"] },
  PRINCESS: { slug: "princess", name: ["Princess", "Prinzess", "Princess", "Принцесса", "Принцеса"] },
  MARQUISE: { slug: "marquise", name: ["Marquise", "Marquise", "Markýza", "Маркиз", "Маркіз"] },
  BAGUETTE: { slug: "baguette", name: ["Baguette", "Baguette", "Bageta", "Багет", "Багет"] },
  HEART: { slug: "heart", name: ["Heart", "Herz", "Srdce", "Сердце", "Серце"] },
  OVAL: { slug: "oval", name: ["Oval", "Oval", "Ovál", "Овал", "Овал"] },
  PEAR: { slug: "pear", name: ["Pear", "Birne", "Hruška", "Груша", "Груша"] },
};

const COLLECTIONS = {
  CLASSIC: { slug: "classic", name: ["Classic", "Classic", "Classic", "Classic", "Classic"] },
  MINIMALISM: {
    slug: "minimalism",
    name: ["Minimalism", "Minimalismus", "Minimalismus", "Минимализм", "Мінімалізм"],
  },
  ILUZJA: { slug: "iluzja", name: ["Iluzja", "Iluzja", "Iluzja", "Iluzja", "Iluzja"] },
  ETERNITY: { slug: "eternity", name: ["Eternity", "Eternity", "Eternity", "Eternity", "Eternity"] },
  FANCY_CUT: {
    slug: "fancy-cut",
    name: ["Fancy Cut", "Fancy Cut", "Fancy Cut", "Fancy Cut", "Fancy Cut"],
  },
  NIGHT_SKY: {
    slug: "night-sky",
    name: ["Night Sky", "Night Sky", "Night Sky", "Night Sky", "Night Sky"],
  },
  PURE_LOVE: {
    slug: "pure-love",
    name: ["Pure Love", "Pure Love", "Pure Love", "Pure Love", "Pure Love"],
  },
};

const SHIPPING = { THREE_DAYS: 3, TEN_DAYS: 10 };

function parseCsv(text) {
  const rows = [];
  let row = [];
  let field = "";
  let quoted = false;

  for (let i = 0; i < text.length; i++) {
    const char = text[i];

    if (quoted) {
      if (char !== '"') {
        field += char;
      } else if (text[i + 1] === '"') {
        field += '"';
        i++;
      } else {
        quoted = false;
      }
      continue;
    }

    if (char === '"') quoted = true;
    else if (char === ",") {
      row.push(field);
      field = "";
    } else if (char === "\n") {
      row.push(field);
      rows.push(row);
      row = [];
      field = "";
    } else if (char !== "\r") field += char;
  }

  if (field !== "" || row.length) {
    row.push(field);
    rows.push(row);
  }

  return rows;
}

// A few enum columns arrive double-quoted inside the quoted field, so the
// parsed value still carries the literal quotes.
const clean = (value) => String(value ?? "").replace(/^"+|"+$/g, "").trim();

const slugify = (value) =>
  value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");

function assetPath(url, line) {
  if (!url.startsWith(ASSET_BASE)) {
    throw new Error(`Row ${line}: asset "${url}" is not hosted on the expected bucket.`);
  }
  return url.slice(ASSET_BASE.length);
}

function imageId(url, line) {
  const path = assetPath(url, line);
  if (!path.startsWith("products/images/") || !path.endsWith(IMAGE_SUFFIX)) {
    throw new Error(`Row ${line}: image "${url}" does not use the expected layout.`);
  }
  const id = path.slice("products/images/".length, path.length - IMAGE_SUFFIX.length);
  if (!/^[0-9a-f]{32}$/.test(id)) {
    throw new Error(`Row ${line}: image id "${id}" is not a 32 character hash.`);
  }
  return id;
}

function requireEnum(table, value, column, line) {
  if (!Object.prototype.hasOwnProperty.call(table, value)) {
    throw new Error(`Row ${line}: unknown ${column} "${value}".`);
  }
  return value;
}

function round(value, places) {
  const factor = 10 ** places;
  return Math.round(value * factor) / factor;
}

function build(csvPath) {
  const source = readFileSync(csvPath);
  const rows = parseCsv(source.toString("utf8"));
  const header = rows[0].map(clean);
  const index = Object.fromEntries(header.map((name, position) => [name, position]));

  for (const column of [
    "categoryEnum",
    "kindEnum",
    "shapeEnum",
    "shippingOptionEnum",
    "caratageGoldEnum",
    "qualityEnum",
    "price",
    "priceShowroom",
    "articul",
    "carats",
    "caratsCentral",
    "collections",
    "currency",
    "isCertificated",
    "stonesCount",
    "weight",
    "videoLink1",
  ]) {
    if (!(column in index)) throw new Error(`The export is missing the "${column}" column.`);
  }

  const products = new Map();
  let variantCount = 0;

  for (let i = 1; i < rows.length; i++) {
    if (rows[i].length < header.length) continue;
    const line = i + 1;
    const get = (column) => clean(rows[i][index[column]]);

    const currency = get("currency");
    if (currency !== "EUR") throw new Error(`Row ${line}: unsupported currency "${currency}".`);

    const articul = get("articul");
    if (!articul) throw new Error(`Row ${line}: missing articul.`);

    const shape = get("shapeEnum");
    if (shape) requireEnum(SHAPES, shape, "shape", line);

    const images = [];
    for (let slot = 1; slot <= 11; slot++) {
      const url = clean(rows[i][index[`imageLink${slot}`]] ?? "");
      if (url) images.push(imageId(url, line));
    }
    if (!images.length) throw new Error(`Row ${line}: no product images.`);

    const video = get("videoLink1");
    const caratsSide = Number(get("carats"));
    const caratsCentral = Number(get("caratsCentral"));

    const product = {
      articul,
      slug: slugify(articul),
      category: requireEnum(CATEGORIES, get("categoryEnum"), "category", line),
      kind: requireEnum(KINDS, get("kindEnum"), "kind", line),
      shape,
      shippingDays: SHIPPING[requireEnum(SHIPPING, get("shippingOptionEnum"), "shipping option", line)],
      collections: get("collections")
        .split(";")
        .map((value) => value.trim())
        .filter(Boolean)
        .map((value) => requireEnum(COLLECTIONS, value, "collection", line)),
      certificated: get("isCertificated") === "true",
      stones: Number(get("stonesCount")),
      // The export records the accent-stone weight separately from the centre
      // stone and leaves it at zero for single-stone pieces.
      caratsSide,
      caratsCentral,
      caratsTotal: round(caratsSide + caratsCentral, 3),
      weight: Number(get("weight")),
      images,
      video: video ? assetPath(video, line) : "",
      variants: [],
    };

    const existing = products.get(articul);
    if (!existing) {
      products.set(articul, product);
    } else {
      // Everything except the price axes must be identical across the rows of
      // one articul, otherwise grouping them into a single product would drop
      // information.
      for (const key of [
        "category",
        "kind",
        "shape",
        "shippingDays",
        "certificated",
        "stones",
        "caratsSide",
        "caratsCentral",
        "weight",
        "video",
      ]) {
        if (existing[key] !== product[key]) {
          throw new Error(`Row ${line}: articul "${articul}" changes ${key} between rows.`);
        }
      }
      if (existing.images.join() !== product.images.join()) {
        throw new Error(`Row ${line}: articul "${articul}" changes its images between rows.`);
      }
      if (existing.collections.join() !== product.collections.join()) {
        throw new Error(`Row ${line}: articul "${articul}" changes its collections between rows.`);
      }
    }

    const target = products.get(articul);
    const gold = requireEnum(GOLD, get("caratageGoldEnum"), "gold caratage", line);
    const quality = requireEnum(QUALITY, get("qualityEnum"), "quality", line);
    if (target.variants.some((variant) => variant[0] === gold && variant[1] === quality)) {
      throw new Error(`Row ${line}: duplicate ${gold}/${quality} variant for articul "${articul}".`);
    }

    const price = Number(get("price"));
    const showroom = Number(get("priceShowroom"));
    if (!(price > 0)) throw new Error(`Row ${line}: price "${get("price")}" is not positive.`);

    target.variants.push([gold, quality, round(price, 2), round(showroom, 2)]);
    variantCount++;
  }

  const goldOrder = Object.keys(GOLD);
  const qualityOrder = Object.keys(QUALITY);
  const list = [...products.values()].sort((a, b) => a.articul.localeCompare(b.articul));
  const slugs = new Set();

  for (const product of list) {
    if (slugs.has(product.slug)) throw new Error(`Articul slug "${product.slug}" is not unique.`);
    slugs.add(product.slug);
    product.variants.sort(
      (a, b) => goldOrder.indexOf(a[0]) - goldOrder.indexOf(b[0]) || qualityOrder.indexOf(a[1]) - qualityOrder.indexOf(b[1]),
    );
  }

  return {
    version: 1,
    source: {
      rows: variantCount,
      sha256: createHash("sha256").update(source).digest("hex"),
    },
    languages: LANGUAGES,
    assetBase: ASSET_BASE,
    imageBase: IMAGE_BASE,
    imageSuffix: IMAGE_SUFFIX,
    gold: GOLD,
    quality: QUALITY,
    categories: CATEGORIES,
    kinds: KINDS,
    shapes: SHAPES,
    collections: COLLECTIONS,
    products: list,
  };
}

const csvPath = process.argv[2];
if (!csvPath) {
  console.error('Usage: node tools/build-catalog-feed.mjs "path/to/products.csv"');
  process.exit(1);
}

const feed = build(resolve(csvPath));
mkdirSync(dirname(OUTPUT), { recursive: true });
writeFileSync(OUTPUT, `${JSON.stringify(feed)}\n`);

const images = new Set(feed.products.flatMap((product) => product.images));
console.log(
  `${feed.products.length} products, ${feed.source.rows} priced variants, ${images.size} images -> ${OUTPUT}`,
);
