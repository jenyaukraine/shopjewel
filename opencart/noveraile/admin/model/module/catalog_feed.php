<?php
namespace Opencart\Admin\Model\Extension\Noveraile\Module;

/**
 * Imports the supplier catalog feed built by tools/build-catalog-feed.mjs.
 *
 * The supplier prices every combination of articul, gold caratage and diamond
 * quality separately. Those combinations are sold as one product per articul
 * with a single required option, so each option value carries the exact
 * difference to the product's own price and no combination is approximated.
 *
 * The import is idempotent and resumable: products carry a checksum so an
 * unchanged run is a no-op, and the image download honours a wall clock budget
 * so a partial run simply continues on the next invocation.
 */
class CatalogFeed extends \Opencart\System\Engine\Model {
    private const FEED_FILE = 'noveraile/data/catalog-feed.json';
    private const IMAGE_DIR = 'catalog/noveraile/feed/';
    private const LANGUAGES = ['en-gb', 'de-de', 'cs-cz', 'ru-ru', 'uk-ua'];
    private const CATALOG_VERSION = 10;

    private array $feed = [];
    private array $copy = [];
    private array $ring_sizes = [];
    private float $deadline = 0.0;

    public function sync(array $options = []): array {
        $budget = max(0.0, (float)($options['budget'] ?? 0));
        $this->deadline = $budget > 0 ? microtime(true) + $budget : 0.0;
        $force = !empty($options['force']);

        $this->installTable();
        $feed = $this->feed();
        $languages = $this->languages();
        if (!$languages) {
            throw new \RuntimeException('No storefront language is installed, so the catalog cannot be imported.');
        }

        $report = ['products' => count($feed['products']), 'created' => 0, 'updated' => 0, 'skipped' => 0, 'removed' => 0, 'retired' => 0, 'images' => 0, 'failed' => 0, 'pending' => 0, 'complete' => false];

        // Runs even when the catalog pass is skipped: a duplicate can be
        // introduced by an import this model does not control.
        $report['retired'] = $this->retireDuplicates();

        // Deployments that do not change the feed must not pay for a full
        // rewrite, so the caller can ask for the catalog pass to be skipped
        // once the store already matches the shipped feed.
        if (!empty($options['if_needed']) && !$force && $this->upToDate($feed)) {
            $report['skipped'] = $report['products'];
            $report['pending'] = ($options['images'] ?? true) ? count($this->missingImages($feed)) : 0;
            $report['complete'] = $report['pending'] === 0;
            return $report;
        }

        // The catalog itself is never partially written — a storefront showing
        // half an assortment is worse than one that is briefly missing photos —
        // so only the image download honours the budget.
        if ($options['products'] ?? true) {
            $attributes = $this->attributes();
            $categories = $this->installCategories($languages);
            $options_map = $this->installOptions($feed, $languages);
            $stock_status_id = $this->stockStatusId();

            $existing = [];
            foreach ($this->db->query("SELECT `articul`, `product_id`, `checksum` FROM `" . DB_PREFIX . "noveraile_feed_product`")->rows as $row) {
                $existing[(string)$row['articul']] = ['product_id' => (int)$row['product_id'], 'checksum' => (string)$row['checksum']];
            }

            $this->load->model('catalog/product');
            foreach ($feed['products'] as $index => $product) {
                $articul = (string)$product['articul'];
                $payload = $this->productPayload($product, $index, $languages, $attributes, $categories, $options_map, $stock_status_id);
                // The availability date is stamped at import time, so leaving it
                // in would make every product look changed on the next day.
                $fingerprint = $payload;
                unset($fingerprint['date_available']);
                $checksum = sha1(json_encode($fingerprint, JSON_UNESCAPED_UNICODE));
                $known = $existing[$articul] ?? null;
                if (!$known) {
                    $adopted_id = $this->findProductByArticul($articul);
                    if ($adopted_id) $known = ['product_id' => $adopted_id, 'checksum' => ''];
                }

                if ($known && !$force && $known['checksum'] === $checksum && $this->productExists($known['product_id'])) {
                    $report['skipped']++;
                    continue;
                }

                if ($known && $this->productExists($known['product_id'])) {
                    $product_id = $known['product_id'];
                    $this->model_catalog_product->editProduct($product_id, $payload);
                    $report['updated']++;
                } else {
                    $product_id = (int)$this->model_catalog_product->addProduct($payload);
                    $report['created']++;
                }

                $this->db->query("REPLACE INTO `" . DB_PREFIX . "noveraile_feed_product` SET `articul` = '" . $this->db->escape($articul) . "', `product_id` = '" . (int)$product_id . "', `checksum` = '" . $this->db->escape($checksum) . "', `date_modified` = NOW()");
                $existing[$articul] = ['product_id' => $product_id, 'checksum' => $checksum];
            }

            $report['removed'] = $this->pruneProducts(array_column($feed['products'], 'articul'));
            $this->retireSeedCatalog();
            $report['retired'] += $this->retireDuplicates();

            // The storefront caches which metal, fineness and stone choices are
            // worth offering, and the catalog it counted has just changed.
            foreach (['product', 'category', 'noveraile.facet'] as $key) {
                $this->cache->delete($key);
            }
        }

        if ($options['images'] ?? true) {
            [$downloaded, $failed, $pending] = $this->downloadImages($feed);
            $report['images'] = $downloaded;
            $report['failed'] = $failed;
            $report['pending'] = $pending;
        } else {
            $report['pending'] = count($this->missingImages($feed));
        }

        $report['complete'] = $report['pending'] === 0;

        $this->setValue('module_noveraile_feed_state', json_encode([
            'checksum' => (string)($feed['source']['sha256'] ?? ''),
            'products' => $report['products'],
            'variants' => (int)($feed['source']['rows'] ?? 0),
            'complete' => $report['complete'],
            'date' => date('c')
        ], JSON_UNESCAPED_SLASHES));

        return $report;
    }

    /**
     * OpenCart's editValue only updates an existing row, so a setting this
     * importer owns has to be inserted the first time it is written.
     */
    private function setValue(string $key, string $value, string $code = 'module_noveraile'): void {
        $existing = $this->db->query("SELECT `setting_id` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' LIMIT 1");

        if ($existing->num_rows) {
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '" . $this->db->escape($value) . "', `serialized` = '0' WHERE `setting_id` = '" . (int)$existing->row['setting_id'] . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = '0', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "', `serialized` = '0'");
        }

        $this->config->set($key, $value);
    }

