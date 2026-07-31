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

        $data['six_stylesheet'] = 'extension/sixmoments/catalog/view/stylesheet/sixmoments.css';
        $data['six_script'] = 'extension/sixmoments/catalog/view/javascript/sixmoments.js';
        $data['six_home'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));
        $data['six_catalog_url'] = $this->url->link('product/search', 'language=' . $this->config->get('config_language'));
        $data['six_about_url'] = $this->url->link('extension/sixmoments/page/about', 'language=' . $this->config->get('config_language'));
        $data['six_diamonds_url'] = $this->url->link('extension/sixmoments/page/diamonds', 'language=' . $this->config->get('config_language'));
        $data['six_quiz_url'] = $this->url->link('extension/sixmoments/page/quiz', 'language=' . $this->config->get('config_language'));
        $data['six_journal_url'] = $this->url->link('cms/blog', 'language=' . $this->config->get('config_language'));
        $data['six_special'] = $this->url->link('product/special', 'language=' . $this->config->get('config_language'));
        $data['six_cart_count'] = $this->cart->countProducts();
        $data['six_language_code'] = $this->config->get('config_language');
        $data['six_currency_code'] = $this->session->data['currency'] ?? $this->config->get('config_currency');

        $this->load->model('catalog/category');
        $data['six_categories'] = [];
        foreach (array_slice($this->model_catalog_category->getCategories(0), 0, 5) as $category) {
            $data['six_categories'][] = [
                'name' => $category['name'],
                'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . (int)$category['category_id'])
            ];
        }
    }

    public function footer(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/common/footer';
        $this->words($data);
        $lang = 'language=' . $this->config->get('config_language');
        $data['six_home'] = $this->url->link('common/home', $lang);
        $data['six_about_url'] = $this->url->link('extension/sixmoments/page/about', $lang);
        $data['six_diamonds_url'] = $this->url->link('extension/sixmoments/page/diamonds', $lang);
        $data['six_shipping_url'] = $this->url->link('extension/sixmoments/page/shipping', $lang);
        $data['six_faq_url'] = $this->url->link('extension/sixmoments/page/faq', $lang);
        $data['six_journal_url'] = $this->url->link('cms/blog', $lang);
        $data['six_catalog_url'] = $this->url->link('product/search', $lang);
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
        $data['six_products'] = $this->productThumbs($this->getProducts(['sort' => 'p.sort_order', 'order' => 'ASC', 'start' => 0, 'limit' => 6]));
        $data['six_specials'] = $this->productThumbs($this->getSpecials(['sort' => 'p.sort_order', 'order' => 'ASC', 'start' => 0, 'limit' => 10]));
        $lang = 'language=' . $this->config->get('config_language');
        $data['six_catalog'] = $this->url->link('product/search', $lang);
        $data['six_special'] = $this->url->link('product/special', $lang);
        $data['six_quiz'] = $this->url->link('extension/sixmoments/page/quiz', $lang);
        $data['six_about'] = $this->url->link('extension/sixmoments/page/about', $lang);
        $data['six_diamonds'] = $this->url->link('extension/sixmoments/page/diamonds', $lang);
        $data['six_instagram'] = $this->config->get('module_sixmoments_instagram');
        $data['six_asset'] = 'image/catalog/sixmoments/';
        $data['six_moments'] = [
            ['code' => '01', 'title' => $data['six_moment_yes'], 'tag' => 'engagement', 'image' => $data['six_asset'] . 'products/promise-solitaire.webp'],
            ['code' => '02', 'title' => $data['six_moment_forever'], 'tag' => 'wedding', 'image' => $data['six_asset'] . 'products/union-band.webp'],
            ['code' => '03', 'title' => $data['six_moment_new_life'], 'tag' => 'motherhood', 'image' => $data['six_asset'] . 'products/arrival-pendant.webp'],
            ['code' => '04', 'title' => $data['six_moment_victory'], 'tag' => 'career', 'image' => $data['six_asset'] . 'products/becoming-hoops.webp'],
            ['code' => '05', 'title' => $data['six_moment_deserve'], 'tag' => 'self-purchase', 'image' => $data['six_asset'] . 'products/gratitude-bracelet.webp'],
            ['code' => '06', 'title' => $data['six_moment_with_me'], 'tag' => 'milestone', 'image' => $data['six_asset'] . 'products/legacy-signet.webp']
        ];
        foreach ($data['six_moments'] as &$moment) {
            $moment['href'] = $this->url->link('product/search', $lang . '&tag=' . rawurlencode($moment['tag']));
        }
    }

    public function product(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/product/product';
        $this->words($data);
        $product_id = (int)($data['product_id'] ?? 0);
        $this->load->model('catalog/product');
        $info = $product_id ? $this->model_catalog_product->getProduct($product_id) : [];
        $data['six_product_weight'] = isset($info['weight']) ? rtrim(rtrim(number_format((float)$info['weight'], 2, '.', ''), '0'), '.') . ' g' : '—';
        $data['six_tags'] = array_filter(array_map('trim', explode(',', (string)($info['tag'] ?? ''))));
        $data['six_moment'] = $this->momentFromTags($data['six_tags']);
        $data['six_hint_action'] = $this->url->link('extension/sixmoments/hint.send', 'language=' . $this->config->get('config_language'));
        $data['six_shipping_url'] = $this->url->link('extension/sixmoments/page/shipping', 'language=' . $this->config->get('config_language'));
    }

    public function thumb(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/product/thumb';
        $this->words($data);
        $tags = array_filter(array_map('trim', explode(',', (string)($data['tag'] ?? ''))));
        $data['six_moment'] = $this->momentFromTags($tags);
        $data['six_sku'] = $data['model'] ?? '';
        $data['six_product_weight'] = isset($data['weight']) ? rtrim(rtrim(number_format((float)$data['weight'], 2, '.', ''), '0'), '.') . ' g' : '—';
        $data['six_stocked'] = (int)($data['quantity'] ?? 0) > 0;
    }

    public function listing(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/product/listing';
        $this->words($data);
    }

    public function information(string &$route, array &$data, string &$code = '', string &$output = ''): void {
        if (!$this->enabled()) return;
        $route = 'extension/sixmoments/information/information';
        $this->words($data);
    }

    private function getProducts(array $filter): array {
        $this->load->model('catalog/product');
        return $this->model_catalog_product->getProducts($filter);
    }

    private function getSpecials(array $filter): array {
        $this->load->model('catalog/product');
        return $this->model_catalog_product->getSpecials($filter);
    }

    private function productThumbs(array $results): array {
        $this->load->model('tool/image');
        $cards = [];
        foreach ($results as $result) {
            $image = !empty($result['image']) && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')) ? $result['image'] : 'placeholder.png';
            $price = $this->currency->format($this->tax->calculate((float)$result['price'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            $special = !empty($result['special']) ? $this->currency->format($this->tax->calculate((float)$result['special'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;
            $product = $result + [
                'thumb' => $this->model_tool_image->resize($image, 900, 900),
                'description' => trim(strip_tags(html_entity_decode((string)$result['description'], ENT_QUOTES, 'UTF-8'))),
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
            ];
            $cards[] = $this->load->controller('product/thumb', $product);
        }
        return $cards;
    }

    private function momentFromTags(array $tags): string {
        $map = [
            'engagement' => 'six_moment_yes', 'wedding' => 'six_moment_forever',
            'motherhood' => 'six_moment_new_life', 'career' => 'six_moment_victory',
            'self-purchase' => 'six_moment_deserve', 'milestone' => 'six_moment_with_me'
        ];
        foreach ($map as $tag => $key) if (in_array($tag, $tags, true)) return $this->language->get($key);
        return $this->language->get('six_signature_piece');
    }
}
