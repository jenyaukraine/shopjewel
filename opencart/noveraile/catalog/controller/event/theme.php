<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Event;

class Theme extends \Opencart\System\Engine\Controller {
    private ?int $gram_weight_class_id = null;

    private function enabled(): bool {
        return (bool)$this->config->get('module_noveraile_status');
    }

    /** Leave a view alone when an earlier extension has already replaced it. */
    private function claimView(string &$route, array $core_routes, string $theme_route): bool {
        if (!in_array($route, $core_routes, true)) return false;
        $route = $theme_route;
        return true;
    }

    private function blogRoute(): string {
        $route = trim((string)$this->config->get('module_noveraile_blog_route'));
        return preg_match('#^[a-z0-9_]+(?:/[a-z0-9_]+)+(?:\.[a-zA-Z0-9_]+)?$#', $route) ? $route : 'cms/blog';
    }

    private function words(array &$data): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $configured_brand = trim((string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name')));
        $brand = in_array($configured_brand, ['', 'Your Store'], true) ? '6 Moments' : $configured_brand;
        foreach ($this->language->all() as $key => $value) {
            if (str_starts_with($key, 'six_')) $data[$key] = is_string($value) ? str_replace(['NOVERAILE', 'Six Moments'], $brand, $value) : $value;
        }
        $data['six_brand_name'] = $brand;
        $data['six_asset'] = '/image/catalog/noveraile/';
    }

    public function header(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['common/header'], 'extension/noveraile/common/header')) return;
        $this->refreshCurrencyRates();
        $this->words($data);

        if (($data['title'] ?? '') === 'Your Store') {
            $data['title'] = $data['six_brand_name'];
        }