    public function status(): array {
        $this->installTable();
        $feed = $this->feed();
        $state = json_decode((string)$this->config->get('module_noveraile_feed_state'), true);
        $products = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "noveraile_feed_product` `f` INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `f`.`product_id`)");

        return [
            'feed_products' => count($feed['products']),
            'feed_variants' => (int)($feed['source']['rows'] ?? 0),
            'imported' => (int)($products->row['total'] ?? 0),
            'images_missing' => count($this->missingImages($feed)),
            'state' => is_array($state) ? $state : []
        ];
    }

    private function upToDate(array $feed): bool {
        if ((int)$this->config->get('module_noveraile_catalog_version') !== self::CATALOG_VERSION) {
            return false;
        }

        $state = json_decode((string)$this->config->get('module_noveraile_feed_state'), true);
        if (!is_array($state) || (string)($state['checksum'] ?? '') !== (string)($feed['source']['sha256'] ?? '')) {
            return false;
        }

        $imported = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "noveraile_feed_product` `f` INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `f`.`product_id`)");

        return (int)($imported->row['total'] ?? 0) === count($feed['products']);
    }

    private function installTable(): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "noveraile_feed_product` (`articul` VARCHAR(64) NOT NULL, `product_id` INT UNSIGNED NOT NULL, `checksum` CHAR(40) NOT NULL, `date_modified` DATETIME NOT NULL, PRIMARY KEY (`articul`), UNIQUE KEY `product_id` (`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function feed(): array {
        if ($this->feed) return $this->feed;

        $path = DIR_EXTENSION . self::FEED_FILE;
        if (!is_file($path)) {
            throw new \RuntimeException('The catalog feed is missing at ' . $path . '.');
        }

        $decoded = json_decode((string)file_get_contents($path), true);
        if (!is_array($decoded) || empty($decoded['products'])) {
            throw new \RuntimeException('The catalog feed could not be decoded.');
        }
        if ((int)($decoded['version'] ?? 0) !== 1) {
            throw new \RuntimeException('The catalog feed uses an unsupported layout version.');
        }

        return $this->feed = $decoded;
    }

    private function languages(): array {
        $this->load->model('localisation/language');
        $languages = [];
        foreach (self::LANGUAGES as $code) {
            $language = $this->model_localisation_language->getLanguageByCode($code);
            if ($language) $languages[$code] = (int)$language['language_id'];
        }
        return $languages;
    }

    /**
     * Every translated string in the feed is an array positioned against the
     * feed's own language list. Reading it by the position a language happens
     * to hold among the installed ones puts German text under Russian as soon
     * as a store does not install all five.
     */
    private function languageIndex(string $code): int {
        $index = array_search($code, (array)($this->feed()['languages'] ?? self::LANGUAGES), true);
        return $index === false ? 0 : (int)$index;
    }

    private function attributes(): array {
        $map = $this->config->get('module_noveraile_attribute_map');
        if (!is_array($map)) $map = json_decode((string)$map, true);
        if (!is_array($map) || empty($map['carat'])) {
            throw new \RuntimeException('The jewelry attribute set is missing. Run the module bootstrap before importing the catalog.');
        }
        return array_map('intval', array_filter($map, static fn($value): bool => (int)$value > 0));
    }

    private function brand(): string {
        $configured = trim((string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name')));
        return in_array($configured, ['', 'Your Store'], true) ? '6 Moments' : $configured;
    }

    private function stockStatusId(): int {
        $query = $this->db->query("SELECT `stock_status_id` FROM `" . DB_PREFIX . "stock_status` WHERE `name` LIKE 'Pre-Order%' ORDER BY `stock_status_id` LIMIT 1");
        return $query->num_rows ? (int)$query->row['stock_status_id'] : (int)$this->config->get('config_stock_status_id');
    }

    /**
     * Four supplier categories become the top level and the sixteen product
     * kinds their children, so the storefront navigation mirrors the feed.
     */
    private function installCategories(array $languages): array {
        $feed = $this->feed();
        $stored = json_decode((string)$this->config->get('module_noveraile_feed_categories'), true);
        $stored = is_array($stored) ? array_map('intval', $stored) : [];
        $brand = $this->brand();
        $codes = array_keys($languages);

        $this->load->model('catalog/category');
        $map = [];
        $sort = 0;

        foreach ($feed['categories'] as $key => $category) {
            $map[$key] = $this->upsertCategory($stored, (string)$category['slug'], (array)$category['name'], 0, ++$sort, $languages, $codes, $brand);
        }

        foreach ($feed['kinds'] as $key => $kind) {
            $parent_id = $map[(string)$kind['category']] ?? 0;
            $map[$key] = $this->upsertCategory($stored, (string)$kind['slug'], (array)$kind['name'], $parent_id, ++$sort, $languages, $codes, $brand);
        }

        $this->setValue('module_noveraile_feed_categories', json_encode($map));
        $this->setValue('module_noveraile_catalog_category_id', (string)($map['RING'] ?? 0));

        return $map;
    }

    private function upsertCategory(array $stored, string $slug, array $names, int $parent_id, int $sort_order, array $languages, array $codes, string $brand): int {
        $description = [];
        $seo = [];
        foreach ($codes as $code) {
            $name = (string)($names[$this->languageIndex($code)] ?? $names[0]);
            $description[$languages[$code]] = ['name' => $name, 'description' => '', 'meta_title' => $name . ' | ' . $brand, 'meta_description' => $name . ' — ' . $brand, 'meta_keyword' => ''];
            $seo[$languages[$code]] = 'noveraile-' . $slug . '-' . $code;
        }

        $payload = [
            'image' => '', 'parent_id' => $parent_id, 'sort_order' => $sort_order, 'status' => 1,
            'column' => 1, 'top' => $parent_id === 0 ? 1 : 0,
            'category_description' => $description, 'category_store' => [0], 'category_seo_url' => [0 => $seo],
            'category_filter' => [], 'category_layout' => []
        ];

        $category_id = (int)($stored[$slug] ?? 0);
        if (!$category_id) {
            // The earlier placeholder catalog claimed the same SEO keywords, so
            // adopt that category instead of creating a duplicate route.
            $owner = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "seo_url` WHERE `key` = 'category_id' AND `keyword` = '" . $this->db->escape((string)reset($seo)) . "' LIMIT 1");
            if ($owner->num_rows) $category_id = (int)$owner->row['value'];
        }

        if ($category_id && $this->db->query("SELECT `category_id` FROM `" . DB_PREFIX . "category` WHERE `category_id` = '" . $category_id . "' LIMIT 1")->num_rows) {
            $this->model_catalog_category->editCategory($category_id, $payload);
            return $category_id;
        }

        return (int)$this->model_catalog_category->addCategory($payload);
    }

    /**
     * One select option holds every gold and quality combination the supplier
     * prices. Naming the values "14K · D/VVS2" keeps the storefront's existing
     * quality facet working without a second lookup table.
     */
    private function installOptions(array $feed, array $languages): array {
        $stored = json_decode((string)$this->config->get('module_noveraile_feed_options'), true);
        $stored = is_array($stored) ? $stored : [];
        $codes = array_keys($languages);

        $names = [
            'en-gb' => 'Gold & diamond quality', 'de-de' => 'Gold & Diamantqualität',
            'cs-cz' => 'Zlato a kvalita diamantu', 'ru-ru' => 'Золото и качество бриллианта',
            'uk-ua' => 'Золото та якість діаманта'
        ];

        $values = [];
        $sort = 0;
        foreach ($feed['gold'] as $gold_key => $gold) {
            if ($gold_key === 'CT_9') continue;
            foreach ($feed['quality'] as $quality_key => $quality) {
                $values[$gold_key . '|' . $quality_key] = ['label' => $gold['karat'] . 'K · ' . $quality['label'], 'sort_order' => $sort++];
            }
        }

        $option_description = [];
        foreach ($codes as $code) {
            $option_description[$languages[$code]] = ['name' => $names[$code] ?? $names['en-gb']];
        }

        $option_value = [];
        foreach ($values as $key => $value) {
            $value_description = [];
            foreach ($languages as $language_id) $value_description[$language_id] = ['name' => $value['label']];
            $option_value[] = [
                'option_value_id' => (int)($stored['values'][$key] ?? 0),
                'image' => '', 'sort_order' => $value['sort_order'], 'option_value_description' => $value_description
            ];
        }

        $this->load->model('catalog/option');
        $payload = ['option_description' => $option_description, 'type' => 'select', 'validation' => '', 'sort_order' => 1, 'option_value' => $option_value];
        $option_id = (int)($stored['option_id'] ?? 0);

        if ($option_id && $this->db->query("SELECT `option_id` FROM `" . DB_PREFIX . "option` WHERE `option_id` = '" . $option_id . "' LIMIT 1")->num_rows) {
            $this->model_catalog_option->editOption($option_id, $payload);
        } else {
            $option_id = (int)$this->model_catalog_option->addOption($payload);
        }

        // Option values are recreated with fresh identifiers whenever the
        // option is rewritten, so the label is the only stable key.
        $value_ids = [];
        $rows = $this->db->query("SELECT `ov`.`option_value_id`, `ovd`.`name` FROM `" . DB_PREFIX . "option_value` `ov` INNER JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`ovd`.`option_value_id` = `ov`.`option_value_id` AND `ovd`.`language_id` = '" . (int)reset($languages) . "') WHERE `ov`.`option_id` = '" . $option_id . "'")->rows;
        $by_label = [];
        foreach ($rows as $row) $by_label[(string)$row['name']] = (int)$row['option_value_id'];
        foreach ($values as $key => $value) {
            if (isset($by_label[$value['label']])) $value_ids[$key] = $by_label[$value['label']];
        }

        $ring_size = $this->installRingSizeOption($languages, (int)($stored['ring_size_option_id'] ?? 0));
        $metal = $this->installMetalColorOption($languages, (int)($stored['metal_option_id'] ?? 0));
        $map = [
            'option_id' => $option_id,
            'values' => $value_ids,
            'ring_size_option_id' => $ring_size['option_id'],
            'metal_option_id' => $metal['option_id'],
            'metal_values' => $metal['values']
        ];
        $this->setValue('module_noveraile_feed_options', json_encode($map));
        if ($map['ring_size_option_id']) {
            $this->setValue('module_noveraile_ring_size_option_id', (string)$map['ring_size_option_id']);
        }

        return $map;
    }

    /**
     * Rings need a size before they can be made, so the existing storefront
     * size option is reused when present and created otherwise.
     */
    private function installRingSizeOption(array $languages, int $stored_option_id = 0): array {
        $option_id = (int)$this->config->get('module_noveraile_ring_size_option_id') ?: $stored_option_id;
        $names = ['en-gb' => 'Ring size', 'de-de' => 'Ringgröße', 'cs-cz' => 'Velikost prstenu', 'ru-ru' => 'Размер кольца', 'uk-ua' => 'Розмір каблучки'];
        $sizes = ['16 / EU 50', '16.5 / EU 52', '17 / EU 54', '18 / EU 56', '18.5 / EU 58', '19 / EU 60'];
        $values = [];
        foreach ($sizes as $sort => $label) $values[(string)$sort] = ['label' => $label, 'sort_order' => $sort];

        return $this->installSelectOption($languages, $option_id, $names, $values, 2);
    }

    private function installMetalColorOption(array $languages, int $option_id = 0): array {
        $names = [
            'en-gb' => 'Metal color', 'de-de' => 'Goldfarbe', 'cs-cz' => 'Barva zlata',
            'ru-ru' => 'Цвет металла', 'uk-ua' => 'Колір металу'
        ];
        $labels = [
            'white-gold' => ['White gold', 'Weißgold', 'Bílé zlato', 'Белое золото', 'Біле золото'],
            'yellow-gold' => ['Yellow gold', 'Gelbgold', 'Žluté zlato', 'Жёлтое золото', 'Жовте золото'],
            'rose-gold' => ['Rose gold', 'Roségold', 'Růžové zlato', 'Розовое золото', 'Рожеве золото']
        ];
        $values = [];
        foreach ($labels as $key => $translations) {
            $localized = [];
            foreach (array_keys($languages) as $position => $code) $localized[$code] = $translations[$position] ?? $translations[0];
            $values[$key] = ['labels' => $localized, 'label' => $translations[0], 'sort_order' => count($values)];
        }

        return $this->installSelectOption($languages, $option_id, $names, $values, 3);
    }

    private function installSelectOption(array $languages, int $option_id, array $names, array $values, int $sort_order): array {
        $description = [];
        foreach ($languages as $code => $language_id) $description[$language_id] = ['name' => $names[$code] ?? $names['en-gb']];

        $option_values = [];
        foreach ($values as $value) {
            $value_description = [];
            foreach ($languages as $code => $language_id) {
                $value_description[$language_id] = ['name' => (string)($value['labels'][$code] ?? $value['label'])];
            }
            $option_values[] = ['option_value_id' => 0, 'image' => '', 'sort_order' => $value['sort_order'], 'option_value_description' => $value_description];
        }

        $this->load->model('catalog/option');
        $payload = ['option_description' => $description, 'type' => 'select', 'validation' => '', 'sort_order' => $sort_order, 'option_value' => $option_values];
        if ($option_id && $this->db->query("SELECT `option_id` FROM `" . DB_PREFIX . "option` WHERE `option_id` = '" . $option_id . "' LIMIT 1")->num_rows) {
            $this->model_catalog_option->editOption($option_id, $payload);
        } else {
            $option_id = (int)$this->model_catalog_option->addOption($payload);
        }

        $value_ids = [];
        $rows = $this->db->query("SELECT `ov`.`option_value_id`, `ovd`.`name` FROM `" . DB_PREFIX . "option_value` `ov` INNER JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`ovd`.`option_value_id` = `ov`.`option_value_id` AND `ovd`.`language_id` = '" . (int)reset($languages) . "') WHERE `ov`.`option_id` = '" . $option_id . "'")->rows;
        $by_label = [];
        foreach ($rows as $row) $by_label[(string)$row['name']] = (int)$row['option_value_id'];
        foreach ($values as $key => $value) {
            if (isset($by_label[$value['label']])) $value_ids[$key] = $by_label[$value['label']];
        }

        if (count($value_ids) !== count($values)) throw new \RuntimeException('A required storefront option could not be installed completely.');
        return ['option_id' => $option_id, 'values' => $value_ids];
    }

    private function productPayload(array $product, int $index, array $languages, array $attributes, array $categories, array $options_map, int $stock_status_id): array {
        $feed = $this->feed();
        // 9K / 375 is intentionally not sold by 6 Moments. Keep it in the
        // supplier feed for traceability, but remove it before price, copy,
        // tags and required option values are built.
        $product['variants'] = array_values(array_filter(
            $product['variants'],
            static fn(array $variant): bool => (string)($variant[0] ?? '') !== 'CT_9'
        ));
        if (!$product['variants']) {
            throw new \RuntimeException(sprintf('Articul "%s" has no saleable 14K or 18K variants.', (string)$product['articul']));
        }
        $codes = array_keys($languages);
        $brand = $this->brand();
        $kind = $feed['kinds'][(string)$product['kind']];
        $category = $feed['categories'][(string)$product['category']];

        $prices = array_map(static fn(array $variant): float => (float)$variant[2], $product['variants']);
        $base = min($prices);

        $option_values = [];
        foreach ($product['variants'] as $variant) {
            $key = $variant[0] . '|' . $variant[1];
            $option_value_id = (int)($options_map['values'][$key] ?? 0);
            if (!$option_value_id) continue;
            $option_values[] = [
                'product_option_value_id' => 0, 'option_value_id' => $option_value_id,
                'quantity' => 0, 'subtract' => 0,
                'price' => round((float)$variant[2] - $base, 2), 'price_prefix' => '+',
                'points' => 0, 'points_prefix' => '+', 'weight' => 0, 'weight_prefix' => '+'
            ];
        }

        // A required option with a missing choice would put a piece on the
        // storefront that nobody can buy, so refuse the whole import instead.
        if (count($option_values) !== count($product['variants'])) {
            throw new \RuntimeException(sprintf('Articul "%s" is missing a gold and quality option value.', (string)$product['articul']));
        }

        $product_options = [[
            'product_option_id' => 0, 'option_id' => (int)$options_map['option_id'], 'type' => 'select',
            'value' => '', 'required' => 1, 'product_option_value' => $option_values
        ]];

        $metal_values = [];
        foreach (['white-gold', 'yellow-gold', 'rose-gold'] as $metal) {
            $option_value_id = (int)($options_map['metal_values'][$metal] ?? 0);
            if (!$option_value_id) continue;
            $metal_values[] = [
                'product_option_value_id' => 0, 'option_value_id' => $option_value_id,
                'quantity' => 0, 'subtract' => 0, 'price' => 0, 'price_prefix' => '+',
                'points' => 0, 'points_prefix' => '+', 'weight' => 0, 'weight_prefix' => '+'
            ];
        }
        if (count($metal_values) !== 3 || empty($options_map['metal_option_id'])) {
            throw new \RuntimeException(sprintf('Articul "%s" is missing a metal color option value.', (string)$product['articul']));
        }
        $product_options[] = [
            'product_option_id' => 0, 'option_id' => (int)$options_map['metal_option_id'], 'type' => 'select',
            'value' => '', 'required' => 1, 'product_option_value' => $metal_values
        ];

        if ((string)$product['category'] === 'RING') {
            $size_values = $this->ringSizeValues((int)$options_map['ring_size_option_id']);
            if (!$size_values) throw new \RuntimeException(sprintf('Articul "%s" is missing ring sizes.', (string)$product['articul']));
            $product_options[] = [
                'product_option_id' => 0, 'option_id' => (int)$options_map['ring_size_option_id'], 'type' => 'select',
                'value' => '', 'required' => 1, 'product_option_value' => $size_values
            ];
        }

        $descriptions = [];
        $seo = [];
        foreach ($codes as $code) {
            $position = $this->languageIndex($code);
            $language_id = $languages[$code];
            $name = trim(((string)($kind['name'][$position] ?? $kind['name'][0])) . ' ' . (string)$product['articul']);
            $descriptions[$language_id] = [
                'name' => $name,
                'description' => $this->describe($product, $code, $position),
                'tag' => implode(',', $this->tags($product, $kind, $category)),
                'meta_title' => $name . ' | ' . $brand,
                'meta_description' => $this->metaDescription($product, $code, $position),
                'meta_keyword' => ''
            ];
            $seo[$language_id] = (string)$product['slug'] . '-' . $code;
        }

        $category_ids = array_values(array_unique(array_filter([
            (int)($categories[(string)$product['category']] ?? 0),
            (int)($categories[(string)$product['kind']] ?? 0)
        ])));

        $images = [];
        foreach (array_slice($product['images'], 1) as $sort => $image) {
            $images[] = ['image' => $this->imagePath((string)$image), 'sort_order' => $sort];
        }

        return [
            'master_id' => 0, 'model' => (string)$product['articul'], 'location' => '', 'variant' => [], 'override' => [],
            // The pieces are made to order, so stock is not tracked and the
            // lead time is carried by the delivery tag instead.
            'quantity' => (int)$product['shippingDays'] === 3 ? 10 : 0, 'minimum' => 1, 'subtract' => 0,
            'stock_status_id' => $stock_status_id, 'date_available' => date('Y-m-d'), 'manufacturer_id' => 0,
            'shipping' => 1, 'price' => round($base, 2), 'points' => 0,
            'weight' => (float)$product['weight'] / 1000, 'weight_class_id' => (int)$this->config->get('config_weight_class_id'),
            'length' => 0, 'width' => 0, 'height' => 0, 'length_class_id' => (int)$this->config->get('config_length_class_id'),
            'status' => 1, 'tax_class_id' => 0, 'sort_order' => $index + 1,
            'image' => $this->imagePath((string)$product['images'][0]),
            'product_description' => $descriptions,
            'product_code' => [['code' => 'sku', 'value' => (string)$product['articul']]],
            // OpenCart 4.0 keeps the codes on the product row; 4.1 moved them
            // into product_code and ignores these keys.
            'sku' => (string)$product['articul'], 'upc' => '', 'ean' => '', 'jan' => '', 'isbn' => '', 'mpn' => '',
            'product_category' => $category_ids, 'product_store' => [0],
            'product_image' => $images, 'product_option' => $product_options,
            'product_attribute' => $this->productAttributes($product, $languages, $attributes),
            'product_seo_url' => [0 => $seo],
            'product_discount' => [], 'product_special' => [], 'product_related' => [],
            'product_download' => [], 'product_filter' => [], 'product_subscription' => [],
            'product_reward' => [], 'product_layout' => []
        ];
    }

    private function productAttributes(array $product, array $languages, array $attributes): array {
        $feed = $this->feed();
        $codes = array_keys($languages);
        $kind = $feed['kinds'][(string)$product['kind']];

        $gemstone = ['Diamond', 'Diamant', 'Diamant', 'Бриллиант', 'Діамант'];
        $styles = [
            'solitaire' => ['Solitaire', 'Solitär', 'Solitér', 'Солитер', 'Солітер'],
            'eternity' => ['Eternity band', 'Eternity-Ring', 'Eternity prsten', 'Кольцо вечности', 'Каблучка вічності'],
            'illusion' => ['Illusion', 'Illusion', 'Illusion', 'Иллюзия', 'Ілюзія'],
            'minimalism' => ['Minimalism', 'Minimalismus', 'Minimalismus', 'Минимализм', 'Мінімалізм'],
            'pendant' => ['Pendant', 'Anhänger', 'Přívěsek', 'Подвеска', 'Підвіска'],
            'studs' => ['Stud earrings', 'Ohrstecker', 'Pecky', 'Пусеты', 'Пусети'],
            'chain-bracelet' => ['Chain bracelet', 'Kettenarmband', 'Řetízkový náramek', 'Цепочный браслет', 'Ланцюжковий браслет']
        ];
        $origins = [
            'both' => ['Natural and lab-grown', 'Natürlich und laborgezüchtet', 'Přírodní a laboratorní', 'Натуральный и лабораторный', 'Натуральний і лабораторний'],
            'natural' => ['Natural', 'Natürlich', 'Přírodní', 'Натуральный', 'Натуральний'],
            'lab-grown' => ['Lab-grown', 'Laborgezüchtet', 'Laboratorní', 'Лабораторный', 'Лабораторний']
        ];
        $metals = [
            ['White gold · Yellow gold · Rose gold', 'Weißgold · Gelbgold · Roségold', 'Bílé zlato · Žluté zlato · Růžové zlato', 'Белое золото · Жёлтое золото · Розовое золото', 'Біле золото · Жовте золото · Рожеве золото']
        ];

        $origin = $origins[$this->originKey($product)];
        $style = $styles[(string)$kind['style']] ?? $gemstone;
        $shape = (string)$product['shape'] !== '' ? (array)$feed['shapes'][(string)$product['shape']]['name'] : [];
        $fineness = implode(' · ', array_map(fn(string $key): string => $this->feedGold($key), $this->goldKeys($product)));

        $texts = [
            'gemstone' => $gemstone,
            'stone_origin' => $origin,
            'style' => $style,
            'stone_shape' => $shape,
            'carat' => array_fill(0, count($codes), $this->number((float)$product['caratsTotal'], 2)),
            'fineness' => array_fill(0, count($codes), $fineness)
        ];
        $texts['metal'] = $metals[0];

        $product_attributes = [];
        foreach ($texts as $key => $values) {
            $attribute_id = (int)($attributes[$key] ?? 0);
            if (!$attribute_id || !$values) continue;
            $description = [];
            foreach ($codes as $code) {
                $description[$languages[$code]] = ['text' => (string)($values[$this->languageIndex($code)] ?? $values[0])];
            }
            $product_attributes[] = ['attribute_id' => $attribute_id, 'product_attribute_description' => $description];
        }

        return $product_attributes;
    }

    private function feedGold(string $key): string {
        $feed = $this->feed();
        return (string)($feed['gold'][$key]['fineness'] ?? $key);
    }

    private function goldKeys(array $product): array {
        $keys = [];
        foreach ($product['variants'] as $variant) $keys[(string)$variant[0]] = true;
        return array_keys($keys);
    }

    private function originKey(array $product): string {
        $feed = $this->feed();
        $origins = [];
        foreach ($product['variants'] as $variant) {
            $origins[(string)($feed['quality'][(string)$variant[1]]['origin'] ?? 'natural')] = true;
        }
        if (count($origins) > 1) return 'both';
        return $origins ? (string)array_key_first($origins) : 'natural';
    }

    private function ringSizeValues(int $option_id): array {
        if (isset($this->ring_sizes[$option_id])) return $this->ring_sizes[$option_id];

        $values = [];
        foreach ($this->db->query("SELECT `option_value_id` FROM `" . DB_PREFIX . "option_value` WHERE `option_id` = '" . $option_id . "' ORDER BY `sort_order`")->rows as $row) {
            $values[] = [
                'product_option_value_id' => 0, 'option_value_id' => (int)$row['option_value_id'],
                'quantity' => 0, 'subtract' => 0, 'price' => 0, 'price_prefix' => '+',
                'points' => 0, 'points_prefix' => '+', 'weight' => 0, 'weight_prefix' => '+'
            ];
        }

        return $this->ring_sizes[$option_id] = $values;
    }

    private function tags(array $product, array $kind, array $category): array {
        $feed = $this->feed();
        $tags = [
            ['rings' => 'ring', 'earrings' => 'earrings', 'necklaces' => 'necklace', 'bracelets' => 'bracelet'][(string)$category['type']] ?? (string)$category['type'],
            (string)$kind['moment'],
            'delivery-' . (int)$product['shippingDays'],
            'diamond',
            (string)$kind['style']
        ];

        if ((string)$product['shape'] !== '') $tags[] = (string)$feed['shapes'][(string)$product['shape']]['slug'];
        foreach ($product['collections'] as $collection) $tags[] = (string)$feed['collections'][(string)$collection]['slug'];

        $origin = $this->originKey($product);
        foreach ($origin === 'both' ? ['natural', 'lab-grown'] : [$origin] as $value) $tags[] = $value;
        foreach ($this->goldKeys($product) as $gold) $tags[] = $this->feedGold($gold);
        foreach (['white-gold', 'yellow-gold', 'rose-gold'] as $metal) $tags[] = $metal;
        if (!empty($product['certificated'])) $tags[] = 'certificated';

        return array_values(array_unique(array_filter($tags)));
    }

    private function describe(array $product, string $code, int $position): string {
        $feed = $this->feed();
        $kind = (string)($feed['kinds'][(string)$product['kind']]['name'][$position] ?? $feed['kinds'][(string)$product['kind']]['name'][0]);
        $copy = $this->copy()[$code] ?? $this->copy()['en-gb'];

        $karats = array_map(fn(string $key): string => (string)$feed['gold'][$key]['karat'], $this->goldKeys($product));
        $last = array_pop($karats);
        $gold_list = $karats ? implode(', ', $karats) . ' ' . $copy['or'] . ' ' . $last : $last;
        $origin = $this->originKey($product);

        $rows = [
            [$copy['centre'], $this->number((float)$product['caratsCentral'], 2) . ' ' . $copy['ct']],
            [$copy['total'], $this->number((float)$product['caratsTotal'], 2) . ' ' . $copy['ct'] . ' · ' . (int)$product['stones'] . ' ' . $this->stoneWord((int)$product['stones'], $copy)],
            [$copy['gold_weight'], $this->number((float)$product['weight'], 2) . ' ' . $copy['g']],
            [$copy['gold'], implode(' · ', array_map(fn(string $key): string => $feed['gold'][$key]['karat'] . 'K (' . $feed['gold'][$key]['fineness'] . ')', $this->goldKeys($product)))],
            [$copy['quality'], implode(' · ', $this->qualityLabels($product))],
            [$copy['certificate'], empty($product['certificated']) ? $copy['on_request'] : $copy['included']],
            [$copy['delivery'], (int)$product['shippingDays'] . ' ' . $copy['days']]
        ];

        if ((string)$product['shape'] !== '') {
            $shape = (array)$feed['shapes'][(string)$product['shape']]['name'];
            array_splice($rows, 2, 0, [[$copy['cut'], (string)($shape[$position] ?? $shape[0])]]);
        }

        if ($product['collections']) {
            $names = [];
            foreach ($product['collections'] as $collection) {
                $names[] = (string)($feed['collections'][(string)$collection]['name'][$position] ?? $collection);
            }
            $rows[] = [$copy['collection'], implode(' · ', $names)];
        }

        $items = '';
        foreach ($rows as [$label, $value]) {
            $items .= '<li><strong>' . $this->escape($label) . ':</strong> ' . $this->escape($value) . '</li>';
        }

        $intro = sprintf($copy['intro'], $this->escape($kind), $this->escape($gold_list), $this->escape($copy['stones_' . $origin]));

        return '<p>' . $intro . '</p><ul class="six-spec">' . $items . '</ul>';
    }

    private function metaDescription(array $product, string $code, int $position): string {
        $feed = $this->feed();
        $kind = (string)($feed['kinds'][(string)$product['kind']]['name'][$position] ?? $feed['kinds'][(string)$product['kind']]['name'][0]);
        $copy = $this->copy()[$code] ?? $this->copy()['en-gb'];

        return sprintf(
            '%s %s · %s %s · %s',
            $kind,
            (string)$product['articul'],
            $this->number((float)$product['caratsTotal'], 2),
            $copy['ct'],
            implode(' / ', $this->qualityLabels($product))
        );
    }

    private function qualityLabels(array $product): array {
        $feed = $this->feed();
        $labels = [];
        foreach ($product['variants'] as $variant) {
            $labels[(string)($feed['quality'][(string)$variant[1]]['label'] ?? $variant[1])] = true;
        }
        return array_keys($labels);
    }

    private function copy(): array {
        if ($this->copy) return $this->copy;

        return $this->copy = [
            'en-gb' => [
                'intro' => '%1$s crafted in %2$s carat gold and set with %3$s. Choose the gold and the diamond quality below — every combination is priced individually.',
                'stones_both' => 'natural and lab-grown diamonds', 'stones_natural' => 'natural diamonds', 'stones_lab-grown' => 'lab-grown diamonds',
                'or' => 'or', 'centre' => 'Centre diamond', 'total' => 'Total diamond weight',
                'plural' => 'simple', 'stones_one' => 'stone', 'stones_few' => 'stones', 'stones_many' => 'stones',
                'cut' => 'Diamond cut', 'gold_weight' => 'Gold weight', 'gold' => 'Gold', 'quality' => 'Diamond quality',
                'certificate' => 'Certificate', 'included' => 'Included', 'on_request' => 'On request',
                'delivery' => 'Delivery', 'days' => 'working days', 'collection' => 'Collection', 'ct' => 'ct', 'g' => 'g'
            ],
            'de-de' => [
                'intro' => '%1$s aus %2$s Karat Gold, besetzt mit %3$s. Wählen Sie unten Gold und Diamantqualität — jede Kombination wird einzeln bepreist.',
                'stones_both' => 'natürlichen und im Labor gezüchteten Diamanten', 'stones_natural' => 'natürlichen Diamanten', 'stones_lab-grown' => 'im Labor gezüchteten Diamanten',
                'or' => 'oder', 'centre' => 'Mittelstein', 'total' => 'Gesamtdiamantgewicht',
                'plural' => 'simple', 'stones_one' => 'Stein', 'stones_few' => 'Steine', 'stones_many' => 'Steine',
                'cut' => 'Diamantschliff', 'gold_weight' => 'Goldgewicht', 'gold' => 'Gold', 'quality' => 'Diamantqualität',
                'certificate' => 'Zertifikat', 'included' => 'Inklusive', 'on_request' => 'Auf Anfrage',
                'delivery' => 'Lieferung', 'days' => 'Werktage', 'collection' => 'Kollektion', 'ct' => 'ct', 'g' => 'g'
            ],
            'cs-cz' => [
                'intro' => '%1$s ze zlata %2$s karátů, osazený %3$s. Níže si vyberte zlato a kvalitu diamantu — každá kombinace má vlastní cenu.',
                'stones_both' => 'přírodními i laboratorními diamanty', 'stones_natural' => 'přírodními diamanty', 'stones_lab-grown' => 'laboratorními diamanty',
                'or' => 'nebo', 'centre' => 'Středový diamant', 'total' => 'Celková hmotnost diamantů',
                'plural' => 'slavic', 'stones_one' => 'kámen', 'stones_few' => 'kameny', 'stones_many' => 'kamenů',
                'cut' => 'Brus diamantu', 'gold_weight' => 'Hmotnost zlata', 'gold' => 'Zlato', 'quality' => 'Kvalita diamantu',
                'certificate' => 'Certifikát', 'included' => 'Součástí', 'on_request' => 'Na vyžádání',
                'delivery' => 'Doručení', 'days' => 'pracovních dnů', 'collection' => 'Kolekce', 'ct' => 'ct', 'g' => 'g'
            ],
            'ru-ru' => [
                'intro' => '%1$s из золота %2$s карат с %3$s. Ниже выберите золото и качество бриллианта — цена рассчитывается для каждой комбинации.',
                'stones_both' => 'натуральными и лабораторными бриллиантами', 'stones_natural' => 'натуральными бриллиантами', 'stones_lab-grown' => 'лабораторными бриллиантами',
                'or' => 'или', 'centre' => 'Центральный бриллиант', 'total' => 'Общий вес бриллиантов',
                'plural' => 'slavic', 'stones_one' => 'камень', 'stones_few' => 'камня', 'stones_many' => 'камней',
                'cut' => 'Огранка', 'gold_weight' => 'Вес золота', 'gold' => 'Золото', 'quality' => 'Качество бриллианта',
                'certificate' => 'Сертификат', 'included' => 'Входит в комплект', 'on_request' => 'По запросу',
                'delivery' => 'Доставка', 'days' => 'рабочих дней', 'collection' => 'Коллекция', 'ct' => 'кар.', 'g' => 'г'
            ],
            'uk-ua' => [
                'intro' => '%1$s із золота %2$s карат з %3$s. Нижче оберіть золото та якість діаманта — ціна розраховується для кожної комбінації.',
                'stones_both' => 'природними та лабораторними діамантами', 'stones_natural' => 'природними діамантами', 'stones_lab-grown' => 'лабораторними діамантами',
                'or' => 'або', 'centre' => 'Центральний діамант', 'total' => 'Загальна вага діамантів',
                'plural' => 'slavic', 'stones_one' => 'камінь', 'stones_few' => 'камені', 'stones_many' => 'каменів',
                'cut' => 'Огранювання', 'gold_weight' => 'Вага золота', 'gold' => 'Золото', 'quality' => 'Якість діаманта',
                'certificate' => 'Сертифікат', 'included' => 'Входить у комплект', 'on_request' => 'На запит',
                'delivery' => 'Доставка', 'days' => 'робочих днів', 'collection' => 'Колекція', 'ct' => 'кар.', 'g' => 'г'
            ]
        ];
    }

    /**
     * Czech, Russian and Ukrainian pick a different noun form for one, for two
     * to four and for everything else, and they decide on the last digit — so
     * 21 takes the singular there while English keeps the plural.
     */
    private function stoneWord(int $count, array $copy): string {
        $count = abs($count);
        if (($copy['plural'] ?? 'simple') !== 'slavic') return $count === 1 ? $copy['stones_one'] : $copy['stones_many'];
        if ($count % 10 === 1 && $count % 100 !== 11) return $copy['stones_one'];
        if ($count % 10 >= 2 && $count % 10 <= 4 && ($count % 100 < 12 || $count % 100 > 14)) return $copy['stones_few'];

        return $copy['stones_many'];
    }

    private function number(float $value, int $decimals): string {
        return rtrim(rtrim(number_format($value, $decimals, '.', ''), '0'), '.') ?: '0';
    }

    private function escape(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function imagePath(string $id): string {
        return self::IMAGE_DIR . substr($id, 0, 2) . '/' . $id . '.webp';
    }

    private function productExists(int $product_id): bool {
        return (bool)$this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . $product_id . "' LIMIT 1")->num_rows;
    }

    private function findProductByArticul(string $articul): int {
        $value = $this->db->escape($articul);
        $match = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` = '" . $value . "' ORDER BY `status` DESC, `product_id` LIMIT 1");
        if ($match->num_rows) return (int)$match->row['product_id'];

        if ($this->usesProductCodeTable()) {
            $match = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product_code` WHERE `code` = 'sku' AND `value` = '" . $value . "' ORDER BY `product_id` LIMIT 1");
        } else {
            $match = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `sku` = '" . $value . "' ORDER BY `status` DESC, `product_id` LIMIT 1");
        }

        return $match->num_rows ? (int)$match->row['product_id'] : 0;
    }

    private function pruneProducts(array $articuls): int {
        $keep = array_map(fn(string $articul): string => "'" . $this->db->escape($articul) . "'", $articuls);
        $sql = "SELECT `articul`, `product_id` FROM `" . DB_PREFIX . "noveraile_feed_product`";
        if ($keep) $sql .= " WHERE `articul` NOT IN (" . implode(',', $keep) . ")";

        $this->load->model('catalog/product');
        $removed = 0;
        foreach ($this->db->query($sql)->rows as $row) {
            $this->model_catalog_product->deleteProduct((int)$row['product_id']);
            $this->db->query("DELETE FROM `" . DB_PREFIX . "noveraile_feed_product` WHERE `articul` = '" . $this->db->escape((string)$row['articul']) . "'");
            $removed++;
        }

        return $removed;
    }

    /**
     * This assortment was already loaded into the store once, before the feed
     * existed, by an import this model does not own. Those products carry the
     * same supplier articuls, so every piece would be offered twice.
     *
     * The feed is the authority on its own articuls, but the earlier rows are
     * only disabled, never deleted: they leave the storefront immediately and
     * stay in admin until a merchant decides what to do with them.
     */
    private function retireDuplicates(): int {
        $sources = ["SELECT `p`.`product_id` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "noveraile_feed_product` `f` ON (`f`.`articul` = `p`.`model`) WHERE `p`.`product_id` <> `f`.`product_id` AND `p`.`status` = '1'"];

        if ($this->usesProductCodeTable()) {
            $sources[] = "SELECT `pc`.`product_id` FROM `" . DB_PREFIX . "product_code` `pc` INNER JOIN `" . DB_PREFIX . "noveraile_feed_product` `f` ON (`f`.`articul` = `pc`.`value`) INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `pc`.`product_id`) WHERE `pc`.`code` = 'sku' AND `pc`.`product_id` <> `f`.`product_id` AND `p`.`status` = '1'";
        } else {
            $sources[] = "SELECT `p`.`product_id` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "noveraile_feed_product` `f` ON (`f`.`articul` = `p`.`sku`) WHERE `p`.`product_id` <> `f`.`product_id` AND `p`.`status` = '1'";
        }

        $product_ids = [];
        foreach ($sources as $sql) {
            foreach ($this->db->query($sql)->rows as $row) $product_ids[(int)$row['product_id']] = true;
        }
        if (!$product_ids) return 0;

        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `status` = '0', `date_modified` = NOW() WHERE `product_id` IN (" . implode(',', array_keys($product_ids)) . ")");
        foreach (['product', 'category', 'noveraile.facet'] as $key) {
            $this->cache->delete($key);
        }

        return count($product_ids);
    }

    private function usesProductCodeTable(): bool {
        return (bool)$this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "product_code'")->num_rows;
    }

    /**
     * The bundled ten piece placeholder catalog is replaced by the supplier
     * feed, so its products are removed and the seeder is pinned past its last
     * migration instead of recreating them on the next deployment.
     */
    private function retireSeedCatalog(): void {
        $this->load->model('catalog/product');
        foreach ($this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` LIKE 'NVR-%'")->rows as $row) {
            $this->model_catalog_product->deleteProduct((int)$row['product_id']);
        }

        $this->setValue('module_noveraile_catalog_version', (string)self::CATALOG_VERSION);

        // Made to order pieces carry no stock, so checkout must not refuse them.
        $this->setValue('config_stock_checkout', '1', 'config');
    }

    private function missingImages(array $feed): array {
        $missing = [];
        // Primary images first: they are what the listings and the cart show.
        foreach ([0, 1] as $pass) {
            foreach ($feed['products'] as $product) {
                $images = $pass === 0 ? array_slice($product['images'], 0, 1) : array_slice($product['images'], 1);
                foreach ($images as $id) {
                    $path = DIR_IMAGE . $this->imagePath((string)$id);
                    if (!isset($missing[(string)$id]) && !is_file($path)) $missing[(string)$id] = $path;
                }
            }
        }
        return $missing;
    }

    private function downloadImages(array $feed): array {
        $missing = $this->missingImages($feed);
        if (!$missing) return [0, 0, 0];
        if (!function_exists('curl_multi_init')) {
            throw new \RuntimeException('The PHP cURL extension is required to download catalog images.');
        }

        $base = (string)$feed['imageBase'];
        $suffix = (string)$feed['imageSuffix'];
        $queue = array_keys($missing);
        $downloaded = 0;
        $failed = 0;
        $position = 0;
        $concurrency = 8;

        while ($position < count($queue)) {
            if ($this->deadline > 0 && microtime(true) >= $this->deadline) break;

            $batch = array_slice($queue, $position, $concurrency);
            $position += count($batch);
            $multi = curl_multi_init();
            $handles = [];

            foreach ($batch as $id) {
                $handle = curl_init($base . $id . $suffix);
                curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_FAILONERROR => true
                ]);
                curl_multi_add_handle($multi, $handle);
                $handles[$id] = $handle;
            }

            do {
                $status = curl_multi_exec($multi, $running);
                if ($running) curl_multi_select($multi, 1.0);
            } while ($running && $status === CURLM_OK);

            foreach ($handles as $id => $handle) {
                $body = curl_multi_getcontent($handle);
                $code = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
                curl_multi_remove_handle($multi, $handle);
                curl_close($handle);

                if ($code !== 200 || !is_string($body) || strlen($body) < 128 || substr($body, 0, 4) !== 'RIFF') {
                    $failed++;
                    continue;
                }

                if ($this->storeImage($missing[$id], $body)) $downloaded++;
                else $failed++;
            }

            curl_multi_close($multi);
        }

        return [$downloaded, $failed, count($this->missingImages($feed))];
    }

    private function storeImage(string $path, string $body): bool {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) return false;

        // Write through a temporary file so a killed run never leaves a
        // truncated image that later looks downloaded.
        $temporary = $path . '.' . getmypid() . '.part';
        if (file_put_contents($temporary, $body) !== strlen($body)) {
            @unlink($temporary);
            return false;
        }
        @chmod($temporary, 0644);

        if (!rename($temporary, $path)) {
            @unlink($temporary);
            return false;
        }

        // The download keeps running in the background after the container has
        // already fixed ownership of the image volume, so match the volume
        // owner here instead of relying on that pass.
        $owner = @fileowner(DIR_IMAGE);
        $group = @filegroup(DIR_IMAGE);
        // Shared hosts commonly disable ownership-changing functions. The file
        // is already written by the web account there, so ownership repair is
        // only needed (and attempted) when PHP actually exposes the helpers.
        if ($owner !== false && function_exists('chown')) @\chown($path, $owner);
        if ($group !== false && function_exists('chgrp')) @\chgrp($path, $group);

        return true;
    }
}
