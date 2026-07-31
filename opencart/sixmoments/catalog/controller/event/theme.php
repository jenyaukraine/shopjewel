<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments\Event;

class Theme extends \Opencart\System\Engine\Controller {
    private function enabled(): bool {
        return (bool)$this->config->get('module_sixmoments_status');
    }

    private function words(array &$data): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        foreach ($this->language->all() as $key => $value) {
            if (str_starts_with($key, 'six_')) $data[$key] = $value;
        }
    }

    public function header(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/common/header';
        $this->words($data);

        if (($data['title'] ?? '') === 'Your Store') {
            $data['title'] = '6MOMENTS Jewelry';
        }

        $data['six_stylesheet'] = 'extension/sixmoments/catalog/view/stylesheet/sixmoments.css?v=1.1.8';
        $data['six_script'] = 'extension/sixmoments/catalog/view/javascript/sixmoments.js?v=1.1.7';
        $data['six_favicon'] = '/image/catalog/sixmoments/favicon.svg?v=2';
        $data['six_og_image'] = '/image/catalog/sixmoments/og-store.png';
        $data['six_home'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));
        $data['six_catalog_url'] = $this->url->link('extension/sixmoments/page/catalog', 'language=' . $this->config->get('config_language'));
        $data['six_about_url'] = $this->url->link('extension/sixmoments/page/about', 'language=' . $this->config->get('config_language'));
        $data['six_diamonds_url'] = $this->url->link('extension/sixmoments/page/diamonds', 'language=' . $this->config->get('config_language'));
        $data['six_quiz_url'] = $this->url->link('extension/sixmoments/page/quiz', 'language=' . $this->config->get('config_language'));
        $data['six_journal_url'] = $this->url->link('cms/blog', 'language=' . $this->config->get('config_language'));
        $data['six_special'] = $this->url->link('extension/sixmoments/page/catalog', 'language=' . $this->config->get('config_language') . '&sale=1');
        $data['six_search_action'] = $data['six_catalog_url'];
        $data['six_search_suggest'] = $this->url->link('extension/sixmoments/search.suggest', 'language=' . $this->config->get('config_language'));
        $data['six_cart_count'] = $this->cart->countProducts();
        $data['six_language_code'] = $this->config->get('config_language');
        $data['six_currency_code'] = $this->session->data['currency'] ?? $this->config->get('config_currency');

        $data['six_categories'] = [];
        $category_query = $this->db->query("SELECT DISTINCT c.category_id, cd.name, c.sort_order FROM `" . DB_PREFIX . "category` c INNER JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = c.category_id) INNER JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p2c.category_id = c.category_id) INNER JOIN `" . DB_PREFIX . "product_code` pc ON (pc.product_id = p2c.product_id AND pc.code = 'sku' AND pc.value LIKE '6M-%') WHERE c.status = '1' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY c.sort_order ASC, cd.name ASC LIMIT 5");
        foreach ($category_query->rows as $category) {
            $data['six_categories'][] = [
                'name' => $category['name'],
                'href' => $this->url->link('extension/sixmoments/page/catalog', 'language=' . $this->config->get('config_language') . '&category_id=' . (int)$category['category_id'])
            ];
        }
        if (!$data['six_categories']) {
            foreach ([
                'rings' => $data['six_type_rings'], 'earrings' => $data['six_type_earrings'],
                'necklaces' => $data['six_type_necklaces'], 'bracelets' => $data['six_type_bracelets'],
                'wedding' => $data['six_type_wedding']
            ] as $type => $name) {
                $data['six_categories'][] = ['name' => $name, 'href' => $this->url->link('extension/sixmoments/page/catalog', 'language=' . $this->config->get('config_language') . '&type=' . $type)];
            }
        }
    }

    public function footer(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/common/footer';
        $this->words($data);
        $data['six_script'] = 'extension/sixmoments/catalog/view/javascript/sixmoments.js?v=1.1.7';
        $lang = 'language=' . $this->config->get('config_language');
        $data['six_home'] = $this->url->link('common/home', $lang);
        $data['six_about_url'] = $this->url->link('extension/sixmoments/page/about', $lang);
        $data['six_diamonds_url'] = $this->url->link('extension/sixmoments/page/diamonds', $lang);
        $data['six_shipping_url'] = $this->url->link('extension/sixmoments/page/shipping', $lang);
        $data['six_faq_url'] = $this->url->link('extension/sixmoments/page/faq', $lang);
        $data['six_journal_url'] = $this->url->link('cms/blog', $lang);
        $data['six_catalog_url'] = $this->url->link('extension/sixmoments/page/catalog', $lang);
        $data['six_contact_url'] = $this->url->link('information/contact', $lang);
        $data['six_privacy_url'] = $this->url->link('extension/sixmoments/page/privacy', $lang);
        $data['six_imprint_url'] = $this->url->link('extension/sixmoments/page/imprint', $lang);
        $data['six_terms_url'] = $this->url->link('extension/sixmoments/page/terms', $lang);
        $data['six_newsletter_action'] = $this->url->link('extension/sixmoments/newsletter.subscribe', $lang);
        $data['six_instagram'] = $this->config->get('module_sixmoments_instagram');
        $data['six_email'] = $this->config->get('module_sixmoments_email');
        $data['six_year'] = date('Y');
    }

    public function home(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/common/home';
        $this->words($data);
        $data['six_products'] = $this->productThumbs($this->getSixmomentsProducts(false, 6));
        $data['six_special_products'] = $this->productThumbs($this->getSixmomentsProducts(true, 10));
        $lang = 'language=' . $this->config->get('config_language');
        $data['six_catalog'] = $this->url->link('extension/sixmoments/page/catalog', $lang);
        $data['six_special'] = $this->url->link('extension/sixmoments/page/catalog', $lang . '&sale=1');
        $data['six_quiz'] = $this->url->link('extension/sixmoments/page/quiz', $lang);
        $data['six_about'] = $this->url->link('extension/sixmoments/page/about', $lang);
        $data['six_diamonds'] = $this->url->link('extension/sixmoments/page/diamonds', $lang);
        $data['six_journal_url'] = $this->url->link('cms/blog', $lang);
        $data['six_instagram'] = $this->config->get('module_sixmoments_instagram');
        // Root-relative paths also resolve correctly when used inside CSS custom properties.
        $data['six_asset'] = '/image/catalog/sixmoments/';
        $data['six_hero_slides'] = [
            ['image' => $data['six_asset'] . 'hero-6moments-v2.png', 'mobile' => $data['six_asset'] . 'hero-6moments-mobile.png', 'position' => '58% 48%', 'kicker' => $data['six_hero_kicker'], 'title' => $data['six_hero_title']],
            ['image' => $data['six_asset'] . 'hero-6moments.webp', 'mobile' => $data['six_asset'] . 'hero-6moments.webp', 'position' => '50% 52%', 'kicker' => $data['six_hero2_kicker'], 'title' => $data['six_hero2_title']],
            ['image' => $data['six_asset'] . 'about-quote-jewelry.webp', 'mobile' => $data['six_asset'] . 'about-quote-jewelry.webp', 'position' => '50% 48%', 'kicker' => $data['six_hero3_kicker'], 'title' => $data['six_hero3_title']]
        ];
        $data['six_moments'] = [
                ['code' => '01', 'title' => $data['six_moment_yes'], 'category' => $data['six_type_rings'], 'tag' => 'engagement', 'image' => $data['six_asset'] . 'products/promise-solitaire.webp'],
                ['code' => '02', 'title' => $data['six_moment_forever'], 'category' => $data['six_type_wedding'], 'tag' => 'wedding', 'image' => $data['six_asset'] . 'products/union-band.webp'],
                ['code' => '03', 'title' => $data['six_moment_new_life'], 'category' => $data['six_type_necklaces'], 'tag' => 'motherhood', 'image' => $data['six_asset'] . 'products/arrival-pendant.webp'],
                ['code' => '04', 'title' => $data['six_moment_victory'], 'category' => $data['six_type_earrings'], 'tag' => 'career', 'image' => $data['six_asset'] . 'products/becoming-hoops.webp'],
                ['code' => '05', 'title' => $data['six_moment_deserve'], 'category' => $data['six_type_bracelets'], 'tag' => 'self-purchase', 'image' => $data['six_asset'] . 'products/gratitude-bracelet.webp'],
                ['code' => '06', 'title' => $data['six_moment_with_me'], 'category' => $data['six_type_rings'], 'tag' => 'milestone', 'image' => $data['six_asset'] . 'products/legacy-signet.webp']
        ];
        foreach ($data['six_moments'] as &$moment) {
            $moment['href'] = $this->url->link('extension/sixmoments/page/catalog', $lang . '&moment=' . rawurlencode($moment['tag']));
        }
        unset($moment);

        $category_images = ['rings'=>'promise-solitaire.webp','earrings'=>'becoming-hoops.webp','necklaces'=>'arrival-pendant.webp','bracelets'=>'gratitude-bracelet.webp','wedding'=>'union-band.webp'];
        $category_names = ['rings'=>$data['six_type_rings'],'earrings'=>$data['six_type_earrings'],'necklaces'=>$data['six_type_necklaces'],'bracelets'=>$data['six_type_bracelets'],'wedding'=>$data['six_type_wedding']];
        $data['six_category_tiles'] = [];
        foreach ($category_images as $type => $image) {
            $data['six_category_tiles'][] = ['name'=>$category_names[$type], 'image'=>$data['six_asset'] . 'products/' . $image, 'href'=>$this->url->link('extension/sixmoments/page/catalog', $lang . '&type=' . $type)];
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
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/product/product';
        $this->words($data);
        $product_id = (int)($data['product_id'] ?? 0);
        $this->load->model('catalog/product');
        $info = $product_id ? $this->model_catalog_product->getProduct($product_id) : [];
        $data['six_product_weight'] = isset($info['weight']) ? $this->formatWeight((float)$info['weight']) : '—';
        $data['six_tags'] = array_filter(array_map('trim', explode(',', (string)($info['tag'] ?? ''))));
        $data['six_moment'] = $this->momentFromTags($data['six_tags']);
        $data['six_hint_action'] = $this->url->link('extension/sixmoments/hint.send', 'language=' . $this->config->get('config_language'));
        $data['six_shipping_url'] = $this->url->link('extension/sixmoments/page/shipping', 'language=' . $this->config->get('config_language'));
        $data['six_cart_add'] = $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language'));
        $data['six_bundle_add'] = $this->url->link('extension/sixmoments/bundle.add', 'language=' . $this->config->get('config_language'));
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
        $this->load->model('extension/sixmoments/catalog');
        foreach ($this->model_extension_sixmoments_catalog->getProductIds(['sort'=>'popular','start'=>0,'limit'=>12]) as $candidate_id) {
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
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/product/thumb';
        $this->words($data);
        $tags = array_filter(array_map('trim', explode(',', (string)($data['tag'] ?? ''))));
        $data['six_moment'] = $this->momentFromTags($tags);
        $data['six_sku'] = $data['model'] ?? '';
        $data['six_product_weight'] = isset($data['weight']) ? $this->formatWeight((float)$data['weight']) : '—';
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
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/product/listing';
        $this->words($data);

        $lang = 'language=' . $this->config->get('config_language');
        $data['six_catalog_url'] = $this->url->link('extension/sixmoments/page/catalog', $lang);
        $data['six_listing_categories'] = [];

        $category_query = $this->db->query("SELECT DISTINCT c.category_id, cd.name, c.sort_order FROM `" . DB_PREFIX . "category` c INNER JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = c.category_id) INNER JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p2c.category_id = c.category_id) INNER JOIN `" . DB_PREFIX . "product` p ON (p.product_id = p2c.product_id AND p.model LIKE '6M-%') WHERE c.status = '1' AND p.status = '1' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY c.sort_order ASC, cd.name ASC LIMIT 8");
        foreach ($category_query->rows as $category) {
            $data['six_listing_categories'][] = [
                'name' => $category['name'],
                'href' => $this->url->link('extension/sixmoments/page/catalog', $lang . '&category_id=' . (int)$category['category_id'])
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
                    'href' => $this->url->link('extension/sixmoments/page/catalog', $lang . '&type=' . $type)
                ];
            }
        }
    }

    public function information(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/information/information';
        $this->words($data);
    }

    public function contact(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;

        $route = 'extension/sixmoments/information/contact';
        $this->words($data);
        $data['six_contact_email'] = $this->config->get('module_sixmoments_email') ?: $this->config->get('config_email');
    }

    public function cart(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;

        $route = 'extension/sixmoments/checkout/cart';
        $this->words($data);
    }

    public function cartList(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;

        $route = 'extension/sixmoments/checkout/cart_list';
        $this->words($data);
        $lang = 'language=' . $this->config->get('config_language');
        $data['continue'] = $this->url->link('extension/sixmoments/page/catalog', $lang);
        $data['six_shipping_url'] = $this->url->link('extension/sixmoments/page/shipping', $lang);
    }

    public function blog(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;

        $route = 'extension/sixmoments/cms/blog';
        $this->words($data);

        $fallbacks = [
            'editorial/journal-ring-architecture.webp',
            'editorial/journal-heirlooms.webp',
            'editorial/journal-patina.webp'
        ];

        foreach ($data['articles'] as $index => &$article) {
            if (empty($article['image'])) {
                $article['image'] = '/image/catalog/sixmoments/' . $fallbacks[$index % count($fallbacks)];
            }

            $article['six_label'] = $data['six_field_note'] . ' ' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
        }
        unset($article);
    }

    public function blogInfo(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;

        $route = 'extension/sixmoments/cms/blog_info';
        $this->words($data);
        $data['six_journal_url'] = $this->url->link('cms/blog', 'language=' . $this->config->get('config_language'));

        if (empty($data['image'])) {
            $data['image'] = '/image/catalog/sixmoments/editorial/journal-ring-architecture.webp';
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

    private function getSixmomentsProducts(bool $specialOnly, int $limit): array {
        $this->load->model('catalog/product');
        $sql = "SELECT p.product_id FROM `" . DB_PREFIX . "product` p";
        if ($specialOnly) {
            $sql .= " INNER JOIN `" . DB_PREFIX . "product_discount` ps ON (ps.product_id = p.product_id AND ps.special = '1')";
        }
        $sql .= " WHERE p.status = '1' AND p.model LIKE '6M-%' GROUP BY p.product_id ORDER BY p.sort_order ASC, p.product_id ASC LIMIT " . (int)$limit;
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

    private function formatWeight(float $weight): string {
        if ($weight >= 1000) {
            return rtrim(rtrim(number_format($weight / 1000, 2, '.', ''), '0'), '.') . ' kg';
        }

        return rtrim(rtrim(number_format($weight, 2, '.', ''), '0'), '.') . ' g';
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