        $data['six_stylesheet'] = 'extension/noveraile/catalog/view/stylesheet/noveraile.css?v=2.6.2.0';
        $data['six_script'] = 'extension/noveraile/catalog/view/javascript/noveraile.js?v=2.6.2.0';
        $data['six_favicon'] = rtrim(HTTP_SERVER, '/') . '/image/catalog/noveraile/favicon.svg?v=2';
        $data['six_og_image'] = rtrim(HTTP_SERVER, '/') . '/image/catalog/noveraile/og-oled.png';
        $data['six_native_menu_status'] = (bool)$this->config->get('module_noveraile_native_menu_status');
        $data['six_canonical'] = '';
        foreach ((array)($data['links'] ?? []) as $link) {
            if (($link['rel'] ?? '') === 'canonical') { $data['six_canonical'] = (string)($link['href'] ?? ''); break; }
        }
        $data['six_home'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));
        $data['six_catalog_url'] = $this->url->link('extension/noveraile/page/catalog', 'language=' . $this->config->get('config_language'));
        $data['six_contact_url'] = (string)($this->config->get('module_noveraile_whatsapp') ?: 'https://wa.me/491707647729');
        $data['six_about_url'] = $this->url->link('extension/noveraile/page/about', 'language=' . $this->config->get('config_language'));
        $data['six_diamonds_url'] = $this->url->link('extension/noveraile/page/diamonds', 'language=' . $this->config->get('config_language'));
        $data['six_quiz_url'] = $this->url->link('extension/noveraile/page/quiz', 'language=' . $this->config->get('config_language'));
        $data['six_journal_url'] = $this->url->link($this->blogRoute(), 'language=' . $this->config->get('config_language'));
        $data['six_special'] = $this->url->link('extension/noveraile/page/catalog', 'language=' . $this->config->get('config_language') . '&sale=1');
        $data['six_search_action'] = $data['six_catalog_url'];
        $data['six_search_suggest'] = $this->url->link('extension/noveraile/search.suggest', 'language=' . $this->config->get('config_language'));
        $data['six_cart_count'] = $this->cart->countProducts();
        $data['six_language_code'] = $this->config->get('config_language');
        $data['six_currency_code'] = $this->session->data['currency'] ?? $this->config->get('config_currency');
        $data['six_phone'] = trim((string)($this->config->get('module_noveraile_phone') ?: $this->config->get('config_telephone')));
        $data['six_phone_href'] = preg_replace('/[^+0-9]/', '', $data['six_phone']);

        $data['six_categories'] = [];
        $data['six_mega_menu_status'] = (bool)$this->config->get('module_noveraile_mega_menu_status');
        $data['six_mega_menu_title'] = (string)($this->config->get('module_noveraile_mega_menu_title') ?: $data['six_catalog']);
        $data['six_mega_menu_promo_text'] = (string)($this->config->get('module_noveraile_mega_menu_promo_text') ?: $data['six_specials']);
        $data['six_mega_menu_promo_url'] = (string)($this->config->get('module_noveraile_mega_menu_promo_url') ?: $data['six_special']);
        // Demo upgrades can leave duplicate category records whose names differ
        // only by case or invisible whitespace. Normalize them in PHP so the
        // navigation stays unique across MySQL collations.
        $category_query = $this->db->query("SELECT c.category_id, cd.name, c.sort_order, COUNT(DISTINCT p.product_id) AS product_total FROM `" . DB_PREFIX . "category` c INNER JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = c.category_id) INNER JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p2c.category_id = c.category_id) INNER JOIN `" . DB_PREFIX . "product` p ON (p.product_id = p2c.product_id AND p.status = '1') WHERE c.status = '1' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY c.category_id, cd.name, c.sort_order ORDER BY c.sort_order ASC, cd.name ASC, c.category_id ASC LIMIT 60");
        $category_names = [];
        foreach ($category_query->rows as $category) {
            $name = preg_replace('/[\p{Z}\s]+/u', ' ', trim((string)$category['name'])) ?: trim((string)$category['name']);
            $key = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
            if ($name === '' || isset($category_names[$key])) continue;
            $category_names[$key] = true;
            $data['six_categories'][] = [
                'name' => $name,
                'icon' => $this->categoryIcon($name),
                'total' => (int)($category['product_total'] ?? 0),
                'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . (int)$category['category_id'])
            ];
            if (count($data['six_categories']) >= 12) break;
        }
        if (!$data['six_categories']) {
            foreach ([
                'rings' => $data['six_type_rings'], 'earrings' => $data['six_type_earrings'],
                'necklaces' => $data['six_type_necklaces'], 'bracelets' => $data['six_type_bracelets'],
                'wedding' => $data['six_type_wedding']
            ] as $type => $name) {
                $data['six_categories'][] = ['name' => $name, 'icon' => $this->categoryIcon($name), 'href' => $this->url->link('extension/noveraile/page/catalog', 'language=' . $this->config->get('config_language') . '&type=' . $type)];
            }
        }
    }

    private function categoryIcon(string $name): string {
        $value = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
        $icons = [
            'wedding' => ['wedding', 'обруч', 'hochzeit', 'snubn', 'svateb'],
            'earring' => ['earring', 'сереж', 'серьг', 'ohrring', 'náuš'],
            'necklace' => ['necklace', 'pendant', 'підвіс', 'подвес', 'halskett', 'náhrdel'],
            'bracelet' => ['bracelet', 'браслет', 'armbänd', 'náram'],
            'ring' => ['ring', 'каблуч', 'кольц', 'prsten'],
        ];
        foreach ($icons as $icon => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($value, $needle)) return $icon;
            }
        }
        return 'jewel';
    }

    /** Refresh the four storefront currencies at most once every twelve hours. */
    private function refreshCurrencyRates(): void {
        $last_update = (int)$this->config->get('module_noveraile_currency_updated_at');
        if ($last_update > time() - 43200 || !function_exists('curl_init')) return;

        // Claim the refresh window before the network request to prevent a traffic
        // spike from starting several identical API calls at once.
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `key` = 'module_noveraile_currency_updated_at'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = '" . (int)$this->config->get('config_store_id') . "', `code` = 'module_noveraile', `key` = 'module_noveraile_currency_updated_at', `value` = '" . time() . "', `serialized` = '0'");

        $handle = curl_init('https://open.er-api.com/v6/latest/USD');
        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 4, CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
        $response = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if (!is_string($response) || $status !== 200) return;

        $payload = json_decode($response, true);
        $rates = is_array($payload['rates'] ?? null) ? $payload['rates'] : [];
        $base = strtoupper((string)$this->config->get('config_currency'));
        $base_rate = (float)($rates[$base] ?? ($base === 'USD' ? 1 : 0));
        if ($base_rate <= 0) return;

        foreach (['USD', 'EUR', 'CZK', 'UAH'] as $currency) {
            $rate = (float)($rates[$currency] ?? 0);
            if ($rate <= 0) continue;
            $value = $currency === $base ? 1.0 : $rate / $base_rate;
            $this->db->query("UPDATE `" . DB_PREFIX . "currency` SET `value` = '" . (float)$value . "', `date_modified` = NOW() WHERE `code` = '" . $this->db->escape($currency) . "'");
        }
    }

    public function footer(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['common/footer'], 'extension/noveraile/common/footer')) return;
        $this->words($data);
        $data['six_script'] = 'extension/noveraile/catalog/view/javascript/noveraile.js?v=2.6.2.0';
        $lang = 'language=' . $this->config->get('config_language');
        $data['six_home'] = $this->url->link('common/home', $lang);
        $data['six_about_url'] = $this->url->link('extension/noveraile/page/about', $lang);
        $data['six_diamonds_url'] = $this->url->link('extension/noveraile/page/diamonds', $lang);
        $data['six_shipping_url'] = $this->url->link('extension/noveraile/page/shipping', $lang);
        $data['six_faq_url'] = $this->url->link('extension/noveraile/page/faq', $lang);
        $data['six_journal_url'] = $this->url->link($this->blogRoute(), $lang);
        $data['six_catalog_url'] = $this->url->link('extension/noveraile/page/catalog', $lang);
        $data['six_contact_url'] = (string)($this->config->get('module_noveraile_whatsapp') ?: 'https://wa.me/491707647729');
        $data['six_privacy_url'] = $this->url->link('extension/noveraile/page/privacy', $lang);
        $data['six_imprint_url'] = $this->url->link('extension/noveraile/page/imprint', $lang);
        $data['six_terms_url'] = $this->url->link('extension/noveraile/page/terms', $lang);
        $data['six_newsletter_action'] = $this->url->link('extension/noveraile/newsletter.subscribe', $lang);
        $data['six_instagram'] = trim((string)$this->config->get('module_noveraile_instagram')) ?: 'https://www.instagram.com/6moments_jewelry';
        $instagram_path = trim((string)parse_url((string)$data['six_instagram'], PHP_URL_PATH), '/');
        $data['six_instagram_label'] = $instagram_path ? '@' . basename($instagram_path) : '@' . strtolower(preg_replace('/[^a-z0-9]+/i', '', $data['six_brand_name']));
        $data['six_email'] = (string)($this->config->get('module_noveraile_email') ?: '6moments.jewelry@gmail.com');
        $data['six_whatsapp'] = (string)($this->config->get('module_noveraile_whatsapp') ?: 'https://wa.me/491707647729');
        $data['six_telegram'] = (string)($this->config->get('module_noveraile_telegram') ?: 'https://wa.me/491707647729');
        $data['six_facebook'] = (string)($this->config->get('module_noveraile_facebook') ?: 'https://www.facebook.com/profile.php?id=61587187514053');
        $data['six_year'] = date('Y');
    }

    public function home(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['common/home'], 'extension/noveraile/common/home')) return;
        $this->words($data);
        $home_products = $this->getNoveraileProducts(false, 8);
        $data['six_products'] = $this->productThumbs($home_products, true);
        $data['six_special_products'] = $this->productThumbs($this->getNoveraileProducts(true, 10));
        $lang = 'language=' . $this->config->get('config_language');
        $data['six_catalog'] = $this->url->link('extension/noveraile/page/catalog', $lang);
        // Built here rather than glued onto six_catalog in the template: the
        // link builder is free to return an address that already carries a
        // query string, or none at all, and appending "&key=value" by hand is
        // only correct for one of those shapes.
        $data['six_catalog_lab'] = $this->url->link('extension/noveraile/page/catalog', $lang . '&stone=lab-grown');
        $data['six_special'] = $this->url->link('extension/noveraile/page/catalog', $lang . '&sale=1');
        $data['six_quiz'] = $this->url->link('extension/noveraile/page/quiz', $lang);
        $data['six_about'] = $this->url->link('extension/noveraile/page/about', $lang);
        $data['six_diamonds'] = $this->url->link('extension/noveraile/page/diamonds', $lang);
        $data['six_journal_url'] = $this->url->link($this->blogRoute(), $lang);
        $data['six_instagram'] = trim((string)$this->config->get('module_noveraile_instagram')) ?: 'https://www.instagram.com/6moments_jewelry';
        $instagram_path = trim((string)parse_url($data['six_instagram'], PHP_URL_PATH), '/');
        $data['six_instagram_label'] = $instagram_path ? '@' . basename($instagram_path) : '@6moments_jewelry';

        // The social block belongs to the home view, so footer event data is
        // not available here. Keep complete locale-aware copy in this payload
        // and never allow a blank promotional panel when a translation cache
        // or an older language pack is incomplete.
        $social_fallbacks = match ((string)$this->config->get('config_language')) {
            'ru-ru' => ['six_follow' => 'Следите за нами в Instagram', 'six_follow_copy' => 'Новые украшения, детали мастерской и особенные релизы.'],
            'uk-ua' => ['six_follow' => 'Стежте за нами в Instagram', 'six_follow_copy' => 'Нові прикраси, деталі майстерні та особливі релізи.'],
            'de-de' => ['six_follow' => 'Folge uns auf Instagram', 'six_follow_copy' => 'Neue Schmuckstücke, Atelier-Einblicke und besondere Veröffentlichungen.'],
            'cs-cz' => ['six_follow' => 'Sledujte nás na Instagramu', 'six_follow_copy' => 'Nové šperky, pohledy do ateliéru a speciální novinky.'],
            default => ['six_follow' => 'Follow us on Instagram', 'six_follow_copy' => 'New jewelry, atelier details and special releases.']
        };
        foreach ($social_fallbacks as $key => $fallback) {
            if (trim((string)($data[$key] ?? '')) === '') $data[$key] = $fallback;
        }
        $builder = json_decode((string)$this->config->get('module_noveraile_page_builder'), true);
        $default_blocks = ['hero','featured','benefits','categories','collections','specials','story','journal','social'];
        if (!is_array($builder) || !$builder) $builder = array_map(static fn($id) => ['id' => $id, 'enabled' => 1], $default_blocks);
        $allowed_blocks = array_flip($default_blocks);
        $data['six_home_blocks'] = [];
        $merchant_catalog = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `status` = '1' AND `model` NOT LIKE 'NVR-%' LIMIT 1");
        $uses_current_catalog = (bool)$merchant_catalog->num_rows;
        foreach ($builder as $block) {
            $id = (string)($block['id'] ?? '');
            // The six editorial moment tiles belong to the bundled sample
            // catalog. Once real products are imported, keep the homepage
            // focused on their imagery and working category destinations.
            if ($uses_current_catalog && $id === 'collections') continue;
            if (isset($allowed_blocks[$id]) && !empty($block['enabled'])) $data['six_home_blocks'][] = $id;
        }
        // Root-relative paths also resolve correctly when used inside CSS custom properties.
        $data['six_asset'] = '/image/catalog/noveraile/';
        $data['six_hero_slides'] = [
            ['image' => $data['six_asset'] . 'hero-noveraile-v2.png', 'mobile' => $data['six_asset'] . 'hero-noveraile-mobile.png', 'width' => 2182, 'height' => 721, 'position' => '58% 48%', 'kicker' => $data['six_hero_kicker'], 'title' => $data['six_hero_title']],
            ['image' => $data['six_asset'] . 'editorial/lab-grown-diamond.png', 'mobile' => $data['six_asset'] . 'editorial/lab-grown-diamond-mobile.png', 'width' => 1774, 'height' => 887, 'position' => '58% 50%', 'kicker' => $data['six_hero2_kicker'], 'title' => $data['six_hero2_title']],
            ['image' => $data['six_asset'] . 'about-quote-jewelry.webp', 'mobile' => $data['six_asset'] . 'about-quote-jewelry.webp', 'width' => 1983, 'height' => 793, 'position' => '50% 48%', 'kicker' => $data['six_hero3_kicker'], 'title' => $data['six_hero3_title']]
        ];
        $custom_kicker = trim((string)$this->config->get('module_noveraile_hero_kicker'));
        $custom_title = trim((string)$this->config->get('module_noveraile_hero_title'));
        if ($custom_kicker !== '') $data['six_hero_slides'][0]['kicker'] = $custom_kicker;
        if ($custom_title !== '') $data['six_hero_slides'][0]['title'] = $custom_title;
        $data['six_hero_primary'] = trim((string)$this->config->get('module_noveraile_hero_cta')) ?: $data['six_hero_primary'];
        $data['six_moments'] = [];

        $category_images = [];
        $category_names = [];
        $data['six_category_tiles'] = [];
        if ($uses_current_catalog) {
            $category_query = $this->db->query("SELECT `c`.`category_id`, `cd`.`name`, `c`.`image` AS `category_image`, (SELECT `p_rep`.`image` FROM `" . DB_PREFIX . "product_to_category` `p2c_rep` INNER JOIN `" . DB_PREFIX . "product` `p_rep` ON (`p_rep`.`product_id` = `p2c_rep`.`product_id`) WHERE `p2c_rep`.`category_id` = `c`.`category_id` AND `p_rep`.`status` = '1' AND `p_rep`.`model` NOT LIKE 'NVR-%' AND NULLIF(`p_rep`.`image`, '') IS NOT NULL ORDER BY `p_rep`.`sort_order`, `p_rep`.`product_id` LIMIT 1) AS `product_image` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `c`.`status` = '1' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_category` `p2c_exists` INNER JOIN `" . DB_PREFIX . "product` `p_exists` ON (`p_exists`.`product_id` = `p2c_exists`.`product_id`) WHERE `p2c_exists`.`category_id` = `c`.`category_id` AND `p_exists`.`status` = '1' AND `p_exists`.`model` NOT LIKE 'NVR-%') ORDER BY `c`.`sort_order`, `c`.`category_id` LIMIT 4");
            foreach ($category_query->rows as $category) {
                // A category cover may be a generic campaign photograph. Prefer
                // a real product from the destination category so the homepage
                // preview always matches what the customer sees after clicking.
                $image = trim((string)($category['product_image'] ?: $category['category_image']));
                if ($image === '') continue;
                $data['six_category_tiles'][] = [
                    'name' => (string)$category['name'],
                    'image' => '/image/' . ltrim(str_replace('\\', '/', $image), '/'),
                    'href' => $this->url->link('product/category', $lang . '&path=' . (int)$category['category_id'])
                ];
            }
        }
        if (!$data['six_category_tiles']) {
            foreach ($category_images as $type => $image) {
                $data['six_category_tiles'][] = ['name'=>$category_names[$type], 'image'=>$data['six_asset'] . 'products/' . $image, 'href'=>$this->url->link('extension/noveraile/page/catalog', $lang . '&type=' . $type)];
            }
        }
        $data['six_category_tiles'] = array_slice($data['six_category_tiles'], 0, 4);
        $data['six_category_tiles'][] = [
            'name' => $data['six_type_wedding'],
            'image' => $data['six_asset'] . 'products/union-band.webp',
            'href' => $this->url->link('extension/noveraile/page/catalog', $lang . '&moment=wedding')
        ];

        $data['six_articles'] = [];
        $this->load->model('cms/article');
        $this->load->model('tool/image');
        $fallbacks = ['editorial/journal-ring-architecture.webp','editorial/journal-heirlooms.webp','editorial/journal-patina.webp'];
        foreach ($this->model_cms_article->getArticles(['sort' => 'date_added', 'order' => 'DESC', 'start' => 0, 'limit' => 3]) as $index => $article) {
            $image = !empty($article['image']) && is_file(DIR_IMAGE . html_entity_decode($article['image'], ENT_QUOTES, 'UTF-8')) ? $this->model_tool_image->resize($article['image'], 900, 620) : $data['six_asset'] . $fallbacks[$index % count($fallbacks)];
            $description = trim(strip_tags(html_entity_decode((string)$article['description'], ENT_QUOTES, 'UTF-8')));
            $data['six_articles'][] = [
                'name' => $article['name'], 'image' => $image,
                'description' => oc_strlen($description) > 150 ? oc_substr($description, 0, 147) . '…' : $description,
                'href' => $this->url->link('cms/blog.info', $lang . '&article_id=' . (int)$article['article_id'])
            ];
        }
        if (!$data['six_articles']) {
            foreach ([
                ['six_article_one_title','six_article_one_copy','editorial/journal-ring-architecture.webp'],
                ['six_article_two_title','six_article_two_copy','editorial/journal-heirlooms.webp'],
                ['six_article_three_title','six_article_three_copy','editorial/journal-patina.webp']
            ] as $article) {
                $data['six_articles'][] = ['name'=>$data[$article[0]], 'description'=>$data[$article[1]], 'image'=>$data['six_asset'] . $article[2], 'href'=>$data['six_journal_url']];
            }
        }
    }

    public function product(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['product/product'], 'extension/noveraile/product/product')) return;
        $this->words($data);
        $product_id = (int)($data['product_id'] ?? 0);
        $this->load->model('catalog/product');
        $info = $product_id ? $this->model_catalog_product->getProduct($product_id) : [];
        $currency = (string)($this->session->data['currency'] ?? $this->config->get('config_currency'));
        $this->load->model('extension/noveraile/pricing');
        $market_price = $this->model_extension_noveraile_pricing->resolve($info, $currency);
        if ($market_price['fixed']) {
            $data['price'] = $this->model_extension_noveraile_pricing->format($market_price['price'], $currency, true);
            $data['special'] = $market_price['special'] > 0 ? $this->model_extension_noveraile_pricing->format($market_price['special'], $currency, true) : false;
        }
        $image = html_entity_decode((string)($info['image'] ?? ''), ENT_QUOTES, 'UTF-8');

        // OpenCart's configured 500 px thumbnail is visibly soft in the large
        // product gallery. Serve the uploaded original for both the preview and
        // zoom so the browser never has to upscale a compressed cache derivative.
        if ($image !== '' && is_file(DIR_IMAGE . $image)) {
            $image_path = implode('/', array_map('rawurlencode', explode('/', ltrim(str_replace('\\', '/', $image), '/'))));
            $original_image = rtrim(HTTP_SERVER, '/') . '/image/' . $image_path;
            $data['thumb'] = $original_image;
            $data['popup'] = $original_image;
        }

        $this->addCategoryTrail($data, $product_id);

        $product_codes = [];
        $codes = $this->db->query("SELECT `code`, `value` FROM `" . DB_PREFIX . "product_code` WHERE `product_id` = '" . $product_id . "' AND `code` IN ('video_url','collections')");
        foreach ($codes->rows as $row) $product_codes[(string)$row['code']] = trim((string)$row['value']);
        $data['six_video_url'] = $product_codes['video_url'] ?? '';
        $data['six_collections_value'] = str_replace(';', ' ·', $product_codes['collections'] ?? '');

        // The merchant's own clip leads, then anything the imported description
        // embedded inline — at full resolution it would otherwise push the two
        // product columns out of the viewport.
        $data['six_videos'] = [];
        if ($data['six_video_url'] !== '') {
            $this->addVideo($data['six_videos'], $data['six_video_url'], (string)($data['thumb'] ?? ''));
        }
        $data['description'] = $this->liftVideos((string)($data['description'] ?? ''), $data['six_videos'], (string)($data['thumb'] ?? ''));

        $data['six_product_weight'] = $this->displayWeight($info);
        $data['six_tags'] = array_filter(array_map('trim', explode(',', (string)($info['tag'] ?? ''))));
        $data['six_moment'] = $this->momentFromTags($data['six_tags']);
        $data['six_hint_action'] = $this->url->link('extension/noveraile/hint.send', 'language=' . $this->config->get('config_language'));
        $data['six_shipping_url'] = $this->url->link('extension/noveraile/page/shipping', 'language=' . $this->config->get('config_language'));
        $data['six_cart_add'] = $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language'));
        $data['six_bundle_add'] = $this->url->link('extension/noveraile/bundle.add', 'language=' . $this->config->get('config_language'));
        $data['six_metal_options'] = $this->metalOptions($data['six_tags']);
        $data['six_metal_value'] = $data['six_metal_options']
            ? implode(' · ', array_column($data['six_metal_options'], 'name'))
            : '—';
        $stone_values = [];
        if (in_array('natural', $data['six_tags'], true)) $stone_values[] = $this->language->get('six_natural');
        if (in_array('lab-grown', $data['six_tags'], true)) $stone_values[] = $this->language->get('six_lab_grown');
        if (in_array('no-stones', $data['six_tags'], true)) $stone_values[] = $this->language->get('six_no_stones');
        $data['six_stone_value'] = $stone_values ? implode(' · ', $stone_values) : '—';
        $tag_carat = $this->tagPrefix($data['six_tags'], 'carat-');
        $tag_stones = $this->tagPrefix($data['six_tags'], 'stones-');
        $data['six_carat_value'] = $tag_carat !== '' ? $tag_carat . ' ct' : '—';
        $data['six_stones_value'] = $tag_stones !== '' ? $tag_stones : '—';
        $fineness_values = [];
        foreach (['585' => '585 / 14K', '750' => '750 / 18K'] as $tag => $label) {
            if (in_array($tag, $data['six_tags'], true)) $fineness_values[] = $label;
        }
        $data['six_fineness_value'] = $fineness_values ? implode(' · ', $fineness_values) : '—';
        $description_text = trim(strip_tags(html_entity_decode((string)($info['description'] ?? ''), ENT_QUOTES, 'UTF-8')));
        $explicit_ten_day_delivery = in_array('delivery-10', $data['six_tags'], true)
            || (bool)preg_match('/(?:10\s*(?:days?|Tage|dn[ií]|дн(?:ей|я)|днів)|(?:days?|Tage|дн(?:ей|я)|днів)\s*10)/ui', $description_text);
        $data['six_delivery_value'] = in_array('delivery-3', $data['six_tags'], true) || (!$explicit_ten_day_delivery && (int)($info['quantity'] ?? 0) > 0)
            ? $this->language->get('six_delivery_3')
            : $this->language->get('six_delivery_10');
        $data['six_bundle_product'] = [];
        $this->load->model('extension/noveraile/catalog');
        foreach ($this->model_extension_noveraile_catalog->getProductIds(['sort'=>'popular','start'=>0,'limit'=>12]) as $candidate_id) {
            if ($candidate_id === $product_id) continue;
            $candidate = $this->model_catalog_product->getProduct($candidate_id);
            if (!$candidate) continue;
            $this->load->model('tool/image');
            $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
            $candidate_market = $this->model_extension_noveraile_pricing->resolve($candidate, $currency);
            $main_market = $this->model_extension_noveraile_pricing->resolve($info, $currency);
            $pair_price = (float)($candidate_market['special'] ?: $candidate_market['price']);
            $main_price = (float)($main_market['special'] ?: $main_market['price']);
            $fixed_set = $candidate_market['fixed'] && $main_market['fixed'];
            $data['six_bundle_product'] = [
                'product_id'=>(int)$candidate['product_id'], 'name'=>$candidate['name'],
                'image'=>$this->model_tool_image->resize($candidate['image'] ?: 'placeholder.png', 520, 520),
                'price'=>$this->model_extension_noveraile_pricing->format($pair_price, $currency, $candidate_market['fixed']),
                'set_price'=>$this->model_extension_noveraile_pricing->format(($main_price + $pair_price) * .9, $currency, $fixed_set),
                'href'=>$this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$candidate['product_id'])
            ];
            break;
        }
    }

    /**
     * OpenCart only builds a category trail when the visitor arrived through a
     * `path` link, so a product opened from search, the homepage or a shared
     * URL ends up with a breadcrumb that leads nowhere. Rebuild the trail from
     * the product's own category so every product page offers a way back to
     * the full listing it belongs to.
     */
    private function addCategoryTrail(array &$data, int $product_id): void {
        $breadcrumbs = (array)($data['breadcrumbs'] ?? []);
        // `path` is the exact condition under which the core already built one.
        if (!$product_id || !$breadcrumbs || isset($this->request->get['path'])) return;

        $language_id = (int)$this->config->get('config_language_id');
        // Several categories can hold one product; prefer the most specific.
        $category = $this->db->query("SELECT `p2c`.`category_id`, COUNT(`cp`.`path_id`) AS `depth` FROM `" . DB_PREFIX . "product_to_category` `p2c` INNER JOIN `" . DB_PREFIX . "category` `c` ON (`c`.`category_id` = `p2c`.`category_id` AND `c`.`status` = '1') INNER JOIN `" . DB_PREFIX . "category_path` `cp` ON (`cp`.`category_id` = `p2c`.`category_id`) WHERE `p2c`.`product_id` = '" . $product_id . "' GROUP BY `p2c`.`category_id` ORDER BY `depth` DESC, `p2c`.`category_id` ASC LIMIT 1");
        if (!$category->num_rows) return;

        $trail = $this->db->query("SELECT `cp`.`path_id`, `cd`.`name` FROM `" . DB_PREFIX . "category_path` `cp` INNER JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `cp`.`path_id` AND `cd`.`language_id` = '" . $language_id . "') WHERE `cp`.`category_id` = '" . (int)$category->row['category_id'] . "' ORDER BY `cp`.`level` ASC");
        $path = [];
        $crumbs = [];
        foreach ($trail->rows as $step) {
            $path[] = (int)$step['path_id'];
            $crumbs[] = [
                'text' => $step['name'],
                'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . implode('_', $path))
            ];
        }
        if (!$crumbs) return;

        array_splice($breadcrumbs, count($breadcrumbs) - 1, 0, $crumbs);
        $data['breadcrumbs'] = $breadcrumbs;
    }

    /** Describe one clip for the gallery, ignoring duplicates and odd schemes. */
    private function addVideo(array &$videos, string $src, string $poster, string $kind = 'file'): void {
        $src = trim(html_entity_decode($src, ENT_QUOTES, 'UTF-8'));
        if ($src === '' || preg_match('#^\s*(?:javascript|data|vbscript):#i', $src)) return;
        foreach ($videos as $existing) if ($existing['src'] === $src) return;
        if ($kind !== 'embed' && preg_match('#(?:youtube(?:-nocookie)?\.com|youtu\.be|vimeo\.com)#i', $src)) $kind = 'embed';
        $extension = strtolower((string)pathinfo((string)parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION));
        $videos[] = [
            'src' => $src,
            'kind' => $kind,
            'type' => ['webm' => 'video/webm', 'ogv' => 'video/ogg', 'mov' => 'video/quicktime'][$extension] ?? 'video/mp4',
            'poster' => trim($poster)
        ];
    }

    /**
     * Pull every embedded clip out of a description and describe it for the
     * gallery. Returns the description with the media removed.
     */
    private function liftVideos(string $description, array &$videos, string $poster): string {
        if (!preg_match('#<video|<iframe|\.(?:mp4|webm|ogv|mov)#i', $description)) return $description;

        $push = function (string $src, string $frame, string $kind) use (&$videos, $poster): void {
            $this->addVideo($videos, $src, trim($frame) !== '' ? $frame : $poster, $kind);
        };

        $description = (string)preg_replace_callback('#<video\b[^>]*>.*?</video>|<video\b[^>]*/?>#is', function (array $match) use ($push): string {
            if (!preg_match('#<source\b[^>]*\bsrc\s*=\s*("|\')(.*?)\1#is', $match[0], $source)) {
                preg_match('#<video\b[^>]*\bsrc\s*=\s*("|\')(.*?)\1#is', $match[0], $source);
            }
            preg_match('#\bposter\s*=\s*("|\')(.*?)\1#is', $match[0], $frame);
            $push($source[2] ?? '', $frame[2] ?? '', 'file');
            return '';
        }, $description);

        // Hosted players (YouTube, Vimeo) keep their own chrome inside a frame.
        $description = (string)preg_replace_callback('#<iframe\b[^>]*\bsrc\s*=\s*("|\')(.*?)\1[^>]*>\s*</iframe>#is', function (array $match) use ($push): string {
            if (!preg_match('#(?:youtube(?:-nocookie)?\.com|youtu\.be|vimeo\.com)#i', $match[2])) return $match[0];
            $push($match[2], '', 'embed');
            return '';
        }, $description);

        // A plain link to a clip is a download prompt, not a player.
        $description = (string)preg_replace_callback('#<a\b[^>]*\bhref\s*=\s*("|\')([^"\']+?\.(?:mp4|webm|ogv|mov))\1[^>]*>.*?</a>#is', function (array $match) use ($push): string {
            $push($match[2], '', 'file');
            return '';
        }, $description);

        return trim((string)preg_replace('#<p>(?:\s|&nbsp;|<br\s*/?>)*</p>#i', '', $description));
    }

    public function thumb(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['product/thumb'], 'extension/noveraile/product/thumb')) return;
        $this->words($data);
        $currency = (string)($this->session->data['currency'] ?? $this->config->get('config_currency'));
        $this->load->model('extension/noveraile/pricing');
        $market_price = $this->model_extension_noveraile_pricing->resolve($data, $currency);
        if ($market_price['fixed']) {
            $data['price'] = $this->model_extension_noveraile_pricing->format($market_price['price'], $currency, true);
            $data['special'] = $market_price['special'] > 0 ? $this->model_extension_noveraile_pricing->format($market_price['special'], $currency, true) : false;
        }
        $tags = array_filter(array_map('trim', explode(',', (string)($data['tag'] ?? ''))));
        $data['six_moment'] = trim((string)($data['six_moment_override'] ?? '')) ?: $this->momentFromTags($tags);
        $data['six_metal_options'] = $this->metalOptions($tags);
        $data['six_fineness_value'] = $this->tagChoice($tags, ['585','750']);
        $data['six_sku'] = $data['model'] ?? '';
        $data['six_product_weight'] = $this->displayWeight($data);
        $carat = $this->tagPrefix($tags, 'carat-');
        $stones = $this->tagPrefix($tags, 'stones-');
        $data['six_product_carat'] = $carat !== '' ? $carat . ' ct' : '—';
        $data['six_product_stones'] = $stones !== '' ? $stones : '—';
        $data['six_stocked'] = (int)($data['quantity'] ?? 0) > 0;
        // OpenCart's bundled demo descriptions are entity encoded in SQL.
        // Normalise card copy to plain text so markup can never leak visibly.
        $data['description'] = trim(strip_tags(html_entity_decode(html_entity_decode((string)($data['description'] ?? ''), ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8')));
    }

    public function listing(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['product/category', 'product/search', 'product/special'], 'extension/noveraile/product/listing')) return;
        $this->words($data);

        $lang = 'language=' . $this->config->get('config_language');
        $data['six_catalog_url'] = $this->url->link('extension/noveraile/page/catalog', $lang);
        $data['six_listing_categories'] = [];

        // OpenCart's own category, search and special listings ship without any
        // refinement. Lend them the catalog rail so a shopper who lands on a
        // category can narrow it down instead of scrolling the whole shelf.
        $path = preg_replace('/[^0-9_]/', '', (string)($this->request->get['path'] ?? ''));
        $path_parts = array_filter(explode('_', (string)$path), 'strlen');
        $category_id = $path_parts ? (int)end($path_parts) : 0;
        $data['six_filter_panel'] = $this->load->controller(
            'extension/noveraile/page/catalog.panel',
            $category_id,
            $category_id ? $this->url->link('product/category', $lang . '&path=' . $path) : ''
        );

        $category_query = $this->db->query("SELECT c.category_id, cd.name, c.sort_order, c.image AS category_image, (SELECT p_rep.image FROM `" . DB_PREFIX . "product_to_category` p2c_rep INNER JOIN `" . DB_PREFIX . "product` p_rep ON (p_rep.product_id = p2c_rep.product_id) WHERE p2c_rep.category_id = c.category_id AND p_rep.status = '1' AND NULLIF(p_rep.image, '') IS NOT NULL ORDER BY p_rep.sort_order ASC, p_rep.product_id ASC LIMIT 1) AS product_image FROM `" . DB_PREFIX . "category` c INNER JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = c.category_id) WHERE c.status = '1' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_category` p2c_exists INNER JOIN `" . DB_PREFIX . "product` p_exists ON (p_exists.product_id = p2c_exists.product_id) WHERE p2c_exists.category_id = c.category_id AND p_exists.status = '1') ORDER BY c.sort_order ASC, cd.name ASC, c.category_id ASC LIMIT 60");
        $category_names = [];
        foreach ($category_query->rows as $category) {
            $name = preg_replace('/[\p{Z}\s]+/u', ' ', trim((string)$category['name'])) ?: trim((string)$category['name']);
            $key = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
            if ($name === '' || isset($category_names[$key])) continue;
            $category_names[$key] = true;
            $image = trim((string)($category['product_image'] ?: $category['category_image']));
            $data['six_listing_categories'][] = [
                'name' => $name,
                'image' => $image !== '' ? '/image/' . ltrim(str_replace('\\', '/', $image), '/') : '',
                'href' => $this->url->link('product/category', $lang . '&path=' . (int)$category['category_id'])
            ];
            if (count($data['six_listing_categories']) >= 6) break;
        }

    }

    public function information(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['information/information'], 'extension/noveraile/information/information')) return;
        $this->words($data);
    }

    public function contact(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['information/contact'], 'extension/noveraile/information/contact')) return;
        $this->words($data);
        $data['six_contact_email'] = $this->config->get('module_noveraile_email') ?: '6moments.jewelry@gmail.com';
        $data['six_whatsapp'] = (string)($this->config->get('module_noveraile_whatsapp') ?: 'https://wa.me/491707647729');
        $data['six_telegram'] = (string)($this->config->get('module_noveraile_telegram') ?: 'https://wa.me/491707647729');
        $data['six_instagram'] = (string)($this->config->get('module_noveraile_instagram') ?: 'https://www.instagram.com/6moments_jewelry');
        $data['six_facebook'] = (string)($this->config->get('module_noveraile_facebook') ?: 'https://www.facebook.com/profile.php?id=61587187514053');
    }

    public function cart(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['checkout/cart'], 'extension/noveraile/checkout/cart')) return;
        $this->words($data);
    }

    public function cartList(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['checkout/cart_list'], 'extension/noveraile/checkout/cart_list')) return;
        $this->words($data);
        // OpenCart rounds gram-scale jewelry weights to a misleading 0.00 kg.
        $data['weight'] = '';
        $lang = 'language=' . $this->config->get('config_language');
        $data['continue'] = $this->url->link('extension/noveraile/page/catalog', $lang);
        $data['six_shipping_url'] = $this->url->link('extension/noveraile/page/shipping', $lang);
        $data['six_coupon_action'] = $this->url->link('extension/noveraile/coupon.apply', $lang);
        $data['six_coupon_status'] = in_array((string)($this->request->get['coupon_status'] ?? ''), ['success', 'error'], true) ? (string)$this->request->get['coupon_status'] : '';
        $currency = (string)($this->session->data['currency'] ?? $this->config->get('config_currency'));
        $this->load->model('extension/noveraile/pricing');
        $cart_products = $this->cart->getProducts();
        foreach ($data['products'] as &$display_product) {
            $raw = null;
            foreach ($cart_products as $candidate) {
                if ((string)($candidate['cart_id'] ?? '') === (string)($display_product['cart_id'] ?? '') || (int)($candidate['product_id'] ?? 0) === (int)($display_product['product_id'] ?? 0)) { $raw = $candidate; break; }
            }
            if (!$raw) continue;
            $market_price = $this->model_extension_noveraile_pricing->resolve($raw, $currency);
            if (!$market_price['fixed']) continue;
            $unit = $market_price['special'] > 0 ? $market_price['special'] : $market_price['price'];
            $display_product['price'] = $this->model_extension_noveraile_pricing->format($unit, $currency, true);
            $display_product['total'] = $this->model_extension_noveraile_pricing->format($unit * max(1, (int)($raw['quantity'] ?? 1)), $currency, true);
        }
        unset($display_product);
    }

    public function checkout(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->config->get('module_noveraile_one_page_checkout_status') || !$this->claimView($route, ['checkout/checkout'], 'extension/noveraile/checkout/checkout')) return;
        $this->words($data);
        $lang = 'language=' . $this->config->get('config_language');
        $data['six_cart_url'] = $this->url->link('checkout/cart', $lang);
        $data['six_catalog_url'] = $this->url->link('extension/noveraile/page/catalog', $lang);
        $data['six_contact_url'] = (string)($this->config->get('module_noveraile_whatsapp') ?: 'https://wa.me/491707647729');
    }

    public function captureSuccess(string &$route, array &$args = []): void {
        if (!$this->enabled()) return;
        $order_id = (int)($this->session->data['order_id'] ?? 0);
        if ($order_id) $this->session->data['noveraile_last_order_id'] = $order_id;
    }

    public function success(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['common/success'], 'extension/noveraile/checkout/success')) return;
        $this->words($data);
        $order_id = (int)($this->session->data['noveraile_last_order_id'] ?? 0);
        $data['six_order_id'] = $order_id;
        $data['order'] = $this->url->link('account/order', 'language=' . $this->config->get('config_language'));
        $data['six_order_products'] = [];
        $data['six_order_totals'] = [];
        if ($order_id) {
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($order_id);
            if ($order) {
                $currency = (string)$order['currency_code'];
                $value = (float)$order['currency_value'];
                foreach ($this->model_checkout_order->getProducts($order_id) as $product) {
                    $data['six_order_products'][] = ['name'=>$product['name'], 'model'=>$product['model'], 'quantity'=>(int)$product['quantity'], 'total'=>$this->currency->format((float)$product['total'], $currency, $value)];
                }
                foreach ($this->model_checkout_order->getTotals($order_id) as $total_line) {
                    $data['six_order_totals'][] = ['title'=>$total_line['title'], 'text'=>$this->currency->format((float)$total_line['value'], $currency, $value)];
                }
            }
        }
    }

    public function notFound(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['error/not_found'], 'extension/noveraile/error/not_found')) return;
        $this->words($data);
        $data['six_catalog_url'] = $this->url->link('extension/noveraile/page/catalog', 'language=' . $this->config->get('config_language'));
    }

    public function accountLogin(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['account/login'], 'extension/noveraile/account/login')) return;
        $this->words($data);
        $data['six_account_image'] = rtrim(HTTP_SERVER, '/') . '/image/catalog/noveraile/about-quote-jewelry.webp';
    }

    public function blog(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['cms/blog'], 'extension/noveraile/cms/blog')) return;
        $this->words($data);

        $fallbacks = [
            'editorial/journal-ring-architecture.webp',
            'editorial/journal-heirlooms.webp',
            'editorial/journal-patina.webp'
        ];

        foreach ($data['articles'] as $index => &$article) {
            if (empty($article['image'])) {
                $article['image'] = '/image/catalog/noveraile/' . $fallbacks[$index % count($fallbacks)];
            }

            $article['six_label'] = $data['six_field_note'] . ' ' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
        }
        unset($article);
    }

    public function blogInfo(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['cms/blog_info'], 'extension/noveraile/cms/blog_info')) return;
        $this->words($data);
        $data['six_journal_url'] = $this->url->link($this->blogRoute(), 'language=' . $this->config->get('config_language'));

        if (empty($data['image'])) {
            $data['image'] = '/image/catalog/noveraile/editorial/journal-ring-architecture.webp';
        }
    }

    private function getProducts(array $filter): array {
        $this->load->model('catalog/product');
        return $this->model_catalog_product->getProducts($filter);
    }

    private function getSpecials(array $filter): array {
        $this->load->model('catalog/product');
        return $this->model_catalog_product->getSpecials($filter);
    }

    private function getNoveraileProducts(bool $specialOnly, int $limit): array {
        $this->load->model('catalog/product');
        $sql = "SELECT p.product_id FROM `" . DB_PREFIX . "product` p";
        if ($specialOnly) {
            $sql .= " INNER JOIN `" . DB_PREFIX . "product_discount` ps ON (ps.product_id = p.product_id AND ps.special = '1')";
        }
        $sql .= " INNER JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p2s.product_id = p.product_id AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "') WHERE p.status = '1' AND p.date_available <= NOW() GROUP BY p.product_id ORDER BY p.sort_order ASC, p.product_id ASC LIMIT " . (int)$limit;
        $products = [];
        foreach ($this->db->query($sql)->rows as $row) {
            $product = $this->model_catalog_product->getProduct((int)$row['product_id']);
            if ($product) $products[] = $product;
        }
        return $products;
    }

    private function productThumbs(array $results, bool $numbered = false): array {
        $this->load->model('tool/image');
        $cards = [];
        foreach ($results as $index => $result) {
            $image = !empty($result['image']) && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')) ? $result['image'] : 'placeholder.png';
            $currency = (string)($this->session->data['currency'] ?? $this->config->get('config_currency'));
            $this->load->model('extension/noveraile/pricing');
            $market_price = $this->model_extension_noveraile_pricing->resolve($result, $currency);
            $price = $this->model_extension_noveraile_pricing->format($market_price['fixed'] ? $market_price['price'] : $this->tax->calculate((float)$result['price'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $currency, $market_price['fixed']);
            $special = $market_price['special'] > 0 ? $this->model_extension_noveraile_pricing->format($market_price['fixed'] ? $market_price['special'] : $this->tax->calculate((float)$result['special'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $currency, $market_price['fixed']) : false;
            $product = array_merge($result, [
                'thumb' => $this->model_tool_image->resize($image, 900, 900),
                'description' => trim(strip_tags(html_entity_decode(html_entity_decode((string)$result['description'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'))),
                'price' => $price,
                'special' => $special,
                'tax' => false,
                'minimum' => max(1, (int)$result['minimum']),
                'href' => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$result['product_id']),
                'cart' => $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language')),
                'cart_add' => $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language')),
                'wishlist_add' => $this->url->link('account/wishlist.add', 'language=' . $this->config->get('config_language')),
                'compare_add' => $this->url->link('product/compare.add', 'language=' . $this->config->get('config_language')),
                'review_status' => false,
                'rating' => 0,
                'six_moment_override' => $numbered
                    ? $this->language->get('six_signature_piece') . ' ' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)
                    : ''
            ]);
            $cards[] = $this->load->view('product/thumb', $product);
        }
        return $cards;
    }

    private function momentFromTags(array $tags): string {
        $map = [
            'moment-01' => 'six_catalog_moment_01', 'moment-02' => 'six_catalog_moment_02',
            'moment-03' => 'six_catalog_moment_03', 'moment-04' => 'six_catalog_moment_04',
            'moment-05' => 'six_catalog_moment_05', 'moment-06' => 'six_catalog_moment_06',
            'moment-special' => 'six_catalog_moment_special',
            'engagement' => 'six_moment_yes', 'wedding' => 'six_moment_forever',
            'motherhood' => 'six_moment_new_life', 'career' => 'six_moment_victory',
            'self-purchase' => 'six_moment_deserve', 'milestone' => 'six_moment_with_me'
        ];
        foreach ($map as $tag => $key) if (in_array($tag, $tags, true)) return $this->language->get($key);
        return $this->language->get('six_signature_piece');
    }

    private function displayWeight(array $product): string {
        $catalog_weights = [
            'NVR-RI-001' => '2.8 g', 'NVR-WE-002' => '3.9 g', 'NVR-NE-003' => '2.1 g',
            'NVR-EA-004' => '4.2 g', 'NVR-BR-005' => '2.6 g', 'NVR-RI-006' => '8.4 g',
            'NVR-WE-007' => '3.4 g', 'NVR-EA-008' => '2.2 g', 'NVR-NE-009' => '2.4 g',
            'NVR-RI-010' => '3.1 g'
        ];
        $model = (string)($product['model'] ?? '');
        if (isset($catalog_weights[$model])) return $catalog_weights[$model];
        if (!isset($product['weight'])) return '—';

        return $this->formatWeight(
            (float)$product['weight'],
            (int)($product['weight_class_id'] ?? $this->config->get('config_weight_class_id'))
        );
    }

    private function formatWeight(float $weight, int $weight_class_id): string {
        if ($this->gram_weight_class_id === null) {
            $gram = $this->db->query("SELECT `weight_class_id` FROM `" . DB_PREFIX . "weight_class` ORDER BY ABS(`value` - 1000) ASC LIMIT 1");
            $this->gram_weight_class_id = (int)($gram->row['weight_class_id'] ?? $weight_class_id);
        }

        $grams = $this->weight->convert($weight, $weight_class_id, $this->gram_weight_class_id);
        // Jewelry imports commonly provide a gram value while retaining the
        // store's kilogram class. A 1.82 kg ring is not plausible; preserve
        // the supplied number as grams for this small-product catalog.
        if ($grams >= 500 && $weight > 0 && $weight <= 50) {
            $grams = $weight;
        }
        if ($grams >= 1000) {
            return rtrim(rtrim(number_format($grams / 1000, 2, '.', ''), '0'), '.') . ' kg';
        }

        return rtrim(rtrim(number_format($grams, 2, '.', ''), '0'), '.') . ' g';
    }

    private function tagChoice(array $tags, array $choices): string {
        foreach ($choices as $choice) if (in_array($choice, $tags, true)) return $choice;
        return '';
    }

    private function metalOptions(array $tags): array {
        $labels = [
            'white-gold' => 'six_white_gold',
            'yellow-gold' => 'six_yellow_gold',
            'rose-gold' => 'six_rose_gold',
            'alloy' => 'six_alloy'
        ];
        $options = [];
        foreach ($labels as $key => $language_key) {
            if (!in_array($key, $tags, true)) continue;
            $options[] = ['key' => $key, 'name' => $this->language->get($language_key)];
        }
        return $options;
    }

    private function tagPrefix(array $tags, string $prefix): string {
        foreach ($tags as $tag) if (str_starts_with($tag, $prefix)) return str_replace('-', '.', substr($tag, strlen($prefix)));
        return '';
    }
}
