<?php
namespace Opencart\Admin\Model\Extension\Sixmoments\Module;

class Sixmoments extends \Opencart\System\Engine\Model {
    public function install(): void {
        $this->bootstrap(false);
    }

    public function bootstrap(bool $with_demo_data = true): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "sixmoments_subscriber` (`subscriber_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `email` VARCHAR(190) NOT NULL, `language_code` VARCHAR(16) NOT NULL, `consent` TINYINT(1) NOT NULL DEFAULT 1, `date_added` DATETIME NOT NULL, PRIMARY KEY (`subscriber_id`), UNIQUE KEY `email` (`email`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "sixmoments_hint` (`hint_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `product_id` INT UNSIGNED NOT NULL, `sender_name` VARCHAR(96) NOT NULL, `sender_email` VARCHAR(190) NOT NULL, `recipient_name` VARCHAR(96) NOT NULL, `recipient_email` VARCHAR(190) NOT NULL, `message` TEXT NOT NULL, `language_code` VARCHAR(16) NOT NULL, `date_added` DATETIME NOT NULL, PRIMARY KEY (`hint_id`), KEY `product_id` (`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->installPackageRegistration();
        $this->installEvents();
        $this->installServiceExtensions();
        $this->installSettings($with_demo_data);

        if ($with_demo_data) {
            $this->installDemo();
        }
    }

    public function installDemo(): void {
        $this->installLanguages();
        $this->installCurrencies();
        $this->seedCatalog();
        $this->seedArticles();
    }

    private function installPackageRegistration(): void {
        $installed = $this->db->query("SELECT `extension_install_id` FROM `" . DB_PREFIX . "extension_install` WHERE `code` = 'sixmoments' LIMIT 1");

        if (!$installed->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "extension_install` SET `extension_id` = '0', `extension_download_id` = '0', `name` = '6MOMENTS Universal Commerce Suite', `description` = 'Premium universal OpenCart 4.x theme with Page Builder, Mega Menu, AJAX filters, One Page Checkout and reviewed AI content tools', `code` = 'sixmoments', `version` = '2.1.0', `author` = '6MOMENTS', `link` = 'https://6moments.store', `status` = '1', `date_added` = NOW()");
        } else {
            $this->db->query("UPDATE `" . DB_PREFIX . "extension_install` SET `name` = '6MOMENTS Universal Commerce Suite', `version` = '2.1.0', `status` = '1' WHERE `extension_install_id` = '" . (int)$installed->row['extension_install_id'] . "'");
        }
    }

    private function installEvents(): void {
        $this->load->model('setting/event');
        $events = [
            ['sixmoments_header', 'catalog/view/common/header/before', 'extension/sixmoments/event/theme.header'],
            ['sixmoments_footer', 'catalog/view/common/footer/before', 'extension/sixmoments/event/theme.footer'],
            ['sixmoments_home', 'catalog/view/common/home/before', 'extension/sixmoments/event/theme.home'],
            ['sixmoments_product', 'catalog/view/product/product/before', 'extension/sixmoments/event/theme.product'],
            ['sixmoments_product_thumb', 'catalog/view/product/thumb/before', 'extension/sixmoments/event/theme.thumb'],
            ['sixmoments_category', 'catalog/view/product/category/before', 'extension/sixmoments/event/theme.listing'],
            ['sixmoments_search', 'catalog/view/product/search/before', 'extension/sixmoments/event/theme.listing'],
            ['sixmoments_special', 'catalog/view/product/special/before', 'extension/sixmoments/event/theme.listing'],
            ['sixmoments_cart', 'catalog/view/checkout/cart/before', 'extension/sixmoments/event/theme.cart'],
            ['sixmoments_cart_list', 'catalog/view/checkout/cart_list/before', 'extension/sixmoments/event/theme.cartList'],
            ['sixmoments_checkout', 'catalog/view/checkout/checkout/before', 'extension/sixmoments/event/theme.checkout'],
            ['sixmoments_account_login', 'catalog/view/account/login/before', 'extension/sixmoments/event/theme.accountLogin'],
            ['sixmoments_blog', 'catalog/view/cms/blog/before', 'extension/sixmoments/event/theme.blog'],
            ['sixmoments_blog_info', 'catalog/view/cms/blog_info/before', 'extension/sixmoments/event/theme.blogInfo'],
            ['sixmoments_information', 'catalog/view/information/information/before', 'extension/sixmoments/event/theme.information'],
            ['sixmoments_contact', 'catalog/view/information/contact/before', 'extension/sixmoments/event/theme.contact']
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
        $this->model_setting_extension->install('module', 'sixmoments', 'sixmoments');
        $this->model_setting_extension->install('payment', 'sixmoments', 'stripe');
        $this->model_setting_extension->install('shipping', 'sixmoments', 'dhl');
        $this->model_setting_extension->install('shipping', 'sixmoments', 'dpd');
        $this->model_setting_extension->install('total', 'sixmoments', 'bundle');
    }

    private function installSettings(bool $enable_storefront = false): void {
        $this->load->model('setting/setting');
        $defaults = [
            'module_sixmoments_status' => (int)$enable_storefront,
            'module_sixmoments_brand_name' => (string)($this->config->get('config_name') ?: '6MOMENTS'),
            'module_sixmoments_instagram' => $enable_storefront ? 'https://www.instagram.com/6moments_jewelry' : '',
            'module_sixmoments_email' => (string)($this->config->get('config_email') ?: ''),
            'module_sixmoments_phone' => '',
            'module_sixmoments_legal_name' => '',
            'module_sixmoments_legal_form' => '',
            'module_sixmoments_legal_representative' => '',
            'module_sixmoments_legal_address' => '',
            'module_sixmoments_legal_register' => '',
            'module_sixmoments_vat_id' => '',
            'module_sixmoments_supervisory_authority' => '',
            'module_sixmoments_content_responsible' => '',
            'module_sixmoments_privacy_email' => '',
            'module_sixmoments_data_authority' => '',
            'module_sixmoments_retention_periods' => '',
            'module_sixmoments_catalog_category_id' => 0,
            'module_sixmoments_lab_category_id' => 0,
            'module_sixmoments_page_builder' => json_encode([
                ['id' => 'hero', 'enabled' => 1], ['id' => 'featured', 'enabled' => 1],
                ['id' => 'benefits', 'enabled' => 1], ['id' => 'categories', 'enabled' => 1],
                ['id' => 'collections', 'enabled' => 1], ['id' => 'specials', 'enabled' => 1],
                ['id' => 'story', 'enabled' => 1], ['id' => 'journal', 'enabled' => 1],
                ['id' => 'social', 'enabled' => 1]
            ], JSON_UNESCAPED_SLASHES),
            'module_sixmoments_hero_kicker' => '',
            'module_sixmoments_hero_title' => '',
            'module_sixmoments_hero_cta' => '',
            'module_sixmoments_color_mode' => 'auto',
            'module_sixmoments_blog_route' => 'cms/blog',
            'module_sixmoments_native_menu_status' => 0,
            'module_sixmoments_mega_menu_status' => 1,
            'module_sixmoments_mega_menu_title' => 'Shop collections',
            'module_sixmoments_mega_menu_promo_text' => 'New arrivals',
            'module_sixmoments_mega_menu_promo_url' => '',
            'module_sixmoments_ajax_filter_status' => 1,
            'module_sixmoments_one_page_checkout_status' => 1,
            'module_sixmoments_ai_endpoint' => 'https://api.openai.com/v1/responses',
            'module_sixmoments_ai_api_key' => '',
            'module_sixmoments_ai_model' => 'gpt-5-mini',
            'module_sixmoments_ai_tone' => 'premium, clear and specific',
            'module_sixmoments_quiz_rules' => json_encode([
                'engagement' => ['moment' => 'yes', 'tags' => ['engagement', 'ring']],
                'wedding' => ['moment' => 'forever', 'tags' => ['wedding', 'anniversary']],
                'motherhood' => ['moment' => 'new-life', 'tags' => ['motherhood']],
                'career' => ['moment' => 'victory', 'tags' => ['career', 'self-purchase']],
                'self' => ['moment' => 'deserve', 'tags' => ['self-purchase']],
                'milestone' => ['moment' => 'with-me', 'tags' => ['milestone']]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        ];
        $this->installDefaultSettings('module_sixmoments', $defaults);

        $this->installDefaultSettings('payment_sixmoments_stripe', [
            'payment_sixmoments_stripe_status' => 0,
            'payment_sixmoments_stripe_secret_key' => '',
            'payment_sixmoments_stripe_webhook_secret' => '',
            'payment_sixmoments_stripe_order_status_id' => (int)(((array)$this->config->get('config_processing_status'))[0] ?? 0),
            'payment_sixmoments_stripe_sort_order' => 1
        ]);
        $this->installDefaultSettings('shipping_sixmoments_dhl', [
            'shipping_sixmoments_dhl_status' => 0,
            'shipping_sixmoments_dhl_cost' => 25,
            'shipping_sixmoments_dhl_tax_class_id' => 0,
            'shipping_sixmoments_dhl_geo_zone_id' => 0,
            'shipping_sixmoments_dhl_sort_order' => 1
        ]);
        $this->installDefaultSettings('shipping_sixmoments_dpd', [
            'shipping_sixmoments_dpd_status' => 0,
            'shipping_sixmoments_dpd_cost' => 15,
            'shipping_sixmoments_dpd_tax_class_id' => 0,
            'shipping_sixmoments_dpd_geo_zone_id' => 0,
            'shipping_sixmoments_dpd_sort_order' => 2
        ]);
        // OpenCart discovers enabled payment, shipping and total methods by these canonical keys.
        $this->installDefaultSettings('payment_stripe', [
            'payment_stripe_status' => (int)($this->config->get('payment_sixmoments_stripe_status') ?: 0),
            'payment_stripe_secret_key' => (string)($this->config->get('payment_sixmoments_stripe_secret_key') ?: ''),
            'payment_stripe_webhook_secret' => (string)($this->config->get('payment_sixmoments_stripe_webhook_secret') ?: ''),
            'payment_stripe_order_status_id' => (int)($this->config->get('payment_sixmoments_stripe_order_status_id') ?: (((array)$this->config->get('config_processing_status'))[0] ?? 0)),
            'payment_stripe_sort_order' => 1
        ]);
        $this->installDefaultSettings('shipping_dhl', [
            'shipping_dhl_status' => 0, 'shipping_dhl_cost' => 25, 'shipping_dhl_tax_class_id' => 0,
            'shipping_dhl_geo_zone_id' => 0, 'shipping_dhl_sort_order' => 1
        ]);
        $this->installDefaultSettings('shipping_dpd', [
            'shipping_dpd_status' => 0, 'shipping_dpd_cost' => 15, 'shipping_dpd_tax_class_id' => 0,
            'shipping_dpd_geo_zone_id' => 0, 'shipping_dpd_sort_order' => 2
        ]);
        $this->installDefaultSettings('total_bundle', [
            'total_bundle_status' => 0, 'total_bundle_sort_order' => 4
        ]);
    }

    private function installDefaultSettings(string $code, array $defaults): void {
        foreach ($defaults as $key => $value) {
            $existing = $this->db->query("SELECT `setting_id` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `key` = '" . $this->db->escape($key) . "' LIMIT 1");

            if (!$existing->num_rows) {
                $serialized = is_array($value);
                $stored = $serialized ? json_encode($value) : (string)$value;
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = '0', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($stored) . "', `serialized` = '" . (int)$serialized . "'");
            }
        }
    }

    private function seedCatalog(): void {
        $catalog_version = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `key` = 'module_sixmoments_catalog_version' LIMIT 1");
        $catalog_version_number = $catalog_version->num_rows ? (int)$catalog_version->row['value'] : 0;
        if ($catalog_version_number >= 5) {
            return;
        }

        // Catalog migration v3 corrects jewelry weights that were originally
        // entered as grams while OpenCart's configured class is kilograms.
        if ($catalog_version_number >= 2) {
            if ($catalog_version_number < 3) {
                $weights = [
                    '6M-RI-001' => 0.0028,
                    '6M-WE-002' => 0.0039,
                    '6M-NE-003' => 0.0021,
                    '6M-EA-004' => 0.0042,
                    '6M-BR-005' => 0.0026,
                    '6M-RI-006' => 0.0084,
                    '6M-SE-007' => 3.1
                ];
                $weight_class_id = (int)$this->config->get('config_weight_class_id');
                foreach ($weights as $model => $weight) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `weight` = '" . (float)$weight . "', `weight_class_id` = '" . $weight_class_id . "' WHERE `model` = '" . $this->db->escape($model) . "'");
                }
            }

            $this->installJewelryAttributes();
            $this->model_setting_setting->editValue('module_sixmoments', 'module_sixmoments_catalog_version', '5');
            return;
        }

        // Catalog migration v2 replaces both the stock OpenCart demo and the
        // earlier placeholder 6MOMENTS seed with the seven products published
        // on the landing page. The predicates deliberately do not match normal
        // merchant-created products.
        $this->load->model('catalog/option');
        $legacy_option_ids = array_map('intval', array_column($this->db->query("SELECT DISTINCT `po`.`option_id` FROM `" . DB_PREFIX . "product_option` `po` INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `po`.`product_id`) WHERE `p`.`model` LIKE '6M-%'")->rows, 'option_id'));

        $this->load->model('catalog/product');
        $obsolete_products = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` LIKE '6M-%' OR ((LOWER(`model`) REGEXP '^product [0-9]+$' OR `model` = 'SAM1') AND (`image` LIKE 'catalog/demo/%' OR `date_added` < '2012-01-01'))");
        foreach ($obsolete_products->rows as $product) {
            $this->model_catalog_product->deleteProduct((int)$product['product_id']);
        }

        foreach ($legacy_option_ids as $option_id) {
            $in_use = $this->db->query("SELECT `product_option_id` FROM `" . DB_PREFIX . "product_option` WHERE `option_id` = '" . $option_id . "' LIMIT 1");
            if (!$in_use->num_rows) {
                $this->model_catalog_option->deleteOption($option_id);
            }
        }

        $this->load->model('catalog/category');
        $legacy_categories = $this->db->query("SELECT DISTINCT CAST(`value` AS UNSIGNED) AS `category_id` FROM `" . DB_PREFIX . "seo_url` WHERE `key` = 'category_id' AND `keyword` LIKE 'sixmoments-%'");
        foreach ($legacy_categories->rows as $category) {
            $category_id = (int)$category['category_id'];
            $in_use = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product_to_category` WHERE `category_id` = '" . $category_id . "' LIMIT 1");
            if (!$in_use->num_rows) {
                $this->model_catalog_category->deleteCategory($category_id);
            }
        }

        $this->load->model('localisation/language');
        $language_ids = [];
        foreach (['en-gb', 'de-de', 'cs-cz', 'ru-ru', 'uk-ua'] as $code) {
            $info = $this->model_localisation_language->getLanguageByCode($code);
            if ($info) $language_ids[$code] = (int)$info['language_id'];
        }
        if (!$language_ids) return;

        $this->load->model('catalog/option');
        $option_names = ['en-gb'=>'Ring size','de-de'=>'Ringgröße','cs-cz'=>'Velikost prstenu','ru-ru'=>'Размер кольца','uk-ua'=>'Розмір каблучки'];
        $option_description=[];
        foreach($language_ids as $code=>$language_id) $option_description[$language_id]=['name'=>$option_names[$code]??$option_names['en-gb']];
        $size_values=[];
        foreach([50,52,54,56,58,60] as $sort=>$size){$value_description=[];foreach($language_ids as $language_id)$value_description[$language_id]=['name'=>(string)$size];$size_values[]=['option_value_id'=>0,'image'=>'','sort_order'=>$sort,'option_value_description'=>$value_description];}
        $size_option_id=$this->model_catalog_option->addOption(['option_description'=>$option_description,'type'=>'select','validation'=>'','sort_order'=>1,'option_value'=>$size_values]);
        $engraving_names=['en-gb'=>'Engraving','de-de'=>'Gravur','cs-cz'=>'Gravírování','ru-ru'=>'Гравировка','uk-ua'=>'Гравіювання'];$engraving_description=[];
        foreach($language_ids as $code=>$language_id)$engraving_description[$language_id]=['name'=>$engraving_names[$code]??$engraving_names['en-gb']];
        $engraving_option_id=$this->model_catalog_option->addOption(['option_description'=>$engraving_description,'type'=>'text','validation'=>'','sort_order'=>2]);
        $size_value_ids=array_map('intval',array_column($this->db->query("SELECT `option_value_id` FROM `".DB_PREFIX."option_value` WHERE `option_id`='".(int)$size_option_id."' ORDER BY `sort_order`")->rows,'option_value_id'));

        $this->load->model('catalog/category');
        $categories = [
            'rings' => ['Rings', 'Ringe', 'Prsteny', 'Кольца', 'Каблучки'],
            'earrings' => ['Earrings', 'Ohrringe', 'Náušnice', 'Серьги', 'Сережки'],
            'necklaces' => ['Necklaces', 'Halsketten', 'Náhrdelníky', 'Подвески', 'Підвіски'],
            'bracelets' => ['Bracelets', 'Armbänder', 'Náramky', 'Браслеты', 'Браслети'],
            'wedding' => ['Wedding rings', 'Trauringe', 'Snubní prsteny', 'Обручальные кольца', 'Обручки'],
            'special' => ['Special editions', 'Sondereditionen', 'Speciální edice', 'Специальные издания', 'Спеціальні видання']
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

        $products = [
            ['promise-solitaire', '6M-RI-001', 'rings', 2750, 2450, 8, 2.8, 'Promise Solitaire', 'Verlobungssolitär Promise', 'Solitér Promise', 'Солитер «Обещание»', 'Солітер «Обіцянка»', 'moment-01,engagement,ring,yellow-gold,750,lab-grown,delivery-3,carat-0-50,stones-1', 'products/promise-solitaire.webp'],
            ['union-band', '6M-WE-002', 'wedding', 980, 0, 0, 3.9, 'Union Band', 'Ehering Union', 'Snubní prsten Union', 'Обручальное кольцо «Союз»', 'Обручка «Союз»', 'moment-02,wedding,ring,yellow-gold,750,no-stones,delivery-10,stones-0', 'products/union-band.webp'],
            ['arrival-pendant', '6M-NE-003', 'necklaces', 1480, 1320, 7, 2.1, 'New Chapter Pendant', 'Anhänger Neues Kapitel', 'Přívěsek Nová kapitola', 'Подвеска «Новая глава»', 'Підвіска «Нова глава»', 'moment-03,motherhood,necklace,yellow-gold,750,natural,delivery-3,carat-0-10,stones-1', 'products/arrival-pendant.webp'],
            ['becoming-hoops', '6M-EA-004', 'earrings', 1180, 0, 10, 4.2, 'Becoming Hoops', 'Creolen Becoming', 'Náušnice Becoming', 'Серьги «Становление»', 'Сережки «Становлення»', 'moment-04,career,earring,yellow-gold,750,no-stones,delivery-3,stones-0', 'products/becoming-hoops.webp'],
            ['gratitude-bracelet', '6M-BR-005', 'bracelets', 1790, 1560, 6, 2.6, 'Gratitude Bracelet', 'Armband Dankbarkeit', 'Náramek Vděčnost', 'Браслет «Благодарность»', 'Браслет «Вдячність»', 'moment-05,self-purchase,bracelet,yellow-gold,750,natural,delivery-3,carat-0-15,stones-1', 'products/gratitude-bracelet.webp'],
            ['legacy-signet', '6M-RI-006', 'rings', 2250, 0, 0, 8.4, 'Legacy Signet', 'Siegelring Vermächtnis', 'Pečetní prsten Odkaz', 'Перстень «Наследие»', 'Перстень «Спадщина»', 'moment-06,milestone,ring,platinum,950,no-stones,delivery-10,stones-0', 'products/legacy-signet.webp'],
            ['first-ride', '6M-SE-007', 'special', 890, 0, 4, 3100, 'First Ride Balance Bike', 'Laufrad Erste Fahrt', 'Odrážedlo První jízda', 'Беговел «Первая поездка»', 'Біговел «Перша поїздка»', 'moment-special,special-edition,alloy,no-stones,delivery-3,stones-0', 'products/first-ride.webp']
        ];
        $customer_group_id = (int)$this->config->get('config_customer_group_id');
        $product_bodies = [
            'en-gb' => [
                '6M-RI-001' => 'A low-set solitaire with a softly rounded band, designed for comfort and everyday wear.',
                '6M-WE-002' => 'A timeless wedding band with a gently softened profile, made individually or as a pair and finished by hand.',
                '6M-NE-003' => 'A small point of light on a fine chain, created to preserve the day a new chapter began.',
                '6M-EA-004' => 'Light oval hoops with presence for every day and the restraint that makes them truly yours.',
                '6M-BR-005' => 'A fine bracelet with oval links and one diamond — a quiet thank-you that always stays close.',
                '6M-RI-006' => 'A substantial signet with a softened face for a monogram, date or symbol that belongs only to you.',
                '6M-SE-007' => 'An enduring piece for a first adventure, with selectable wheel diameter, frame size and colour.'
            ],
            'ru-ru' => [
                '6M-RI-001' => 'Солитер с низкой посадкой и мягко закруглённой шинкой, созданный для комфорта и ежедневного ношения.',
                '6M-WE-002' => 'Вневременное обручальное кольцо с деликатно смягчённым профилем, изготовленное отдельно или парой и отделанное вручную.',
                '6M-NE-003' => 'Маленькая точка света на тонкой цепочке — создана, чтобы сохранить день, когда началась новая глава.',
                '6M-EA-004' => 'Лёгкие овальные серьги, выразительные на каждый день и сдержанные настолько, чтобы стать по-настоящему вашими.',
                '6M-BR-005' => 'Тонкий браслет с овальными звеньями и одним бриллиантом — тихое «спасибо», которое всегда рядом.',
                '6M-RI-006' => 'Весомый перстень со смягчённой площадкой для монограммы, даты или символа, принадлежащего только вам.',
                '6M-SE-007' => 'Долговечная вещь для самого первого приключения с выбором диаметра колёс, размера рамы и цвета.'
            ],
            'uk-ua' => [
                '6M-RI-001' => 'Солітер із низькою посадкою та м’яко заокругленою шинкою, створений для зручності й щоденного носіння.',
                '6M-WE-002' => 'Позачасова обручка з делікатно пом’якшеним профілем. Виготовляється окремо або парою та оздоблюється вручну.',
                '6M-NE-003' => 'Маленька точка світла на тонкому ланцюжку — створена, щоб зберегти день, коли у світі почалася нова глава.',
                '6M-EA-004' => 'Легкі овальні сережки з виразністю для кожного дня та стриманістю, що робить їх по-справжньому вашими.',
                '6M-BR-005' => 'Тонкий браслет з овальними ланками та одним діамантом — тихе «дякую», яке завжди поруч.',
                '6M-RI-006' => 'Вагомий перстень із пом’якшеною площиною для знака, монограми, дати або символу, що належить лише вам.',
                '6M-SE-007' => 'Довговічна річ для найпершої пригоди з вибором діаметра коліс, розміру рами та кольору.'
            ]
        ];
        foreach ($products as $index => $p) {
            $names = array_slice($p, 7, 5);
            $descriptions = [];
            $seo = [];
            $i = 0;
            foreach ($language_ids as $code => $language_id) {
                $name = $names[$i++] ?? $names[0];
                $copy = $product_bodies[$code][$p[1]] ?? $product_bodies['en-gb'][$p[1]];
                $body = '<p>' . $copy . '</p>';
                $descriptions[$language_id] = ['name' => $name, 'description' => $body, 'tag' => $p[12], 'meta_title' => $name . ' | 6MOMENTS', 'meta_description' => $name . ' by 6MOMENTS Jewelry', 'meta_keyword' => ''];
                $seo[$language_id] = $p[0] . '-' . $code;
            }
            $discounts = [];
            if ($p[4]) $discounts[] = ['customer_group_id' => $customer_group_id, 'quantity' => 1, 'priority' => 1, 'price' => $p[4], 'type' => 'F', 'special' => 1, 'date_start' => '0000-00-00', 'date_end' => '0000-00-00'];
            $product_options=[['product_option_id'=>0,'option_id'=>$engraving_option_id,'value'=>'','required'=>0]];
            if(in_array($p[2],['rings','wedding'],true)){$option_values=[];foreach($size_value_ids as $option_value_id)$option_values[]=['product_option_value_id'=>0,'option_value_id'=>$option_value_id,'quantity'=>99,'subtract'=>0,'price'=>0,'price_prefix'=>'+','points'=>0,'points_prefix'=>'+','weight'=>0,'weight_prefix'=>'+'];$product_options[]=['product_option_id'=>0,'option_id'=>$size_option_id,'value'=>'','required'=>1,'product_option_value'=>$option_values];}
            $this->model_catalog_product->addProduct([
                'master_id' => 0, 'model' => $p[1], 'location' => '', 'variant' => [], 'override' => [],
                'quantity' => $p[5], 'minimum' => 1, 'subtract' => 1, 'stock_status_id' => (int)$this->config->get('config_stock_status_id'),
                'date_available' => date('Y-m-d'), 'manufacturer_id' => 0, 'shipping' => 1, 'price' => $p[3], 'points' => 0,
                'weight' => $p[6] / 1000, 'weight_class_id' => (int)$this->config->get('config_weight_class_id'),
                'length' => 0, 'width' => 0, 'height' => 0, 'length_class_id' => (int)$this->config->get('config_length_class_id'),
                'status' => 1, 'tax_class_id' => 0, 'sort_order' => $index + 1, 'image' => 'catalog/sixmoments/' . $p[13],
                'product_description' => $descriptions, 'product_code' => [['code' => 'sku', 'value' => $p[1]]],
                'product_category' => [$category_ids[$p[2]]], 'product_store' => [0], 'product_discount' => $discounts,
                'product_seo_url' => [0 => $seo], 'product_option' => $product_options
            ]);
        }

        $this->installJewelryAttributes();
        $this->model_setting_setting->editValue('module_sixmoments', 'module_sixmoments_catalog_category_id', $category_ids['rings']);
        if ($catalog_version->num_rows) {
            $this->model_setting_setting->editValue('module_sixmoments', 'module_sixmoments_catalog_version', '5');
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = '0', `code` = 'module_sixmoments', `key` = 'module_sixmoments_catalog_version', `value` = '5', `serialized` = '0'");
        }
    }

    /**
     * Install a real OpenCart attribute set for jewelry facets. Product tags
     * remain useful for editorial navigation, while these records are the
     * merchant-editable source for attribute filters and attribute sorting.
     */
    private function installJewelryAttributes(): void {
        $this->load->model('localisation/language');
        $language_ids = [];
        foreach (['en-gb', 'de-de', 'cs-cz', 'ru-ru', 'uk-ua'] as $code) {
            $language = $this->model_localisation_language->getLanguageByCode($code);
            if ($language) $language_ids[$code] = (int)$language['language_id'];
        }
        if (!$language_ids) return;

        $group_names = [
            'en-gb' => 'Jewelry specifications', 'de-de' => 'Schmuckdetails',
            'cs-cz' => 'Parametry šperku', 'ru-ru' => 'Характеристики украшения',
            'uk-ua' => 'Характеристики прикраси'
        ];
        $existing_group = $this->db->query("SELECT `attribute_group_id` FROM `" . DB_PREFIX . "attribute_group_description` WHERE `name` = 'Jewelry specifications' LIMIT 1");
        if ($existing_group->num_rows) {
            $attribute_group_id = (int)$existing_group->row['attribute_group_id'];
        } else {
            $this->load->model('catalog/attribute_group');
            $descriptions = [];
            foreach ($language_ids as $code => $language_id) {
                $descriptions[$language_id] = ['name' => $group_names[$code] ?? $group_names['en-gb']];
            }
            $attribute_group_id = $this->model_catalog_attribute_group->addAttributeGroup([
                'attribute_group_description' => $descriptions,
                'sort_order' => 1
            ]);
        }

        $attribute_names = [
            'metal' => ['Metal color', 'Metallfarbe', 'Barva kovu', 'Цвет металла', 'Колір металу'],
            'fineness' => ['Fineness', 'Feingehalt', 'Ryzost', 'Проба', 'Проба'],
            'gemstone' => ['Gemstone', 'Edelstein', 'Drahokam', 'Камень', 'Камінь'],
            'stone_origin' => ['Stone origin', 'Steinherkunft', 'Původ kamene', 'Происхождение камня', 'Походження каменю'],
            'carat' => ['Total carat weight', 'Gesamtkaratgewicht', 'Celková karátová hmotnost', 'Общая каратность', 'Загальна каратність'],
            'stone_shape' => ['Stone shape', 'Steinform', 'Tvar kamene', 'Форма огранки', 'Форма огранювання'],
            'style' => ['Jewelry style', 'Schmuckstil', 'Styl šperku', 'Стиль украшения', 'Стиль прикраси']
        ];
        $codes = array_keys($language_ids);
        $this->load->model('catalog/attribute');
        $attribute_ids = [];
        foreach ($attribute_names as $key => $names) {
            $name = $names[0];
            $existing = $this->db->query("SELECT `a`.`attribute_id` FROM `" . DB_PREFIX . "attribute` `a` INNER JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`ad`.`attribute_id` = `a`.`attribute_id`) WHERE `a`.`attribute_group_id` = '" . $attribute_group_id . "' AND `ad`.`name` = '" . $this->db->escape($name) . "' LIMIT 1");
            if ($existing->num_rows) {
                $attribute_ids[$key] = (int)$existing->row['attribute_id'];
                continue;
            }

            $descriptions = [];
            foreach ($codes as $index => $code) {
                $descriptions[$language_ids[$code]] = ['name' => $names[$index] ?? $name];
            }
            $attribute_ids[$key] = $this->model_catalog_attribute->addAttribute([
                'attribute_group_id' => $attribute_group_id,
                'attribute_description' => $descriptions,
                'sort_order' => count($attribute_ids) + 1
            ]);
        }

        $translations = [
            'yellow-gold' => ['Yellow gold', 'Gelbgold', 'Žluté zlato', 'Жёлтое золото', 'Жовте золото'],
            'platinum' => ['Platinum', 'Platin', 'Platina', 'Платина', 'Платина'],
            'diamond' => ['Diamond', 'Diamant', 'Diamant', 'Бриллиант', 'Діамант'],
            'no-stones' => ['No stones', 'Ohne Steine', 'Bez kamenů', 'Без камней', 'Без каменів'],
            'lab-grown' => ['Lab-grown', 'Laborgezüchtet', 'Laboratorní', 'Лабораторный', 'Лабораторний'],
            'natural' => ['Natural', 'Natürlich', 'Přírodní', 'Натуральный', 'Натуральний'],
            'not-applicable' => ['Not applicable', 'Nicht zutreffend', 'Nevztahuje se', 'Не применяется', 'Не застосовується'],
            'round' => ['Round', 'Rund', 'Kulatý', 'Круглая', 'Кругла'],
            'solitaire' => ['Solitaire', 'Solitär', 'Solitér', 'Солитер', 'Солітер'],
            'wedding-band' => ['Wedding band', 'Ehering', 'Snubní prsten', 'Обручальное кольцо', 'Обручка'],
            'pendant' => ['Pendant', 'Anhänger', 'Přívěsek', 'Подвеска', 'Підвіска'],
            'hoops' => ['Hoops', 'Creolen', 'Kruhy', 'Кольца', 'Кільця'],
            'chain-bracelet' => ['Chain bracelet', 'Kettenarmband', 'Řetízkový náramek', 'Цепочный браслет', 'Ланцюжковий браслет'],
            'signet' => ['Signet', 'Siegelring', 'Pečetní prsten', 'Перстень', 'Перстень']
        ];
        $specs = [
            '6M-RI-001' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'diamond','stone_origin'=>'lab-grown','carat'=>'0.50','stone_shape'=>'round','style'=>'solitaire'],
            '6M-WE-002' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'no-stones','stone_origin'=>'not-applicable','carat'=>'0','stone_shape'=>'not-applicable','style'=>'wedding-band'],
            '6M-NE-003' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'diamond','stone_origin'=>'natural','carat'=>'0.10','stone_shape'=>'round','style'=>'pendant'],
            '6M-EA-004' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'no-stones','stone_origin'=>'not-applicable','carat'=>'0','stone_shape'=>'not-applicable','style'=>'hoops'],
            '6M-BR-005' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'diamond','stone_origin'=>'natural','carat'=>'0.15','stone_shape'=>'round','style'=>'chain-bracelet'],
            '6M-RI-006' => ['metal'=>'platinum','fineness'=>'950','gemstone'=>'no-stones','stone_origin'=>'not-applicable','carat'=>'0','stone_shape'=>'not-applicable','style'=>'signet']
        ];

        $this->load->model('catalog/product');
        foreach ($specs as $model => $values) {
            $product = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` = '" . $this->db->escape($model) . "' LIMIT 1");
            if (!$product->num_rows) continue;
            $product_id = (int)$product->row['product_id'];
            foreach ($values as $key => $value) {
                $attribute_id = (int)$attribute_ids[$key];
                $this->model_catalog_product->deleteAttributes($product_id, $attribute_id);
                foreach ($codes as $index => $code) {
                    $text = in_array($key, ['fineness', 'carat'], true) ? $value : ($translations[$value][$index] ?? $value);
                    $this->model_catalog_product->addAttribute($product_id, $attribute_id, $language_ids[$code], ['text' => $text]);
                }
            }
        }

        $ring_option = $this->db->query("SELECT `option_id` FROM `" . DB_PREFIX . "option_description` WHERE LOWER(`name`) IN ('ring size','ringgröße','velikost prstenu','размер кольца','розмір каблучки') ORDER BY `option_id` DESC LIMIT 1");
        $attribute_map = ['group' => $attribute_group_id] + $attribute_ids;
        $settings = ['module_sixmoments_attribute_map' => json_encode($attribute_map)];
        if ($ring_option->num_rows) {
            $settings['module_sixmoments_ring_size_option_id'] = (string)(int)$ring_option->row['option_id'];
        }
        $this->installDefaultSettings('module_sixmoments', $settings);
    }

    private function seedArticles(): void {
        $exists = $this->db->query("SELECT `article_id` FROM `" . DB_PREFIX . "article` WHERE `author` = '6MOMENTS' LIMIT 1");
        if ($exists->num_rows) return;
        $this->load->model('localisation/language');
        $language_ids = [];
        foreach (['en-gb','de-de','cs-cz','ru-ru','uk-ua'] as $code) {
            $language = $this->model_localisation_language->getLanguageByCode($code);
            if ($language) $language_ids[$code] = (int)$language['language_id'];
        }
        if (!$language_ids) return;
        $articles = [
            ['The architecture of a forever ring','Die Architektur eines Rings für immer','Architektura prstenu navždy','Архитектура кольца навсегда','Архітектура каблучки назавжди','editorial/journal-ring-architecture.webp','Proportion, comfort and the quiet details that allow a ring to become part of everyday life.'],
            ['How modern heirlooms gather meaning','Wie moderne Erbstücke Bedeutung sammeln','Jak moderní šperky získávají význam','Как современные реликвии обретают смысл','Як сучасні реліквії набувають сенсу','editorial/journal-heirlooms.webp','A jewel becomes an heirloom through the life lived around it — through touch, memory and the stories passed forward.'],
            ['Why gold changes beautifully over time','Warum Gold mit der Zeit schöner wird','Proč zlato časem krásní','Почему золото красиво меняется со временем','Чому золото з часом стає красивішим','editorial/journal-patina.webp','Fine marks and a softening polish are not flaws. They are the visible record of a piece that has stayed close.']
        ];
        $this->load->model('cms/article');
        foreach ($articles as $index => $article) {
            $descriptions=[];$seo=[];$i=0;
            foreach ($language_ids as $code=>$language_id) {
                $name=$article[$i++]??$article[0];$descriptions[$language_id]=['image'=>'catalog/sixmoments/'.$article[5],'name'=>$name,'description'=>'<p>'.$article[6].'</p>','tag'=>'jewelry,craftsmanship,legacy','meta_title'=>$name.' | 6MOMENTS Journal','meta_description'=>$article[6],'meta_keyword'=>''];$seo[$language_id]='sixmoments-journal-'.($index+1).'-'.$code;
            }
            $this->model_cms_article->addArticle(['topic_id'=>0,'author'=>'6MOMENTS','status'=>1,'article_description'=>$descriptions,'article_store'=>[0],'article_seo_url'=>[0=>$seo]]);
        }
    }

    public function uninstall(): void {
        $this->load->model('setting/event');
        foreach (['sixmoments_header','sixmoments_footer','sixmoments_home','sixmoments_product','sixmoments_product_thumb','sixmoments_category','sixmoments_search','sixmoments_special','sixmoments_cart','sixmoments_cart_list','sixmoments_checkout','sixmoments_account_login','sixmoments_blog','sixmoments_blog_info','sixmoments_information','sixmoments_contact'] as $code) {
            $this->model_setting_event->deleteEventByCode($code);
        }

        $this->load->model('setting/extension');
        $this->model_setting_extension->uninstall('payment', 'stripe');
        $this->model_setting_extension->uninstall('shipping', 'dhl');
        $this->model_setting_extension->uninstall('shipping', 'dpd');
        $this->model_setting_extension->uninstall('total', 'bundle');
        // Customer leads, gift hints and catalog content are intentionally retained.
    }
}
