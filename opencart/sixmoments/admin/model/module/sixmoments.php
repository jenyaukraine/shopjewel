<?php
namespace Opencart\Admin\Model\Extension\Sixmoments\Module;

class Sixmoments extends \Opencart\System\Engine\Model {
    public function install(): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "sixmoments_subscriber` (`subscriber_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `email` VARCHAR(190) NOT NULL, `language_code` VARCHAR(16) NOT NULL, `consent` TINYINT(1) NOT NULL DEFAULT 1, `date_added` DATETIME NOT NULL, PRIMARY KEY (`subscriber_id`), UNIQUE KEY `email` (`email`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "sixmoments_hint` (`hint_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `product_id` INT UNSIGNED NOT NULL, `sender_name` VARCHAR(96) NOT NULL, `sender_email` VARCHAR(190) NOT NULL, `recipient_name` VARCHAR(96) NOT NULL, `recipient_email` VARCHAR(190) NOT NULL, `message` TEXT NOT NULL, `language_code` VARCHAR(16) NOT NULL, `date_added` DATETIME NOT NULL, PRIMARY KEY (`hint_id`), KEY `product_id` (`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->installLanguages();
        $this->installCurrencies();
        $this->installEvents();
        $this->installServiceExtensions();
        $this->installSettings();
        $this->seedCatalog();
    }

    private function installEvents(): void {
        $this->load->model('setting/event');
        $events = [
            ['sixmoments_header', 'view/common/header/before', 'extension/sixmoments/event/theme.header'],
            ['sixmoments_footer', 'view/common/footer/before', 'extension/sixmoments/event/theme.footer'],
            ['sixmoments_home', 'view/common/home/before', 'extension/sixmoments/event/theme.home'],
            ['sixmoments_product', 'view/product/product/before', 'extension/sixmoments/event/theme.product'],
            ['sixmoments_product_thumb', 'view/product/thumb/before', 'extension/sixmoments/event/theme.thumb'],
            ['sixmoments_category', 'view/product/category/before', 'extension/sixmoments/event/theme.listing'],
            ['sixmoments_search', 'view/product/search/before', 'extension/sixmoments/event/theme.listing'],
            ['sixmoments_special', 'view/product/special/before', 'extension/sixmoments/event/theme.listing'],
            ['sixmoments_information', 'view/information/information/before', 'extension/sixmoments/event/theme.information']
        ];

        foreach ($events as [$code, $trigger, $action]) {
            $this->model_setting_event->deleteEventByCode($code);
            $this->model_setting_event->addEvent([
                'code' => $code,
                'description' => '6MOMENTS storefront view',
                'trigger' => $trigger,
                'action' => $action,
                'status' => 1,
                'sort_order' => 900
            ]);
        }
    }

    private function installLanguages(): void {
        $this->load->model('localisation/language');
        $languages = [
            ['name' => 'Deutsch', 'code' => 'de-de', 'locale' => 'de_DE.UTF-8,de_DE,de-de,german', 'sort_order' => 2],
            ['name' => 'Čeština', 'code' => 'cs-cz', 'locale' => 'cs_CZ.UTF-8,cs_CZ,cs-cz,czech', 'sort_order' => 3],
            ['name' => 'Русский', 'code' => 'ru-ru', 'locale' => 'ru_RU.UTF-8,ru_RU,ru-ru,russian', 'sort_order' => 4],
            ['name' => 'Українська', 'code' => 'uk-ua', 'locale' => 'uk_UA.UTF-8,uk_UA,uk-ua,ukrainian', 'sort_order' => 5]
        ];

        foreach ($languages as $language) {
            if (!$this->model_localisation_language->getLanguageByCode($language['code'])) {
                $this->model_localisation_language->addLanguage($language + ['extension' => 'sixmoments', 'status' => 1]);
            }
        }
    }

    private function installCurrencies(): void {
        $this->load->model('localisation/currency');
        $currencies = [
            ['title' => 'US Dollar', 'code' => 'USD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_place' => 2, 'value' => 1.0],
            ['title' => 'Euro', 'code' => 'EUR', 'symbol_left' => '€', 'symbol_right' => '', 'decimal_place' => 2, 'value' => 0.92],
            ['title' => 'Czech Koruna', 'code' => 'CZK', 'symbol_left' => '', 'symbol_right' => ' Kč', 'decimal_place' => 0, 'value' => 23.4],
            ['title' => 'Ukrainian Hryvnia', 'code' => 'UAH', 'symbol_left' => '', 'symbol_right' => ' ₴', 'decimal_place' => 0, 'value' => 41.2]
        ];

        foreach ($currencies as $currency) {
            if (!$this->model_localisation_currency->getCurrencyByCode($currency['code'])) {
                $this->model_localisation_currency->addCurrency($currency + ['status' => 1]);
            }
        }
    }

    private function installServiceExtensions(): void {
        $this->load->model('setting/extension');
        $this->model_setting_extension->install('payment', 'sixmoments', 'stripe');
        $this->model_setting_extension->install('shipping', 'sixmoments', 'dhl');
        $this->model_setting_extension->install('shipping', 'sixmoments', 'dpd');
    }

    private function installSettings(): void {
        $this->load->model('setting/setting');
        $defaults = [
            'module_sixmoments_status' => 1,
            'module_sixmoments_instagram' => 'https://www.instagram.com/6moments_jewelry?igsh=MTdnaHg4eWo0YzlrNQ==',
            'module_sixmoments_email' => 'atelier@6moments.store',
            'module_sixmoments_phone' => '',
            'module_sixmoments_catalog_category_id' => 0,
            'module_sixmoments_lab_category_id' => 0,
            'module_sixmoments_quiz_rules' => json_encode([
                'engagement' => ['moment' => 'yes', 'tags' => ['engagement', 'ring']],
                'wedding' => ['moment' => 'forever', 'tags' => ['wedding', 'anniversary']],
                'motherhood' => ['moment' => 'new-life', 'tags' => ['motherhood']],
                'career' => ['moment' => 'victory', 'tags' => ['career', 'self-purchase']],
                'self' => ['moment' => 'deserve', 'tags' => ['self-purchase']],
                'milestone' => ['moment' => 'with-me', 'tags' => ['milestone']]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        ];
        $this->model_setting_setting->editSetting('module_sixmoments', $defaults);

        $this->model_setting_setting->editSetting('payment_sixmoments_stripe', [
            'payment_sixmoments_stripe_status' => 0,
            'payment_sixmoments_stripe_secret_key' => '',
            'payment_sixmoments_stripe_webhook_secret' => '',
            'payment_sixmoments_stripe_order_status_id' => (int)((array)$this->config->get('config_processing_status'))[0] ?? 0,
            'payment_sixmoments_stripe_sort_order' => 1
        ]);
        $this->model_setting_setting->editSetting('shipping_sixmoments_dhl', [
            'shipping_sixmoments_dhl_status' => 1,
            'shipping_sixmoments_dhl_cost' => 25,
            'shipping_sixmoments_dhl_tax_class_id' => 0,
            'shipping_sixmoments_dhl_geo_zone_id' => 0,
            'shipping_sixmoments_dhl_sort_order' => 1
        ]);
        $this->model_setting_setting->editSetting('shipping_sixmoments_dpd', [
            'shipping_sixmoments_dpd_status' => 1,
            'shipping_sixmoments_dpd_cost' => 15,
            'shipping_sixmoments_dpd_tax_class_id' => 0,
            'shipping_sixmoments_dpd_geo_zone_id' => 0,
            'shipping_sixmoments_dpd_sort_order' => 2
        ]);
    }

    private function seedCatalog(): void {
        $already = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product_code` WHERE `code` = 'sku' AND `value` LIKE '6M-%' LIMIT 1");
        if ($already->num_rows) {
            return;
        }

        $this->load->model('localisation/language');
        $language_ids = [];
        foreach (['en-gb', 'de-de', 'cs-cz', 'ru-ru', 'uk-ua'] as $code) {
            $info = $this->model_localisation_language->getLanguageByCode($code);
            if ($info) $language_ids[$code] = (int)$info['language_id'];
        }
        if (!$language_ids) return;

        $this->load->model('catalog/category');
        $categories = [
            'rings' => ['Rings', 'Ringe', 'Prsteny', 'Кольца', 'Каблучки'],
            'earrings' => ['Earrings', 'Ohrringe', 'Náušnice', 'Серьги', 'Сережки'],
            'necklaces' => ['Necklaces', 'Halsketten', 'Náhrdelníky', 'Подвески', 'Підвіски'],
            'bracelets' => ['Bracelets', 'Armbänder', 'Náramky', 'Браслеты', 'Браслети'],
            'wedding' => ['Wedding rings', 'Trauringe', 'Snubní prsteny', 'Обручальные кольца', 'Обручки']
        ];
        $category_ids = [];
        foreach ($categories as $slug => $names) {
            $description = [];
            $seo = [];
            $i = 0;
            foreach ($language_ids as $code => $language_id) {
                $name = $names[$i++] ?? $names[0];
                $description[$language_id] = ['name' => $name, 'description' => '', 'meta_title' => $name . ' | 6MOMENTS', 'meta_description' => $name . ' by 6MOMENTS Jewelry', 'meta_keyword' => ''];
                $seo[$language_id] = 'sixmoments-' . $slug . '-' . $code;
            }
            $category_ids[$slug] = $this->model_catalog_category->addCategory([
                'image' => '', 'parent_id' => 0, 'sort_order' => count($category_ids) + 1, 'status' => 1,
                'category_description' => $description, 'category_store' => [0], 'category_seo_url' => [0 => $seo]
            ]);
        }

        $this->load->model('catalog/product');
        $products = [
            ['promise-solitaire', '6M-RI-001', 'rings', 2750, 2450, 8, 2.8, 'Promise Solitaire', 'Verlobungssolitär Promise', 'Solitér Promise', 'Солитер «Обещание»', 'Солітер «Обіцянка»', 'yes,engagement,lab-grown', 'products/promise-solitaire.webp'],
            ['union-band', '6M-WE-002', 'wedding', 980, 0, 5, 3.9, 'Union Band', 'Ehering Union', 'Snubní prsten Union', 'Обручальное кольцо «Союз»', 'Обручка «Союз»', 'forever,wedding', 'products/union-band.webp'],
            ['arrival-pendant', '6M-NE-003', 'necklaces', 1480, 1320, 7, 2.1, 'Arrival Pendant', 'Arrival Anhänger', 'Přívěsek Arrival', 'Подвеска «Новая жизнь»', 'Підвіска «Нове життя»', 'new-life,motherhood,natural', 'products/arrival-pendant.webp'],
            ['becoming-hoops', '6M-EA-004', 'earrings', 1180, 0, 10, 4.2, 'Becoming Hoops', 'Becoming Creolen', 'Náušnice Becoming', 'Серьги «Моя победа»', 'Сережки «Моя перемога»', 'victory,career', 'products/becoming-hoops.webp'],
            ['gratitude-bracelet', '6M-BR-005', 'bracelets', 1790, 1560, 6, 2.6, 'Gratitude Bracelet', 'Gratitude Armband', 'Náramek Gratitude', 'Браслет «Я заслуживаю»', 'Браслет «Я заслуговую»', 'deserve,self-purchase,natural', 'products/gratitude-bracelet.webp'],
            ['legacy-signet', '6M-RI-006', 'rings', 2250, 0, 3, 8.7, 'Legacy Signet', 'Legacy Siegelring', 'Pečetní prsten Legacy', 'Перстень «С собой»', 'Перстень «Із собою»', 'with-me,milestone', 'products/legacy-signet.webp']
        ];
        $customer_group_id = (int)$this->config->get('config_customer_group_id');
        foreach ($products as $index => $p) {
            $names = array_slice($p, 7, 5);
            $descriptions = [];
            $seo = [];
            $i = 0;
            foreach ($language_ids as $code => $language_id) {
                $name = $names[$i++] ?? $names[0];
                $body = '<p>Thoughtfully designed in gold and diamonds, individually crafted and inspected before it reaches you.</p>';
                $descriptions[$language_id] = ['name' => $name, 'description' => $body, 'tag' => $p[12], 'meta_title' => $name . ' | 6MOMENTS', 'meta_description' => $name . ' by 6MOMENTS Jewelry', 'meta_keyword' => ''];
                $seo[$language_id] = $p[0] . '-' . $code;
            }
            $discounts = [];
            if ($p[4]) $discounts[] = ['customer_group_id' => $customer_group_id, 'quantity' => 1, 'priority' => 1, 'price' => $p[4], 'type' => 'F', 'special' => 1, 'date_start' => '0000-00-00', 'date_end' => '0000-00-00'];
            $this->model_catalog_product->addProduct([
                'master_id' => 0, 'model' => $p[1], 'location' => '', 'variant' => [], 'override' => [],
                'quantity' => $p[5], 'minimum' => 1, 'subtract' => 1, 'stock_status_id' => (int)$this->config->get('config_stock_status_id'),
                'date_available' => date('Y-m-d'), 'manufacturer_id' => 0, 'shipping' => 1, 'price' => $p[3], 'points' => 0,
                'weight' => $p[6], 'weight_class_id' => (int)$this->config->get('config_weight_class_id'),
                'length' => 0, 'width' => 0, 'height' => 0, 'length_class_id' => (int)$this->config->get('config_length_class_id'),
                'status' => 1, 'tax_class_id' => 0, 'sort_order' => $index + 1, 'image' => 'catalog/sixmoments/' . $p[13],
                'product_description' => $descriptions, 'product_code' => [['code' => 'sku', 'value' => $p[1]]],
                'product_category' => [$category_ids[$p[2]]], 'product_store' => [0], 'product_discount' => $discounts,
                'product_seo_url' => [0 => $seo]
            ]);
        }

        $this->model_setting_setting->editValue('module_sixmoments', 'module_sixmoments_catalog_category_id', $category_ids['rings']);
    }

    public function uninstall(): void {
        $this->load->model('setting/event');
        foreach (['sixmoments_header','sixmoments_footer','sixmoments_home','sixmoments_product','sixmoments_product_thumb','sixmoments_category','sixmoments_search','sixmoments_special','sixmoments_information'] as $code) {
            $this->model_setting_event->deleteEventByCode($code);
        }

        $this->load->model('setting/extension');
        $this->model_setting_extension->uninstall('payment', 'stripe');
        $this->model_setting_extension->uninstall('shipping', 'dhl');
        $this->model_setting_extension->uninstall('shipping', 'dpd');
        // Customer leads, gift hints and catalog content are intentionally retained.
    }
}
