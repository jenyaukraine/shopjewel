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
        $brand = (string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name') ?: 'NOVERAILE');
        foreach ($this->language->all() as $key => $value) {
            if (str_starts_with($key, 'six_')) $data[$key] = is_string($value) ? str_replace(['NOVERAILE', 'Six Moments'], $brand, $value) : $value;
        }
        $data['six_brand_name'] = $brand;
    }

    public function header(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['common/header'], 'extension/noveraile/common/header')) return;
        $this->words($data);

        if (($data['title'] ?? '') === 'Your Store') {
            $data['title'] = $data['six_brand_name'];
        }

        $data['six_stylesheet'] = 'extension/noveraile/catalog/view/stylesheet/noveraile.css?v=2.2.0.2';
        $data['six_script'] = 'extension/noveraile/catalog/view/javascript/noveraile.js?v=2.2.0.1';
        $data['six_favicon'] = rtrim(HTTP_SERVER, '/') . '/image/catalog/noveraile/favicon.svg?v=2';
        $data['six_og_image'] = rtrim(HTTP_SERVER, '/') . '/image/catalog/noveraile/og-store.png';
        $data['six_color_mode'] = in_array($this->config->get('module_noveraile_color_mode'), ['light', 'dark'], true) ? $this->config->get('module_noveraile_color_mode') : 'auto';
        $data['six_native_menu_status'] = (bool)$this->config->get('module_noveraile_native_menu_status');
        $data['six_canonical'] = '';
        foreach ((array)($data['links'] ?? []) as $link) {
            if (($link['rel'] ?? '') === 'canonical') { $data['six_canonical'] = (string)($link['href'] ?? ''); break; }
        }
        $data['six_home'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));
        $data['six_catalog_url'] = $this->url->link('extension/noveraile/page/catalog', 'language=' . $this->config->get('config_language'));
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

        $data['six_categories'] = [];
        $data['six_mega_menu_status'] = (bool)$this->config->get('module_noveraile_mega_menu_status');
        $data['six_mega_menu_title'] = (string)($this->config->get('module_noveraile_mega_menu_title') ?: $data['six_catalog']);
        $data['six_mega_menu_promo_text'] = (string)($this->config->get('module_noveraile_mega_menu_promo_text') ?: $data['six_specials']);
        $data['six_mega_menu_promo_url'] = (string)($this->config->get('module_noveraile_mega_menu_promo_url') ?: $data['six_special']);
        // Demo upgrades can leave more than one category record with the same
        // translated name. Present each customer-facing category only once.
        $category_query = $this->db->query("SELECT MIN(c.category_id) AS category_id, cd.name, MIN(c.sort_order) AS sort_order, COUNT(DISTINCT p.product_id) AS product_total FROM `" . DB_PREFIX . "category` c INNER JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = c.category_id) INNER JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p2c.category_id = c.category_id) INNER JOIN `" . DB_PREFIX . "product` p ON (p.product_id = p2c.product_id AND p.status = '1') WHERE c.status = '1' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY cd.name ORDER BY MIN(c.sort_order) ASC, cd.name ASC LIMIT 12");
        foreach ($category_query->rows as $category) {
            $data['six_categories'][] = [
                'name' => $category['name'],
                'total' => (int)($category['product_total'] ?? 0),
                'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . (int)$category['category_id'])
            ];
        }
        if (!$data['six_categories']) {
            foreach ([
                'rings' => $data['six_type_rings'], 'earrings' => $data['six_type_earrings'],
                'necklaces' => $data['six_type_necklaces'], 'bracelets' => $data['six_type_bracelets'],
                'wedding' => $data['six_type_wedding']
            ] as $type => $name) {
                $data['six_categories'][] = ['name' => $name, 'href' => $this->url->link('extension/noveraile/page/catalog', 'language=' . $this->config->get('config_language') . '&type=' . $type)];
            }
        }
    }

    public function footer(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['common/footer'], 'extension/noveraile/common/footer')) return;
        $this->words($data);
        $data['six_script'] = 'extension/noveraile/catalog/view/javascript/noveraile.js?v=2.2.0';
        $lang = 'language=' . $this->config->get('config_language');
        $data['six_home'] = $this->url->link('common/home', $lang);
        $data['six_about_url'] = $this->url->link('extension/noveraile/page/about', $lang);
        $data['six_diamonds_url'] = $this->url->link('extension/noveraile/page/diamonds', $lang);
        $data['six_shipping_url'] = $this->url->link('extension/noveraile/page/shipping', $lang);
        $data['six_faq_url'] = $this->url->link('extension/noveraile/page/faq', $lang);
        $data['six_journal_url'] = $this->url->link($this->blogRoute(), $lang);
        $data['six_catalog_url'] = $this->url->link('extension/noveraile/page/catalog', $lang);
        $data['six_contact_url'] = $this->url->link('information/contact', $lang);
        $data['six_privacy_url'] = $this->url->link('extension/noveraile/page/privacy', $lang);
        $data['six_imprint_url'] = $this->url->link('extension/noveraile/page/imprint', $lang);
        $data['six_terms_url'] = $this->url->link('extension/noveraile/page/terms', $lang);
        $data['six_newsletter_action'] = $this->url->link('extension/noveraile/newsletter.subscribe', $lang);
        $data['six_instagram'] = $this->config->get('module_noveraile_instagram');
        $instagram_path = trim((string)parse_url((string)$data['six_instagram'], PHP_URL_PATH), '/');
        $data['six_instagram_label'] = $instagram_path ? '@' . basename($instagram_path) : '@' . strtolower(preg_replace('/[^a-z0-9]+/i', '', $data['six_brand_name']));
        $data['six_email'] = $this->config->get('module_noveraile_email');
        $data['six_year'] = date('Y');
    }

    public function home(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['common/home'], 'extension/noveraile/common/home')) return;
        $this->words($data);
        $data['six_products'] = $this->productThumbs($this->getNoveraileProducts(false, 6));
        $data['six_special_products'] = $this->productThumbs($this->getNoveraileProducts(true, 10));
        $lang = 'language=' . $this->config->get('config_language');
        $data['six_catalog'] = $this->url->link('extension/noveraile/page/catalog', $lang);
        $data['six_special'] = $this->url->link('extension/noveraile/page/catalog', $lang . '&sale=1');
        $data['six_quiz'] = $this->url->link('extension/noveraile/page/quiz', $lang);
        $data['six_about'] = $this->url->link('extension/noveraile/page/about', $lang);
        $data['six_diamonds'] = $this->url->link('extension/noveraile/page/diamonds', $lang);
        $data['six_journal_url'] = $this->url->link($this->blogRoute(), $lang);
        $data['six_instagram'] = $this->config->get('module_noveraile_instagram');
        $builder = json_decode((string)$this->config->get('module_noveraile_page_builder'), true);
        $default_blocks = ['hero','featured','benefits','categories','collections','specials','story','journal','social'];
        if (!is_array($builder) || !$builder) $builder = array_map(static fn($id) => ['id' => $id, 'enabled' => 1], $default_blocks);
        $allowed_blocks = array_flip($default_blocks);
        $data['six_home_blocks'] = [];
        foreach ($builder as $block) {
            $id = (string)($block['id'] ?? '');
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
        $data['six_moments'] = [
                ['code' => '01', 'title' => $data['six_moment_yes'], 'category' => $data['six_type_rings'], 'tag' => 'engagement', 'image' => $data['six_asset'] . 'products/promise-solitaire.webp'],
                ['code' => '02', 'title' => $data['six_moment_forever'], 'category' => $data['six_type_wedding'], 'tag' => 'wedding', 'image' => $data['six_asset'] . 'products/union-band.webp'],
                ['code' => '03', 'title' => $data['six_moment_new_life'], 'category' => $data['six_type_necklaces'], 'tag' => 'motherhood', 'image' => $data['six_asset'] . 'products/arrival-pendant.webp'],
                ['code' => '04', 'title' => $data['six_moment_victory'], 'category' => $data['six_type_earrings'], 'tag' => 'career', 'image' => $data['six_asset'] . 'products/becoming-hoops.webp'],
                ['code' => '05', 'title' => $data['six_moment_deserve'], 'category' => $data['six_type_bracelets'], 'tag' => 'self-purchase', 'image' => $data['six_asset'] . 'products/gratitude-bracelet.webp'],
                ['code' => '06', 'title' => $data['six_moment_with_me'], 'category' => $data['six_type_rings'], 'tag' => 'milestone', 'image' => $data['six_asset'] . 'products/legacy-signet.webp']
        ];
        foreach ($data['six_moments'] as &$moment) {
            $moment['href'] = $this->url->link('extension/noveraile/page/catalog', $lang . '&moment=' . rawurlencode($moment['tag']));
        }
        unset($moment);

        $category_images = ['rings'=>'promise-solitaire.webp','earrings'=>'becoming-hoops.webp','necklaces'=>'arrival-pendant.webp','bracelets'=>'gratitude-bracelet.webp','wedding'=>'union-band.webp'];
        $category_names = ['rings'=>$data['six_type_rings'],'earrings'=>$data['six_type_earrings'],'necklaces'=>$data['six_type_necklaces'],'bracelets'=>$data['six_type_bracelets'],'wedding'=>$data['six_type_wedding']];
        $data['six_category_tiles'] = [];
        foreach ($category_images as $type => $image) {
            $data['six_category_tiles'][] = ['name'=>$category_names[$type], 'image'=>$data['six_asset'] . 'products/' . $image, 'href'=>$this->url->link('extension/noveraile/page/catalog', $lang . '&type=' . $type)];
        }

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

        $data['six_product_weight'] = $this->displayWeight($info);
        $data['six_tags'] = array_filter(array_map('trim', explode(',', (string)($info['tag'] ?? ''))));
        $data['six_moment'] = $this->momentFromTags($data['six_tags']);
        $data['six_hint_action'] = $this->url->link('extension/noveraile/hint.send', 'language=' . $this->config->get('config_language'));
        $data['six_shipping_url'] = $this->url->link('extension/noveraile/page/shipping', 'language=' . $this->config->get('config_language'));
        $data['six_cart_add'] = $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language'));
        $data['six_bundle_add'] = $this->url->link('extension/noveraile/bundle.add', 'language=' . $this->config->get('config_language'));
        $metal = $this->tagChoice($data['six_tags'], ['white-gold','yellow-gold','rose-gold','platinum','alloy']) ?: 'yellow-gold';
        $metal_keys = ['white-gold'=>'six_white_gold','yellow-gold'=>'six_yellow_gold','rose-gold'=>'six_rose_gold','platinum'=>'six_platinum','alloy'=>'six_alloy'];
        $data['six_metal_value'] = $this->language->get($metal_keys[$metal] ?? 'six_yellow_gold');
        $stone = $this->tagChoice($data['six_tags'], ['natural','lab-grown']);
        $data['six_stone_value'] = in_array('no-stones', $data['six_tags'], true) ? $this->language->get('six_no_stones') : $this->language->get($stone === 'lab-grown' ? 'six_lab_grown' : 'six_natural');
        $tag_carat = $this->tagPrefix($data['six_tags'], 'carat-');
        $tag_stones = $this->tagPrefix($data['six_tags'], 'stones-');
        $data['six_carat_value'] = $tag_carat !== '' ? $tag_carat . ' ct' : '—';
        $data['six_stones_value'] = $tag_stones !== '' ? $tag_stones : '—';
        $data['six_fineness_value'] = $this->tagChoice($data['six_tags'], ['585','750','950']) ?: '—';
        $data['six_delivery_value'] = in_array('delivery-3', $data['six_tags'], true) || (int)($info['quantity'] ?? 0) > 0 ? $this->language->get('six_delivery_3') : $this->language->get('six_delivery_10');
        $data['six_bundle_product'] = [];
        $this->load->model('extension/noveraile/catalog');
        foreach ($this->model_extension_noveraile_catalog->getProductIds(['sort'=>'popular','start'=>0,'limit'=>12]) as $candidate_id) {
            if ($candidate_id === $product_id) continue;
            $candidate = $this->model_catalog_product->getProduct($candidate_id);
            if (!$candidate) continue;
            $this->load->model('tool/image');
            $pair_price = (float)($candidate['special'] ?: $candidate['price']);
            $main_price = (float)($info['special'] ?: $info['price']);
            $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
            $data['six_bundle_product'] = [
                'product_id'=>(int)$candidate['product_id'], 'name'=>$candidate['name'],
                'image'=>$this->model_tool_image->resize($candidate['image'] ?: 'placeholder.png', 520, 520),
                'price'=>$this->currency->format($pair_price, $currency),
                'set_price'=>$this->currency->format(($main_price + $pair_price) * .9, $currency),
                'href'=>$this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$candidate['product_id'])
            ];
            break;
        }
    }

    public function thumb(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['product/thumb'], 'extension/noveraile/product/thumb')) return;
        $this->words($data);
        $tags = array_filter(array_map('trim', explode(',', (string)($data['tag'] ?? ''))));
        $data['six_moment'] = $this->momentFromTags($tags);
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

        $category_query = $this->db->query("SELECT DISTINCT c.category_id, cd.name, c.sort_order FROM `" . DB_PREFIX . "category` c INNER JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = c.category_id) INNER JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p2c.category_id = c.category_id) INNER JOIN `" . DB_PREFIX . "product` p ON (p.product_id = p2c.product_id) WHERE c.status = '1' AND p.status = '1' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY c.sort_order ASC, cd.name ASC LIMIT 8");
        foreach ($category_query->rows as $category) {
            $data['six_listing_categories'][] = [
                'name' => $category['name'],
                'href' => $this->url->link('product/category', $lang . '&path=' . (int)$category['category_id'])
            ];
        }

        if (!$data['six_listing_categories']) {
            foreach ([
                'rings' => $data['six_type_rings'], 'earrings' => $data['six_type_earrings'],
                'necklaces' => $data['six_type_necklaces'], 'bracelets' => $data['six_type_bracelets'],
                'wedding' => $data['six_type_wedding']
            ] as $type => $name) {
                $data['six_listing_categories'][] = [
                    'name' => $name,
                    'href' => $this->url->link('extension/noveraile/page/catalog', $lang . '&type=' . $type)
                ];
            }
        }
    }

    public function information(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['information/information'], 'extension/noveraile/information/information')) return;
        $this->words($data);
    }

    public function contact(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->claimView($route, ['information/contact'], 'extension/noveraile/information/contact')) return;
        $this->words($data);
        $data['six_contact_email'] = $this->config->get('module_noveraile_email') ?: $this->config->get('config_email');
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
    }

    public function checkout(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled() || !$this->config->get('module_noveraile_one_page_checkout_status') || !$this->claimView($route, ['checkout/checkout'], 'extension/noveraile/checkout/checkout')) return;
        $this->words($data);
        $lang = 'language=' . $this->config->get('config_language');
        $data['six_cart_url'] = $this->url->link('checkout/cart', $lang);
        $data['six_catalog_url'] = $this->url->link('extension/noveraile/page/catalog', $lang);
        $data['six_contact_url'] = $this->url->link('information/contact', $lang);
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

    private function productThumbs(array $results): array {
        $this->load->model('tool/image');
        $cards = [];
        foreach ($results as $result) {
            $image = !empty($result['image']) && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')) ? $result['image'] : 'placeholder.png';
            $price = $this->currency->format($this->tax->calculate((float)$result['price'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            $special = !empty($result['special']) ? $this->currency->format($this->tax->calculate((float)$result['special'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;
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
                'rating' => 0
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
            'NVR-SE-007' => '3.1 kg'
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
        if ($grams >= 1000) {
            return rtrim(rtrim(number_format($grams / 1000, 2, '.', ''), '0'), '.') . ' kg';
        }

        return rtrim(rtrim(number_format($grams, 2, '.', ''), '0'), '.') . ' g';
    }

    private function tagChoice(array $tags, array $choices): string {
        foreach ($choices as $choice) if (in_array($choice, $tags, true)) return $choice;
        return '';
    }

    private function tagPrefix(array $tags, string $prefix): string {
        foreach ($tags as $tag) if (str_starts_with($tag, $prefix)) return str_replace('-', '.', substr($tag, strlen($prefix)));
        return '';
    }
}
