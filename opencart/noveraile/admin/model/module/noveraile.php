<?php
namespace Opencart\Admin\Model\Extension\Noveraile\Module;

class Noveraile extends \Opencart\System\Engine\Model {
    public function install(): void {
        $this->bootstrap(false);
    }

    public function bootstrap(bool $with_demo_data = true): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "noveraile_subscriber` (`subscriber_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `email` VARCHAR(190) NOT NULL, `language_code` VARCHAR(16) NOT NULL, `consent` TINYINT(1) NOT NULL DEFAULT 1, `date_added` DATETIME NOT NULL, PRIMARY KEY (`subscriber_id`), UNIQUE KEY `email` (`email`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "noveraile_hint` (`hint_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `product_id` INT UNSIGNED NOT NULL, `sender_name` VARCHAR(96) NOT NULL, `sender_email` VARCHAR(190) NOT NULL, `recipient_name` VARCHAR(96) NOT NULL, `recipient_email` VARCHAR(190) NOT NULL, `message` TEXT NOT NULL, `language_code` VARCHAR(16) NOT NULL, `date_added` DATETIME NOT NULL, PRIMARY KEY (`hint_id`), KEY `product_id` (`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->installPackageRegistration();
        $this->installEvents();
        $this->installServiceExtensions();
        $this->installPermissions();
        $this->installLanguages();
        $this->installCurrencies();
        $this->installSettings($with_demo_data);
        $this->normalizeCatalogJsonColumns();
        $this->refreshProjectSettings();

        if ($with_demo_data) {
            $this->installDemo();
        } else {
            // Production keeps the OpenCart database between deployments. If
            // this installation already owns a versioned demo catalog, apply
            // its pending catalog migrations without seeding products into a
            // merchant store that never opted into demo content.
            $managed_catalog = $this->db->query("SELECT `setting_id` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `key` = 'module_noveraile_catalog_version' LIMIT 1");
            if ($managed_catalog->num_rows) {
                $this->seedCatalog();
            }
            $this->installJewelryAttributes();
        }
    }

    public function installDemo(): void {
        $this->seedCatalog();
        $this->seedArticles();
    }

    private function installPackageRegistration(): void {
        $installed = $this->db->query("SELECT `extension_install_id` FROM `" . DB_PREFIX . "extension_install` WHERE `code` = 'noveraile' LIMIT 1");

        if (!$installed->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "extension_install` SET `extension_id` = '0', `extension_download_id` = '0', `name` = 'NOVERAILE Commerce Suite', `description` = 'OpenCart 4 storefront suite with Page Builder, catalog import/export, Mega Menu, progressive filters, checkout and reviewed AI tools', `code` = 'noveraile', `version` = '2.6.2', `author` = 'NOVERAILE', `link` = '', `status` = '1', `date_added` = NOW()");
        } else {
            $this->db->query("UPDATE `" . DB_PREFIX . "extension_install` SET `name` = 'NOVERAILE Commerce Suite', `version` = '2.6.2', `status` = '1' WHERE `extension_install_id` = '" . (int)$installed->row['extension_install_id'] . "'");
        }
    }

    private function installEvents(): void {
        $this->load->model('setting/event');
        $events = [
            ['noveraile_header', 'catalog/view/common/header/before', 'extension/noveraile/event/theme.header'],
            ['noveraile_footer', 'catalog/view/common/footer/before', 'extension/noveraile/event/theme.footer'],
            ['noveraile_home', 'catalog/view/common/home/before', 'extension/noveraile/event/theme.home'],
            ['noveraile_product', 'catalog/view/product/product/before', 'extension/noveraile/event/theme.product'],
            ['noveraile_product_thumb', 'catalog/view/product/thumb/before', 'extension/noveraile/event/theme.thumb'],
            ['noveraile_category', 'catalog/view/product/category/before', 'extension/noveraile/event/theme.listing'],
            ['noveraile_search', 'catalog/view/product/search/before', 'extension/noveraile/event/theme.listing'],
            ['noveraile_special', 'catalog/view/product/special/before', 'extension/noveraile/event/theme.listing'],
            ['noveraile_cart', 'catalog/view/checkout/cart/before', 'extension/noveraile/event/theme.cart'],
            ['noveraile_cart_list', 'catalog/view/checkout/cart_list/before', 'extension/noveraile/event/theme.cartList'],
            ['noveraile_checkout', 'catalog/view/checkout/checkout/before', 'extension/noveraile/event/theme.checkout'],
            ['noveraile_success_capture', 'catalog/controller/checkout/success/before', 'extension/noveraile/event/theme.captureSuccess'],
            ['noveraile_success', 'catalog/view/common/success/before', 'extension/noveraile/event/theme.success'],
            ['noveraile_not_found', 'catalog/view/error/not_found/before', 'extension/noveraile/event/theme.notFound'],
            ['noveraile_account_login', 'catalog/view/account/login/before', 'extension/noveraile/event/theme.accountLogin'],
            ['noveraile_blog', 'catalog/view/cms/blog/before', 'extension/noveraile/event/theme.blog'],
            ['noveraile_blog_info', 'catalog/view/cms/blog_info/before', 'extension/noveraile/event/theme.blogInfo'],
            ['noveraile_information', 'catalog/view/information/information/before', 'extension/noveraile/event/theme.information'],
            ['noveraile_contact', 'catalog/view/information/contact/before', 'extension/noveraile/event/theme.contact']
        ];

        foreach ($events as [$code, $trigger, $action]) {
            $this->model_setting_event->deleteEventByCode($code);
            $this->model_setting_event->addEvent([
                'code' => $code,
                'description' => 'NOVERAILE storefront view',
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
                $this->model_localisation_language->addLanguage($language + ['extension' => 'noveraile', 'status' => 1]);
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
        $this->model_setting_extension->install('module', 'noveraile', 'noveraile');
        $this->model_setting_extension->install('payment', 'noveraile', 'stripe');
        $this->model_setting_extension->install('shipping', 'noveraile', 'dhl');
        $this->model_setting_extension->install('shipping', 'noveraile', 'dpd');
        $this->model_setting_extension->install('total', 'noveraile', 'bundle');
    }

    private function installSettings(bool $enable_storefront = false): void {
        $this->load->model('setting/setting');
        $defaults = [
            'module_noveraile_status' => (int)$enable_storefront,
            'module_noveraile_brand_name' => in_array(trim((string)$this->config->get('config_name')), ['', 'Your Store'], true) ? '6 Moments' : (string)$this->config->get('config_name'),
            'module_noveraile_instagram' => 'https://www.instagram.com/6moments_jewelry?igsh=MTdnaHg4eWo0YzlrNQ==',
            'module_noveraile_email' => '6moments.jewelry@gmail.com',
            'module_noveraile_phone' => '+49 170 7647729',
            'module_noveraile_whatsapp' => 'https://wa.me/491707647729',
            'module_noveraile_telegram' => 'https://wa.me/491707647729',
            'module_noveraile_facebook' => 'https://www.facebook.com/profile.php?id=61587187514053',
            'module_noveraile_legal_name' => '',
            'module_noveraile_legal_form' => '',
            'module_noveraile_legal_representative' => '',
            'module_noveraile_legal_address' => '',
            'module_noveraile_legal_register' => '',
            'module_noveraile_vat_id' => '',
            'module_noveraile_supervisory_authority' => '',
            'module_noveraile_content_responsible' => '',
            'module_noveraile_privacy_email' => '',
            'module_noveraile_data_authority' => '',
            'module_noveraile_retention_periods' => '',
            'module_noveraile_catalog_category_id' => 0,
            'module_noveraile_lab_category_id' => 0,
            'module_noveraile_page_builder' => json_encode([
                ['id' => 'hero', 'enabled' => 1], ['id' => 'featured', 'enabled' => 1],
                ['id' => 'benefits', 'enabled' => 1], ['id' => 'categories', 'enabled' => 1],
                ['id' => 'collections', 'enabled' => 1], ['id' => 'specials', 'enabled' => 1],
                ['id' => 'story', 'enabled' => 1], ['id' => 'journal', 'enabled' => 1],
                ['id' => 'social', 'enabled' => 1]
            ], JSON_UNESCAPED_SLASHES),
            'module_noveraile_hero_kicker' => '',
            'module_noveraile_hero_title' => '',
            'module_noveraile_hero_cta' => '',
            'module_noveraile_blog_route' => 'cms/blog',
            'module_noveraile_native_menu_status' => 0,
            'module_noveraile_mega_menu_status' => 1,
            'module_noveraile_mega_menu_title' => 'Shop collections',
            'module_noveraile_mega_menu_promo_text' => 'New arrivals',
            'module_noveraile_mega_menu_promo_url' => '',
            'module_noveraile_ajax_filter_status' => 1,
            'module_noveraile_one_page_checkout_status' => 1,
            'module_noveraile_ai_endpoint' => 'https://api.openai.com/v1/responses',
            'module_noveraile_ai_api_key' => '',
            'module_noveraile_ai_model' => 'gpt-5-mini',
            'module_noveraile_ai_tone' => 'premium, clear and specific',
            'module_noveraile_quiz_rules' => json_encode([
                'engagement' => ['moment' => 'yes', 'tags' => ['engagement', 'ring']],
                'wedding' => ['moment' => 'forever', 'tags' => ['wedding', 'anniversary']],
                'motherhood' => ['moment' => 'new-life', 'tags' => ['motherhood']],
                'career' => ['moment' => 'victory', 'tags' => ['career', 'self-purchase']],
                'self' => ['moment' => 'deserve', 'tags' => ['self-purchase']],
                'milestone' => ['moment' => 'with-me', 'tags' => ['milestone']]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'module_noveraile_price_book' => $this->defaultPriceBook(),
            'module_noveraile_price_multiplier' => '1'
        ];
        $this->installDefaultSettings('module_noveraile', $defaults);
        $current_brand = trim((string)$this->config->get('module_noveraile_brand_name'));
        if ($current_brand === '' || $current_brand === 'Your Store') {
            $this->model_setting_setting->editValue('module_noveraile', 'module_noveraile_brand_name', '6 Moments');
        }
        $current_instagram = trim((string)$this->config->get('module_noveraile_instagram'));
        if ($current_instagram === '' || str_contains($current_instagram, 'journal.framework')) {
            $this->model_setting_setting->editValue('module_noveraile', 'module_noveraile_instagram', 'https://www.instagram.com/6moments_jewelry?igsh=MTdnaHg4eWo0YzlrNQ==');
        }

        $this->installDefaultSettings('payment_noveraile_stripe', [
            'payment_noveraile_stripe_status' => 0,
            'payment_noveraile_stripe_secret_key' => '',
            'payment_noveraile_stripe_webhook_secret' => '',
            'payment_noveraile_stripe_order_status_id' => (int)(((array)$this->config->get('config_processing_status'))[0] ?? 0),
            'payment_noveraile_stripe_sort_order' => 1
        ]);
        $this->installDefaultSettings('shipping_noveraile_dhl', [
            'shipping_noveraile_dhl_status' => 0,
            'shipping_noveraile_dhl_cost' => 25,
            'shipping_noveraile_dhl_tax_class_id' => 0,
            'shipping_noveraile_dhl_geo_zone_id' => 0,
            'shipping_noveraile_dhl_sort_order' => 1
        ]);
        $this->installDefaultSettings('shipping_noveraile_dpd', [
            'shipping_noveraile_dpd_status' => 0,
            'shipping_noveraile_dpd_cost' => 15,
            'shipping_noveraile_dpd_tax_class_id' => 0,
            'shipping_noveraile_dpd_geo_zone_id' => 0,
            'shipping_noveraile_dpd_sort_order' => 2
        ]);
        // OpenCart discovers enabled payment, shipping and total methods by these canonical keys.
        $this->installDefaultSettings('payment_stripe', [
            'payment_stripe_status' => (int)($this->config->get('payment_noveraile_stripe_status') ?: 0),
            'payment_stripe_secret_key' => (string)($this->config->get('payment_noveraile_stripe_secret_key') ?: ''),
            'payment_stripe_webhook_secret' => (string)($this->config->get('payment_noveraile_stripe_webhook_secret') ?: ''),
            'payment_stripe_order_status_id' => (int)($this->config->get('payment_noveraile_stripe_order_status_id') ?: (((array)$this->config->get('config_processing_status'))[0] ?? 0)),
            'payment_stripe_sort_order' => 1
        ]);
        $stripe_secret = trim((string)(getenv('STRIPE_SECRET_KEY') ?: ''));
        $stripe_webhook = trim((string)(getenv('STRIPE_WEBHOOK_SECRET') ?: ''));
        if (preg_match('/^sk_(?:test|live)_[A-Za-z0-9]+$/', $stripe_secret) && preg_match('/^whsec_[A-Za-z0-9]+$/', $stripe_webhook)) {
            $this->model_setting_setting->editValue('payment_stripe', 'payment_stripe_secret_key', $stripe_secret);
            $this->model_setting_setting->editValue('payment_stripe', 'payment_stripe_webhook_secret', $stripe_webhook);
            $this->model_setting_setting->editValue('payment_stripe', 'payment_stripe_status', '1');
        }
        $this->installDefaultSettings('shipping_dhl', [
            'shipping_dhl_status' => 0, 'shipping_dhl_cost' => 25,
            'shipping_dhl_eu_cost' => 25, 'shipping_dhl_world_cost' => 25, 'shipping_dhl_tax_class_id' => 0,
            'shipping_dhl_geo_zone_id' => 0, 'shipping_dhl_sort_order' => 1
        ]);
        $this->installDefaultSettings('shipping_dpd', [
            'shipping_dpd_status' => 0, 'shipping_dpd_cost' => 15,
            'shipping_dpd_ukraine_cost' => 15, 'shipping_dpd_eu_cost' => 15, 'shipping_dpd_tax_class_id' => 0,
            'shipping_dpd_geo_zone_id' => 0, 'shipping_dpd_sort_order' => 2
        ]);
        $this->installDefaultSettings('total_bundle', [
            'total_bundle_status' => 1, 'total_bundle_sort_order' => 4
        ]);
    }

    private function refreshProjectSettings(): void {
        $replacements = [
            'module_noveraile_brand_name' => ['value' => '6 Moments', 'legacy' => ['', 'Your Store', 'NOVERAILE']],
            'module_noveraile_email' => ['value' => '6moments.jewelry@gmail.com', 'legacy' => ['', 'atelier@6moments.store']],
            'module_noveraile_phone' => ['value' => '+49 170 7647729', 'legacy' => ['']],
            'module_noveraile_whatsapp' => ['value' => 'https://wa.me/491707647729', 'legacy' => ['']],
            'module_noveraile_telegram' => ['value' => 'https://wa.me/491707647729', 'legacy' => ['']],
            'module_noveraile_facebook' => ['value' => 'https://www.facebook.com/profile.php?id=61587187514053', 'legacy' => ['']]
        ];
        foreach ($replacements as $key => $replacement) {
            $current = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `key` = '" . $this->db->escape($key) . "' LIMIT 1");
            if ($current->num_rows && in_array(trim((string)$current->row['value']), $replacement['legacy'], true)) {
                $this->model_setting_setting->editValue('module_noveraile', $key, $replacement['value']);
            }
        }

        // The bundled 6 Moments production catalog has fixed, credential-free
        // delivery rates. Keep marketplace installs opt-in, but make an
        // existing managed storefront checkout-ready after every deployment.
        $managed_catalog = $this->db->query("SELECT `setting_id` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `key` = 'module_noveraile_catalog_version' LIMIT 1");
        if ($managed_catalog->num_rows && $this->config->get('module_noveraile_status')) {
            $this->model_setting_setting->editValue('shipping_dhl', 'shipping_dhl_status', '1');
            $this->model_setting_setting->editValue('shipping_dpd', 'shipping_dpd_status', '1');
        }
    }

    private function normalizeCatalogJsonColumns(): void {
        // External feeds can leave OpenCart 4.1 JSON fields as NULL. The cart
        // decodes them on every request, leaking a PHP warning into HTML/JSON.
        foreach (['variant', 'override'] as $column) {
            $exists = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE '" . $column . "'");
            if ($exists->num_rows) {
                $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `" . $column . "` = '[]' WHERE `" . $column . "` IS NULL OR TRIM(`" . $column . "`) = '' OR `" . $column . "` = 'null'");
            }
        }
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
        $catalog_version = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `key` = 'module_noveraile_catalog_version' LIMIT 1");
        $catalog_version_number = $catalog_version->num_rows ? (int)$catalog_version->row['value'] : 0;
        if ($catalog_version_number >= 7) {
            return;
        }

        // Catalog migration v3 corrects jewelry weights that were originally
        // entered as grams while OpenCart's configured class is kilograms.
        if ($catalog_version_number >= 2) {
            if ($catalog_version_number < 3) {
                $weights = [
                    'NVR-RI-001' => 0.0028,
                    'NVR-WE-002' => 0.0039,
                    'NVR-NE-003' => 0.0021,
                    'NVR-EA-004' => 0.0042,
                    'NVR-BR-005' => 0.0026,
                    'NVR-RI-006' => 0.0084
                ];
                $weight_class_id = (int)$this->config->get('config_weight_class_id');
                foreach ($weights as $model => $weight) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `weight` = '" . (float)$weight . "', `weight_class_id` = '" . $weight_class_id . "' WHERE `model` = '" . $this->db->escape($model) . "'");
                }
            }

        }

        // Catalog migration v7 replaces the earlier placeholder seed with ten
        // discounted jewelry pieces. The predicates deliberately do not match
        // normal merchant-created products.
        $this->load->model('catalog/option');
        $legacy_option_ids = array_map('intval', array_column($this->db->query("SELECT DISTINCT `po`.`option_id` FROM `" . DB_PREFIX . "product_option` `po` INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `po`.`product_id`) WHERE `p`.`model` LIKE 'NVR-%'")->rows, 'option_id'));

        $this->load->model('catalog/product');
        $obsolete_products = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` LIKE 'NVR-%' OR ((LOWER(`model`) REGEXP '^product [0-9]+$' OR `model` = 'SAM1') AND (`image` LIKE 'catalog/demo/%' OR `date_added` < '2012-01-01'))");
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
        $legacy_categories = $this->db->query("SELECT DISTINCT CAST(`value` AS UNSIGNED) AS `category_id` FROM `" . DB_PREFIX . "seo_url` WHERE `key` = 'category_id' AND `keyword` LIKE 'noveraile-%'");
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
        $configured_brand = trim((string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name')));
        $brand = in_array($configured_brand, ['', 'Your Store'], true) ? '6 Moments' : $configured_brand;

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
                $description[$language_id] = ['name' => $name, 'description' => '', 'meta_title' => $name . ' | ' . $brand, 'meta_description' => $name . ' by ' . $brand . ' Jewelry', 'meta_keyword' => ''];
                $seo[$language_id] = 'noveraile-' . $slug . '-' . $code;
            }
            $category_ids[$slug] = $this->model_catalog_category->addCategory([
                'image' => '', 'parent_id' => 0, 'sort_order' => count($category_ids) + 1, 'status' => 1,
                'category_description' => $description, 'category_store' => [0], 'category_seo_url' => [0 => $seo]
            ]);
        }

        $products = [
            ['promise-solitaire', 'NVR-RI-001', 'rings', 2750, 2450, 8, 2.8, 'Promise Solitaire', 'Verlobungssolitär Promise', 'Solitér Promise', 'Солитер «Обещание»', 'Солітер «Обіцянка»', 'moment-01,engagement,ring,yellow-gold,750,lab-grown,delivery-3,carat-0-50,stones-1', 'products/promise-solitaire.webp'],
            ['union-band', 'NVR-WE-002', 'wedding', 980, 890, 0, 3.9, 'Union Band', 'Ehering Union', 'Snubní prsten Union', 'Обручальное кольцо «Союз»', 'Обручка «Союз»', 'moment-02,wedding,ring,yellow-gold,750,no-stones,delivery-10,stones-0', 'products/union-band.webp'],
            ['arrival-pendant', 'NVR-NE-003', 'necklaces', 1480, 1320, 7, 2.1, 'New Chapter Pendant', 'Anhänger Neues Kapitel', 'Přívěsek Nová kapitola', 'Подвеска «Новая глава»', 'Підвіска «Нова глава»', 'moment-03,motherhood,necklace,yellow-gold,750,natural,delivery-3,carat-0-10,stones-1', 'products/arrival-pendant.webp'],
            ['becoming-hoops', 'NVR-EA-004', 'earrings', 1180, 1040, 10, 4.2, 'Becoming Hoops', 'Creolen Becoming', 'Náušnice Becoming', 'Серьги «Становление»', 'Сережки «Становлення»', 'moment-04,career,earring,yellow-gold,750,no-stones,delivery-3,stones-0', 'products/becoming-hoops.webp'],
            ['gratitude-bracelet', 'NVR-BR-005', 'bracelets', 1790, 1560, 6, 2.6, 'Gratitude Bracelet', 'Armband Dankbarkeit', 'Náramek Vděčnost', 'Браслет «Благодарность»', 'Браслет «Вдячність»', 'moment-05,self-purchase,bracelet,yellow-gold,750,natural,delivery-3,carat-0-15,stones-1', 'products/gratitude-bracelet.webp'],
            ['legacy-signet', 'NVR-RI-006', 'rings', 2250, 1990, 0, 8.4, 'Legacy Signet', 'Siegelring Vermächtnis', 'Pečetní prsten Odkaz', 'Перстень «Наследие»', 'Перстень «Спадщина»', 'moment-06,milestone,ring,yellow-gold,750,no-stones,delivery-10,stones-0', 'products/legacy-signet.webp'],
            ['eternity-band', 'NVR-WE-007', 'wedding', 1650, 1480, 5, 3.4, 'Eternity Band', 'Eternity-Ring', 'Prsten Eternity', 'Кольцо «Вечность»', 'Каблучка «Вічність»', 'moment-02,wedding,ring,rose-gold,750,natural,delivery-3,carat-0-20,stones-18', 'products/union-band.webp'],
            ['horizon-studs', 'NVR-EA-008', 'earrings', 1320, 1160, 8, 2.2, 'Horizon Studs', 'Ohrstecker Horizont', 'Náušnice Horizont', 'Пусеты «Горизонт»', 'Пусети «Горизонт»', 'moment-04,career,earring,white-gold,750,lab-grown,delivery-3,carat-0-30,stones-2', 'products/becoming-hoops.webp'],
            ['keepsake-pendant', 'NVR-NE-009', 'necklaces', 1540, 1390, 6, 2.4, 'Keepsake Pendant', 'Anhänger Erinnerung', 'Přívěsek Vzpomínka', 'Подвеска «Память»', 'Підвіска «Спогад»', 'moment-03,motherhood,necklace,rose-gold,750,lab-grown,delivery-3,carat-0-25,stones-1', 'products/arrival-pendant.webp'],
            ['self-promise-ring', 'NVR-RI-010', 'rings', 1880, 1690, 7, 3.1, 'Self Promise Ring', 'Ring Selbstversprechen', 'Prsten Slib sobě', 'Кольцо «Обещание себе»', 'Каблучка «Обіцянка собі»', 'moment-05,self-purchase,ring,white-gold,750,natural,delivery-3,carat-0-35,stones-7', 'products/promise-solitaire.webp']
        ];
        $customer_group_id = (int)$this->config->get('config_customer_group_id');
        $product_bodies = [
            'en-gb' => [
                'NVR-RI-001' => 'A low-set solitaire with a softly rounded band, designed for comfort and everyday wear.',
                'NVR-WE-002' => 'A timeless wedding band with a gently softened profile, made individually or as a pair and finished by hand.',
                'NVR-NE-003' => 'A small point of light on a fine chain, created to preserve the day a new chapter began.',
                'NVR-EA-004' => 'Light oval hoops with presence for every day and the restraint that makes them truly yours.',
                'NVR-BR-005' => 'A fine bracelet with oval links and one diamond — a quiet thank-you that always stays close.',
                'NVR-RI-006' => 'A substantial signet with a softened face for a monogram, date or symbol that belongs only to you.',
                'NVR-WE-007' => 'A rose-gold eternity band set with eighteen natural diamonds to mark a promise that keeps unfolding.',
                'NVR-EA-008' => 'Two lab-grown diamonds in clean white-gold settings, made for everyday milestones and quiet victories.',
                'NVR-NE-009' => 'A rose-gold pendant with a lab-grown diamond, created to keep a new chapter close.',
                'NVR-RI-010' => 'A white-gold diamond ring made as a personal promise and finished for comfortable daily wear.'
            ],
            'de-de' => [
                'NVR-RI-001' => 'Ein tief gefasster Solitär mit sanft gerundeter Ringschiene für hohen Tragekomfort im Alltag.',
                'NVR-WE-002' => 'Ein zeitloser Ehering mit weichem Profil, einzeln oder als Paar gefertigt und von Hand vollendet.',
                'NVR-NE-003' => 'Ein kleiner Lichtpunkt an einer feinen Kette, geschaffen für den Tag, an dem ein neues Kapitel begann.',
                'NVR-EA-004' => 'Leichte ovale Creolen mit Präsenz für jeden Tag und einer Zurückhaltung, die sie ganz persönlich macht.',
                'NVR-BR-005' => 'Ein feines Armband mit ovalen Gliedern und einem Diamanten – ein stilles Dankeschön, das nahe bleibt.',
                'NVR-RI-006' => 'Ein markanter Siegelring mit weicher Fläche für Monogramm, Datum oder ein persönliches Symbol.',
                'NVR-WE-007' => 'Ein Eternity-Ring aus Roségold mit achtzehn natürlichen Diamanten für ein Versprechen, das weiterwächst.',
                'NVR-EA-008' => 'Zwei im Labor gezüchtete Diamanten in klaren Weißgoldfassungen für tägliche Meilensteine.',
                'NVR-NE-009' => 'Ein Roségoldanhänger mit Labordiamant, der ein neues Kapitel ganz nah bewahrt.',
                'NVR-RI-010' => 'Ein Weißgoldring mit natürlichen Diamanten als persönliches Versprechen für jeden Tag.'
            ],
            'cs-cz' => [
                'NVR-RI-001' => 'Nízký solitér s jemně zaoblenou obroučkou navržený pro pohodlné každodenní nošení.',
                'NVR-WE-002' => 'Nadčasový snubní prsten s měkkým profilem, vyráběný jednotlivě nebo v páru a ručně dokončený.',
                'NVR-NE-003' => 'Malý bod světla na jemném řetízku, vytvořený pro den, kdy začala nová kapitola.',
                'NVR-EA-004' => 'Lehké oválné kruhy výrazné pro každý den a zároveň dokonale střídmé.',
                'NVR-BR-005' => 'Jemný náramek s oválnými články a jedním diamantem – tiché poděkování, které zůstává nablízku.',
                'NVR-RI-006' => 'Výrazný pečetní prsten s měkčenou plochou pro monogram, datum nebo osobní symbol.',
                'NVR-WE-007' => 'Prsten z růžového zlata s osmnácti přírodními diamanty pro slib, který stále roste.',
                'NVR-EA-008' => 'Dva laboratorní diamanty v čistých obrubách z bílého zlata pro každodenní vítězství.',
                'NVR-NE-009' => 'Přívěsek z růžového zlata s laboratorním diamantem, který uchová novou kapitolu nablízku.',
                'NVR-RI-010' => 'Prsten z bílého zlata s přírodními diamanty jako osobní slib pro každý den.'
            ],
            'ru-ru' => [
                'NVR-RI-001' => 'Солитер с низкой посадкой и мягко закруглённой шинкой, созданный для комфорта и ежедневного ношения.',
                'NVR-WE-002' => 'Вневременное обручальное кольцо с деликатно смягчённым профилем, изготовленное отдельно или парой и отделанное вручную.',
                'NVR-NE-003' => 'Маленькая точка света на тонкой цепочке — создана, чтобы сохранить день, когда началась новая глава.',
                'NVR-EA-004' => 'Лёгкие овальные серьги, выразительные на каждый день и сдержанные настолько, чтобы стать по-настоящему вашими.',
                'NVR-BR-005' => 'Тонкий браслет с овальными звеньями и одним бриллиантом — тихое «спасибо», которое всегда рядом.',
                'NVR-RI-006' => 'Весомый перстень со смягчённой площадкой для монограммы, даты или символа, принадлежащего только вам.',
                'NVR-WE-007' => 'Кольцо вечности из розового золота с восемнадцатью натуральными бриллиантами — знак обещания, которое продолжается.',
                'NVR-EA-008' => 'Два лабораторных бриллианта в лаконичных оправах из белого золота — для ежедневных побед.',
                'NVR-NE-009' => 'Подвеска из розового золота с лабораторным бриллиантом, сохраняющая новую главу рядом.',
                'NVR-RI-010' => 'Кольцо из белого золота с натуральными бриллиантами — личное обещание на каждый день.'
            ],
            'uk-ua' => [
                'NVR-RI-001' => 'Солітер із низькою посадкою та м’яко заокругленою шинкою, створений для зручності й щоденного носіння.',
                'NVR-WE-002' => 'Позачасова обручка з делікатно пом’якшеним профілем. Виготовляється окремо або парою та оздоблюється вручну.',
                'NVR-NE-003' => 'Маленька точка світла на тонкому ланцюжку — створена, щоб зберегти день, коли у світі почалася нова глава.',
                'NVR-EA-004' => 'Легкі овальні сережки з виразністю для кожного дня та стриманістю, що робить їх по-справжньому вашими.',
                'NVR-BR-005' => 'Тонкий браслет з овальними ланками та одним діамантом — тихе «дякую», яке завжди поруч.',
                'NVR-RI-006' => 'Вагомий перстень із пом’якшеною площиною для знака, монограми, дати або символу, що належить лише вам.',
                'NVR-WE-007' => 'Каблучка вічності з рожевого золота з вісімнадцятьма природними діамантами — знак обіцянки, що триває.',
                'NVR-EA-008' => 'Два лабораторні діаманти в лаконічних оправах із білого золота — для щоденних перемог.',
                'NVR-NE-009' => 'Підвіска з рожевого золота з лабораторним діамантом, що зберігає новий розділ поруч.',
                'NVR-RI-010' => 'Каблучка з білого золота з природними діамантами — особиста обіцянка на кожен день.'
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
                $descriptions[$language_id] = ['name' => $name, 'description' => $body, 'tag' => $p[12], 'meta_title' => $name . ' | ' . $brand, 'meta_description' => $name . ' by ' . $brand . ' Jewelry', 'meta_keyword' => ''];
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
                'status' => 1, 'tax_class_id' => 0, 'sort_order' => $index + 1, 'image' => 'catalog/noveraile/' . $p[13],
                'product_description' => $descriptions, 'product_code' => [['code' => 'sku', 'value' => $p[1]]],
                'product_category' => [$category_ids[$p[2]]], 'product_store' => [0], 'product_discount' => $discounts,
                'product_seo_url' => [0 => $seo], 'product_option' => $product_options
            ]);
        }

        $this->installJewelryAttributes();
        $this->model_setting_setting->editValue('module_noveraile', 'module_noveraile_catalog_category_id', $category_ids['rings']);
        if ($catalog_version->num_rows) {
            $this->model_setting_setting->editValue('module_noveraile', 'module_noveraile_catalog_version', '7');
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = '0', `code` = 'module_noveraile', `key` = 'module_noveraile_catalog_version', `value` = '7', `serialized` = '0'");
        }
        $this->model_setting_setting->editValue('total_bundle', 'total_bundle_status', '1');
    }

    private function defaultPriceBook(): string {
        $base = [
            'NVR-RI-001'=>[2750,2450], 'NVR-WE-002'=>[980,890], 'NVR-NE-003'=>[1480,1320],
            'NVR-EA-004'=>[1180,1040], 'NVR-BR-005'=>[1790,1560], 'NVR-RI-006'=>[2250,1990],
            'NVR-WE-007'=>[1650,1480], 'NVR-EA-008'=>[1320,1160], 'NVR-NE-009'=>[1540,1390],
            'NVR-RI-010'=>[1880,1690]
        ];
        $markets = ['USD'=>[1.0,10], 'EUR'=>[0.94,10], 'CZK'=>[23.0,100], 'UAH'=>[41.0,100]];
        $book = [];
        foreach ($markets as $currency => [$multiplier, $step]) {
            foreach ($base as $model => [$price, $special]) {
                $book[$currency][$model] = [
                    'price' => round($price * $multiplier / $step) * $step,
                    'special' => round($special * $multiplier / $step) * $step
                ];
            }
        }
        return json_encode($book, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private function installPermissions(): void {
        $route = 'extension/noveraile/module/noveraile';
        $group = $this->db->query("SELECT `user_group_id`, `permission` FROM `" . DB_PREFIX . "user_group` WHERE `user_group_id` = '1' LIMIT 1");
        if (!$group->num_rows) return;

        $permission = json_decode((string)$group->row['permission'], true);
        if (!is_array($permission)) $permission = [];
        foreach (['access', 'modify'] as $type) {
            if (!isset($permission[$type]) || !is_array($permission[$type])) $permission[$type] = [];
            if (!in_array($route, $permission[$type], true)) $permission[$type][] = $route;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET `permission` = '" . $this->db->escape(json_encode($permission, JSON_UNESCAPED_SLASHES)) . "' WHERE `user_group_id` = '" . (int)$group->row['user_group_id'] . "'");
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
            'stone_count' => ['Number of stones', 'Anzahl der Steine', 'Počet kamenů', 'Количество камней', 'Кількість каменів'],
            'stone_shape' => ['Stone shape', 'Steinform', 'Tvar kamene', 'Форма огранки', 'Форма огранювання'],
            'stone_quality' => ['Stone quality', 'Steinqualität', 'Kvalita kamene', 'Качество камня', 'Якість каменю'],
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
            'white-gold' => ['White gold', 'Weißgold', 'Bílé zlato', 'Белое золото', 'Біле золото'],
            'rose-gold' => ['Rose gold', 'Roségold', 'Růžové zlato', 'Розовое золото', 'Рожеве золото'],
            'diamond' => ['Diamond', 'Diamant', 'Diamant', 'Бриллиант', 'Діамант'],
            'no-stones' => ['No stones', 'Ohne Steine', 'Bez kamenů', 'Без камней', 'Без каменів'],
            'lab-grown' => ['Lab-grown', 'Laborgezüchtet', 'Laboratorní', 'Лабораторный', 'Лабораторний'],
            'natural' => ['Natural', 'Natürlich', 'Přírodní', 'Натуральный', 'Натуральний'],
            'not-applicable' => ['Not applicable', 'Nicht zutreffend', 'Nevztahuje se', 'Не применяется', 'Не застосовується'],
            'round' => ['Round', 'Rund', 'Kulatý', 'Круглая', 'Кругла'],
            'princess' => ['Princess', 'Prinzess', 'Princess', 'Принцесса', 'Принцеса'],
            'marquise' => ['Marquise', 'Marquise', 'Markýza', 'Маркиз', 'Маркіз'],
            'baguette' => ['Baguette', 'Baguette', 'Bageta', 'Багет', 'Багет'],
            'cushion' => ['Cushion', 'Kissen', 'Polštářek', 'Кушон', 'Кушон'],
            'heart' => ['Heart', 'Herz', 'Srdce', 'Сердце', 'Серце'],
            'oval' => ['Oval', 'Oval', 'Ovál', 'Овал', 'Овал'],
            'solitaire' => ['Solitaire', 'Solitär', 'Solitér', 'Солитер', 'Солітер'],
            'wedding-band' => ['Wedding band', 'Ehering', 'Snubní prsten', 'Обручальное кольцо', 'Обручка'],
            'pendant' => ['Pendant', 'Anhänger', 'Přívěsek', 'Подвеска', 'Підвіска'],
            'hoops' => ['Hoops', 'Creolen', 'Kruhy', 'Кольца', 'Кільця'],
            'chain-bracelet' => ['Chain bracelet', 'Kettenarmband', 'Řetízkový náramek', 'Цепочный браслет', 'Ланцюжковий браслет'],
            'signet' => ['Signet', 'Siegelring', 'Pečetní prsten', 'Перстень', 'Перстень'],
            'eternity' => ['Eternity band', 'Eternity-Ring', 'Eternity prsten', 'Кольцо вечности', 'Каблучка вічності'],
            'studs' => ['Stud earrings', 'Ohrstecker', 'Pecky', 'Пусеты', 'Пусети']
        ];
        $specs = [
            'NVR-RI-001' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'diamond','stone_origin'=>'lab-grown','carat'=>'0.50','stone_shape'=>'round','stone_quality'=>'G/VS2','style'=>'solitaire'],
            'NVR-WE-002' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'no-stones','stone_origin'=>'not-applicable','carat'=>'0','stone_shape'=>'not-applicable','style'=>'wedding-band'],
            'NVR-NE-003' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'diamond','stone_origin'=>'natural','carat'=>'0.10','stone_shape'=>'round','stone_quality'=>'G/SI','style'=>'pendant'],
            'NVR-EA-004' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'no-stones','stone_origin'=>'not-applicable','carat'=>'0','stone_shape'=>'not-applicable','style'=>'hoops'],
            'NVR-BR-005' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'diamond','stone_origin'=>'natural','carat'=>'0.15','stone_shape'=>'round','stone_quality'=>'F/VS2','style'=>'chain-bracelet'],
            'NVR-RI-006' => ['metal'=>'yellow-gold','fineness'=>'750','gemstone'=>'no-stones','stone_origin'=>'not-applicable','carat'=>'0','stone_shape'=>'not-applicable','style'=>'signet'],
            'NVR-WE-007' => ['metal'=>'rose-gold','fineness'=>'750','gemstone'=>'diamond','stone_origin'=>'natural','carat'=>'0.20','stone_shape'=>'round','stone_quality'=>'G/VS2','style'=>'eternity'],
            'NVR-EA-008' => ['metal'=>'white-gold','fineness'=>'750','gemstone'=>'diamond','stone_origin'=>'lab-grown','carat'=>'0.30','stone_shape'=>'round','stone_quality'=>'LAB','style'=>'studs'],
            'NVR-NE-009' => ['metal'=>'rose-gold','fineness'=>'750','gemstone'=>'diamond','stone_origin'=>'lab-grown','carat'=>'0.25','stone_shape'=>'round','stone_quality'=>'LAB','style'=>'pendant'],
            'NVR-RI-010' => ['metal'=>'white-gold','fineness'=>'750','gemstone'=>'diamond','stone_origin'=>'natural','carat'=>'0.35','stone_shape'=>'round','stone_quality'=>'D/VVS2','style'=>'eternity']
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
                    $text = in_array($key, ['fineness', 'carat', 'stone_count'], true) ? $value : ($translations[$value][$index] ?? $value);
                    $this->model_catalog_product->addAttribute($product_id, $attribute_id, $language_ids[$code], ['text' => $text]);
                }
            }
        }

        $ring_option = $this->db->query("SELECT `option_id` FROM `" . DB_PREFIX . "option_description` WHERE LOWER(`name`) IN ('ring size','ringgröße','velikost prstenu','размер кольца','розмір каблучки') ORDER BY `option_id` DESC LIMIT 1");
        $attribute_map = ['group' => $attribute_group_id] + $attribute_ids;
        $settings = ['module_noveraile_attribute_map' => json_encode($attribute_map)];
        if ($ring_option->num_rows) {
            $settings['module_noveraile_ring_size_option_id'] = (string)(int)$ring_option->row['option_id'];
        }
        $this->installDefaultSettings('module_noveraile', $settings);
        $this->model_setting_setting->editValue('module_noveraile', 'module_noveraile_attribute_map', json_encode($attribute_map));
    }

    private function seedArticles(): void {
        $version = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `key` = 'module_noveraile_article_version' LIMIT 1");
        if ($version->num_rows && (int)$version->row['value'] >= 2) return;
        $this->load->model('localisation/language');
        $language_ids = [];
        foreach (['en-gb','de-de','cs-cz','ru-ru','uk-ua'] as $code) {
            $language = $this->model_localisation_language->getLanguageByCode($code);
            if ($language) $language_ids[$code] = (int)$language['language_id'];
        }
        if (!$language_ids) return;
        $this->load->model('cms/article');
        $seeded = $this->db->query("SELECT `article_id` FROM `" . DB_PREFIX . "article` WHERE `author` IN ('NOVERAILE', '6 Moments')");
        foreach ($seeded->rows as $row) $this->model_cms_article->deleteArticle((int)$row['article_id']);

        $configured_brand = trim((string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name')));
        $brand = in_array($configured_brand, ['', 'Your Store'], true) ? '6 Moments' : $configured_brand;
        $articles = [
            ['image'=>'editorial/journal-ring-architecture.webp','name'=>['en-gb'=>'The architecture of a forever ring','de-de'=>'Die Architektur eines Rings für immer','cs-cz'=>'Architektura prstenu navždy','ru-ru'=>'Архитектура кольца навсегда','uk-ua'=>'Архітектура каблучки назавжди'],'copy'=>['en-gb'=>'Proportion, comfort and the quiet details that allow a ring to become part of everyday life.','de-de'=>'Proportion, Komfort und leise Details lassen einen Ring zum selbstverständlichen Teil des Alltags werden.','cs-cz'=>'Proporce, pohodlí a jemné detaily dovolují prstenu stát se přirozenou součástí každého dne.','ru-ru'=>'Пропорции, комфорт и тихие детали помогают кольцу стать естественной частью каждого дня.','uk-ua'=>'Пропорції, комфорт і тихі деталі допомагають каблучці стати природною частиною кожного дня.']],
            ['image'=>'editorial/journal-heirlooms.webp','name'=>['en-gb'=>'How modern heirlooms gather meaning','de-de'=>'Wie moderne Erbstücke Bedeutung sammeln','cs-cz'=>'Jak moderní šperky získávají význam','ru-ru'=>'Как современные реликвии обретают смысл','uk-ua'=>'Як сучасні реліквії набувають сенсу'],'copy'=>['en-gb'=>'A jewel becomes an heirloom through the life lived around it — through touch, memory and the stories passed forward.','de-de'=>'Ein Schmuckstück wird durch Berührung, Erinnerung und weitergegebene Geschichten zum Erbstück.','cs-cz'=>'Šperk se stává dědictvím díky dotekům, vzpomínkám a příběhům předávaným dál.','ru-ru'=>'Украшение становится реликвией благодаря прикосновениям, памяти и историям, которые передают дальше.','uk-ua'=>'Прикраса стає реліквією завдяки дотикам, пам’яті та історіям, які передають далі.']],
            ['image'=>'editorial/journal-patina.webp','name'=>['en-gb'=>'Why gold changes beautifully over time','de-de'=>'Warum Gold mit der Zeit schöner wird','cs-cz'=>'Proč zlato časem krásní','ru-ru'=>'Почему золото красиво меняется со временем','uk-ua'=>'Чому золото з часом стає красивішим'],'copy'=>['en-gb'=>'Fine marks and a softening polish are not flaws. They are the visible record of a piece that has stayed close.','de-de'=>'Feine Spuren und ein weicher werdender Glanz sind keine Fehler, sondern sichtbare Erinnerungen an ein getragenes Leben.','cs-cz'=>'Jemné stopy a měkčí lesk nejsou vadou, ale viditelným záznamem života nošeného šperku.','ru-ru'=>'Тонкие следы и мягкий блеск — не недостатки, а видимая запись жизни украшения рядом с вами.','uk-ua'=>'Тонкі сліди й м’який блиск — не недоліки, а видимий запис життя прикраси поруч із вами.']]
        ];
        foreach ($articles as $index => $article) {
            $descriptions=[];$seo=[];
            foreach ($language_ids as $code=>$language_id) {
                $name=$article['name'][$code]??$article['name']['en-gb'];$copy=$article['copy'][$code]??$article['copy']['en-gb'];
                $descriptions[$language_id]=['image'=>'catalog/noveraile/'.$article['image'],'name'=>$name,'description'=>'<p>'.$copy.'</p>','tag'=>'jewelry,craftsmanship,legacy','meta_title'=>$name.' | '.$brand.' Journal','meta_description'=>$copy,'meta_keyword'=>''];$seo[$language_id]='noveraile-journal-'.($index+1).'-'.$code;
            }
            $this->model_cms_article->addArticle(['topic_id'=>0,'author'=>$brand,'status'=>1,'article_description'=>$descriptions,'article_store'=>[0],'article_seo_url'=>[0=>$seo]]);
        }
        $this->model_setting_setting->editValue('module_noveraile', 'module_noveraile_article_version', '2');
    }

    public function getCatalogSummary(): array {
        $products = $this->db->query("SELECT COUNT(*) AS `total`, SUM(CASE WHEN `status` = '1' THEN 1 ELSE 0 END) AS `active` FROM `" . DB_PREFIX . "product`");
        $languages = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "language` WHERE `status` = '1'");

        return [
            'total' => (int)($products->row['total'] ?? 0),
            'active' => (int)($products->row['active'] ?? 0),
            'languages' => (int)($languages->row['total'] ?? 0)
        ];
    }

    public function exportProducts(): array {
        $sku = $this->usesProductCodeTable()
            ? "(SELECT `pc`.`value` FROM `" . DB_PREFIX . "product_code` `pc` WHERE `pc`.`product_id` = `p`.`product_id` AND `pc`.`code` = 'sku' LIMIT 1)"
            : "`p`.`sku`";
        $query = $this->db->query("SELECT `p`.`product_id`, `p`.`model`, `p`.`price`, `p`.`quantity`, `p`.`status`, `p`.`image`, `p`.`weight`, `p`.`sort_order`, `p`.`date_available`, `l`.`language_id`, `l`.`code` AS `language_code`, `pd`.`name`, `pd`.`description`, `pd`.`meta_title`, `pd`.`meta_description`, `pd`.`meta_keyword`, `pd`.`tag`, " . $sku . " AS `sku`, (SELECT GROUP_CONCAT(`ptc`.`category_id` ORDER BY `ptc`.`category_id` SEPARATOR '|') FROM `" . DB_PREFIX . "product_to_category` `ptc` WHERE `ptc`.`product_id` = `p`.`product_id`) AS `category_ids`, (SELECT GROUP_CONCAT(`pi`.`image` ORDER BY `pi`.`sort_order`, `pi`.`product_image_id` SEPARATOR '|') FROM `" . DB_PREFIX . "product_image` `pi` WHERE `pi`.`product_id` = `p`.`product_id`) AS `additional_images` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `pd`.`language_id`) ORDER BY `p`.`product_id`, `l`.`sort_order`, `l`.`name`");

        $attribute_map = array_filter($this->attributeMap(), static fn($id): bool => (int)$id > 0);
        $attribute_keys = array_flip(array_map('intval', $attribute_map));
        $attributes = [];
        if ($attribute_keys) {
            $attribute_query = $this->db->query("SELECT `product_id`, `language_id`, `attribute_id`, `text` FROM `" . DB_PREFIX . "product_attribute` WHERE `attribute_id` IN (" . implode(',', array_keys($attribute_keys)) . ")");
            foreach ($attribute_query->rows as $attribute) {
                $key = $attribute_keys[(int)$attribute['attribute_id']] ?? '';
                if ($key !== '') $attributes[(int)$attribute['product_id']][(int)$attribute['language_id']][$key] = $attribute['text'];
            }
        }
        $rows = $query->rows;
        foreach ($rows as &$row) {
            foreach (array_keys($attribute_map) as $key) {
                $row[$key] = $attributes[(int)$row['product_id']][(int)$row['language_id']][$key] ?? '';
            }
            unset($row['language_id']);
        }
        unset($row);

        return $rows;
    }

    public function importProducts(array $rows, bool $update_existing): array {
        if (!$rows) {
            throw new \RuntimeException('The CSV file does not contain any product rows.');
        }

        $language_rows = $this->db->query("SELECT `language_id`, `code` FROM `" . DB_PREFIX . "language`")->rows;
        $languages = [];
        foreach ($language_rows as $language) {
            $languages[strtolower((string)$language['code'])] = (int)$language['language_id'];
        }

        $groups = [];
        foreach ($rows as $row) {
            $line = (int)($row['_line'] ?? 0);
            $product_id = $this->positiveInteger($row['product_id'] ?? '', $line, 'product_id', true);
            $model = $this->catalogText($row['model'] ?? '', $line, 'model', 64, true);
            $language_code = strtolower($this->catalogText($row['language_code'] ?? '', $line, 'language_code', 16, true));

            if (!isset($languages[$language_code])) {
                throw new \RuntimeException(sprintf('Row %d: language "%s" is not installed in OpenCart.', $line, $language_code));
            }

            $key = $product_id > 0 ? 'id:' . $product_id : 'model:' . strtolower($model);
            if (!isset($groups[$key])) {
                $groups[$key] = ['product_id' => $product_id, 'model' => $model, 'rows' => []];
            } elseif ($groups[$key]['model'] !== $model) {
                throw new \RuntimeException(sprintf('Row %d: product rows with the same ID must use the same model.', $line));
            }

            if (isset($groups[$key]['rows'][$language_code])) {
                throw new \RuntimeException(sprintf('Row %d: duplicate language "%s" for model "%s".', $line, $language_code, $model));
            }

            $row['language_id'] = $languages[$language_code];
            $groups[$key]['rows'][$language_code] = $row;
        }

        $prepared = [];
        foreach ($groups as $group) {
            $first = reset($group['rows']);
            $line = (int)$first['_line'];
            $product_id = (int)$group['product_id'];
            $model = (string)$group['model'];

            if ($product_id > 0) {
                $existing = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . $product_id . "' LIMIT 1");
                if (!$existing->num_rows) {
                    throw new \RuntimeException(sprintf('Row %d: product_id %d does not exist. Leave product_id blank to create a product.', $line, $product_id));
                }
            } else {
                $existing = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` = '" . $this->db->escape($model) . "' ORDER BY `product_id`");
                if ($existing->num_rows > 1) {
                    throw new \RuntimeException(sprintf('Row %d: model "%s" matches more than one existing product. Export the catalog and use product_id.', $line, $model));
                }
                $product_id = $existing->num_rows ? (int)$existing->row['product_id'] : 0;
            }

            if ($product_id > 0 && !$update_existing) {
                throw new \RuntimeException(sprintf('Row %d: model "%s" already exists. Enable updating existing products or remove it from the file.', $line, $model));
            }

            $model_owner = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` = '" . $this->db->escape($model) . "' AND `product_id` != '" . $product_id . "' LIMIT 1");
            if ($model_owner->num_rows) {
                throw new \RuntimeException(sprintf('Row %d: model "%s" is already used by another product.', $line, $model));
            }

            $category_ids = $this->categoryIds($first['category_ids'] ?? '', $line);
            if ($category_ids) {
                $valid = $this->db->query("SELECT `category_id` FROM `" . DB_PREFIX . "category` WHERE `category_id` IN (" . implode(',', $category_ids) . ")")->rows;
                if (count($valid) !== count($category_ids)) {
                    throw new \RuntimeException(sprintf('Row %d: one or more category_ids do not exist.', $line));
                }
            }

            $image = $this->catalogImagePath($first['image'] ?? '', $line, 'image');
            $additional_images = array_key_exists('additional_images', $first)
                ? $this->catalogImagePaths($first['additional_images'], $line, 'additional_images')
                : null;

            $descriptions = [];
            $attribute_map = $this->attributeMap();
            $attribute_keys = ['metal', 'fineness', 'stone_origin', 'gemstone', 'stone_shape', 'stone_quality', 'carat', 'stone_count', 'style'];
            $attributes = [];
            foreach ($group['rows'] as $row) {
                $row_line = (int)$row['_line'];
                $name = $this->catalogText($row['name'] ?? '', $row_line, 'name', 255, true);
                $descriptions[(int)$row['language_id']] = [
                    'name' => $name,
                    'description' => (string)($row['description'] ?? ''),
                    'tag' => $this->catalogText($row['tag'] ?? '', $row_line, 'tag', 255),
                    'meta_title' => $this->catalogText($row['meta_title'] ?? $name, $row_line, 'meta_title', 255),
                    'meta_description' => (string)($row['meta_description'] ?? ''),
                    'meta_keyword' => (string)($row['meta_keyword'] ?? '')
                ];
                foreach ($attribute_keys as $attribute_key) {
                    if (empty($attribute_map[$attribute_key]) || !array_key_exists($attribute_key, $row)) continue;
                    $attributes[(int)$row['language_id']][(int)$attribute_map[$attribute_key]] = trim((string)$row[$attribute_key]);
                }
            }

            $date_available = trim((string)($first['date_available'] ?? '')) ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_available)) {
                throw new \RuntimeException(sprintf('Row %d: date_available must use YYYY-MM-DD.', $line));
            }

            $prepared[] = [
                'product_id' => $product_id,
                'model' => $model,
                'sku' => $this->catalogText($first['sku'] ?? '', $line, 'sku', 64),
                'price' => $this->decimalNumber($first['price'] ?? '0', $line, 'price'),
                'quantity' => $this->nonNegativeInteger($first['quantity'] ?? '0', $line, 'quantity'),
                'status' => $this->catalogStatus($first['status'] ?? '1', $line),
                'category_ids' => $category_ids,
                'image' => $image,
                'additional_images' => $additional_images,
                'attributes' => $attributes,
                'weight' => $this->decimalNumber($first['weight'] ?? '0', $line, 'weight'),
                'sort_order' => $this->nonNegativeInteger($first['sort_order'] ?? '0', $line, 'sort_order'),
                'date_available' => $date_available,
                'descriptions' => $descriptions
            ];
        }

        $created = 0;
        $updated = 0;
        $translations = 0;
        $this->load->model('catalog/product');
        $this->db->query('START TRANSACTION');

        try {
            foreach ($prepared as $product) {
                if ($product['product_id']) {
                    $product_id = (int)$product['product_id'];
                    $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `model` = '" . $this->db->escape($product['model']) . "', `quantity` = '" . (int)$product['quantity'] . "', `price` = '" . (float)$product['price'] . "', `status` = '" . (int)$product['status'] . "', `image` = '" . $this->db->escape($product['image']) . "', `weight` = '" . (float)$product['weight'] . "', `sort_order` = '" . (int)$product['sort_order'] . "', `date_available` = '" . $this->db->escape($product['date_available']) . "', `date_modified` = NOW() WHERE `product_id` = '" . $product_id . "'");

                    foreach ($product['descriptions'] as $language_id => $description) {
                        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . $product_id . "' AND `language_id` = '" . (int)$language_id . "'");
                        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_description` SET `product_id` = '" . $product_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($description['name']) . "', `description` = '" . $this->db->escape($description['description']) . "', `tag` = '" . $this->db->escape($description['tag']) . "', `meta_title` = '" . $this->db->escape($description['meta_title']) . "', `meta_description` = '" . $this->db->escape($description['meta_description']) . "', `meta_keyword` = '" . $this->db->escape($description['meta_keyword']) . "'");
                        $translations++;
                    }

                    if ($this->usesProductCodeTable()) {
                        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_code` WHERE `product_id` = '" . $product_id . "' AND `code` = 'sku'");
                        if ($product['sku'] !== '') {
                            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_code` SET `product_id` = '" . $product_id . "', `code` = 'sku', `value` = '" . $this->db->escape($product['sku']) . "'");
                        }
                    } else {
                        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `sku` = '" . $this->db->escape($product['sku']) . "' WHERE `product_id` = '" . $product_id . "'");
                    }

                    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . $product_id . "'");
                    foreach ($product['category_ids'] as $category_id) {
                        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = '" . $product_id . "', `category_id` = '" . (int)$category_id . "'");
                    }
                    $this->replaceCatalogMediaAndAttributes($product_id, $product['additional_images'], $product['attributes']);
                    $updated++;
                } else {
                    $codes = $product['sku'] !== '' ? [['code' => 'sku', 'value' => $product['sku']]] : [];
                    $product_id = (int)$this->model_catalog_product->addProduct([
                        'master_id' => 0,
                        'model' => $product['model'],
                        'sku' => $product['sku'],
                        'upc' => '',
                        'ean' => '',
                        'jan' => '',
                        'isbn' => '',
                        'mpn' => '',
                        'location' => '',
                        'variant' => [],
                        'override' => [],
                        'quantity' => $product['quantity'],
                        'minimum' => 1,
                        'subtract' => 1,
                        'stock_status_id' => (int)$this->config->get('config_stock_status_id'),
                        'date_available' => $product['date_available'],
                        'manufacturer_id' => 0,
                        'shipping' => 1,
                        'price' => $product['price'],
                        'points' => 0,
                        'weight' => $product['weight'],
                        'weight_class_id' => (int)$this->config->get('config_weight_class_id'),
                        'length' => 0,
                        'width' => 0,
                        'height' => 0,
                        'length_class_id' => (int)$this->config->get('config_length_class_id'),
                        'status' => $product['status'],
                        'tax_class_id' => 0,
                        'sort_order' => $product['sort_order'],
                        'image' => $product['image'],
                        'product_description' => $product['descriptions'],
                        'product_code' => $codes,
                        'product_category' => $product['category_ids'],
                        'product_store' => [0]
                    ]);
                    $this->replaceCatalogMediaAndAttributes($product_id, $product['additional_images'], $product['attributes']);
                    $translations += count($product['descriptions']);
                    $created++;
                }
            }

            $this->db->query('COMMIT');
            $this->cache->delete('product');
        } catch (\Throwable $error) {
            $this->db->query('ROLLBACK');
            throw $error;
        }

        return ['created' => $created, 'updated' => $updated, 'translations' => $translations];
    }

    private function replaceCatalogMediaAndAttributes(int $product_id, ?array $images, array $attributes): void {
        if ($images !== null) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "product_image` WHERE `product_id` = '" . $product_id . "'");
            foreach ($images as $sort_order => $image) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "product_image` SET `product_id` = '" . $product_id . "', `image` = '" . $this->db->escape($image) . "', `sort_order` = '" . (int)$sort_order . "'");
            }
        }
        foreach ($attributes as $language_id => $language_attributes) {
            foreach ($language_attributes as $attribute_id => $text) {
                $this->db->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = '" . $product_id . "' AND `attribute_id` = '" . (int)$attribute_id . "' AND `language_id` = '" . (int)$language_id . "'");
                if ($text !== '') {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET `product_id` = '" . $product_id . "', `attribute_id` = '" . (int)$attribute_id . "', `language_id` = '" . (int)$language_id . "', `text` = '" . $this->db->escape($text) . "'");
                }
            }
        }
    }

    private function catalogImagePath(mixed $value, int $line, string $field): string {
        $image = str_replace('\\', '/', trim((string)$value));
        if (str_contains($image, '..') || str_starts_with($image, '/') || preg_match('#^[a-z]+://#i', $image)) {
            throw new \RuntimeException(sprintf('Row %d: %s must contain relative OpenCart image paths only.', $line, $field));
        }
        return $image;
    }

    private function catalogImagePaths(mixed $value, int $line, string $field): array {
        $paths = [];
        foreach (explode('|', trim((string)$value)) as $path) {
            if (trim($path) === '') continue;
            $paths[] = $this->catalogImagePath($path, $line, $field);
        }
        return array_values(array_unique($paths));
    }

    private function attributeMap(): array {
        $value = $this->config->get('module_noveraile_attribute_map');
        if (is_array($value)) return $value;
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function catalogText(mixed $value, int $line, string $field, int $maximum, bool $required = false): string {
        $value = trim((string)$value);
        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        if ($required && $value === '') {
            throw new \RuntimeException(sprintf('Row %d: %s is required.', $line, $field));
        }
        if ($length > $maximum) {
            throw new \RuntimeException(sprintf('Row %d: %s is longer than %d characters.', $line, $field, $maximum));
        }
        return $value;
    }

    private function positiveInteger(mixed $value, int $line, string $field, bool $allow_blank = false): int {
        $value = trim((string)$value);
        if ($allow_blank && $value === '') return 0;
        if (!ctype_digit($value) || (int)$value < 1) {
            throw new \RuntimeException(sprintf('Row %d: %s must be a positive integer.', $line, $field));
        }
        return (int)$value;
    }

    private function nonNegativeInteger(mixed $value, int $line, string $field): int {
        $value = trim((string)$value);
        if ($value === '' || !ctype_digit($value)) {
            throw new \RuntimeException(sprintf('Row %d: %s must be zero or a positive integer.', $line, $field));
        }
        return (int)$value;
    }

    private function decimalNumber(mixed $value, int $line, string $field): float {
        $value = trim((string)$value);
        if (str_contains($value, ',') && !str_contains($value, '.')) $value = str_replace(',', '.', $value);
        if ($value === '' || !is_numeric($value) || (float)$value < 0) {
            throw new \RuntimeException(sprintf('Row %d: %s must be zero or a positive number.', $line, $field));
        }
        return (float)$value;
    }

    private function catalogStatus(mixed $value, int $line): int {
        $value = strtolower(trim((string)$value));
        if (in_array($value, ['1', 'true', 'yes', 'on', 'enabled', 'active'], true)) return 1;
        if (in_array($value, ['0', 'false', 'no', 'off', 'disabled', 'inactive'], true)) return 0;
        throw new \RuntimeException(sprintf('Row %d: status must be 1 or 0.', $line));
    }

    private function categoryIds(mixed $value, int $line): array {
        $value = trim((string)$value);
        if ($value === '') return [];
        $ids = [];
        foreach (preg_split('/[|,]/', $value) as $part) {
            $part = trim($part);
            if (!ctype_digit($part) || (int)$part < 1) {
                throw new \RuntimeException(sprintf('Row %d: category_ids must contain numeric IDs separated by |.', $line));
            }
            $ids[] = (int)$part;
        }
        return array_values(array_unique($ids));
    }

    private function usesProductCodeTable(): bool {
        return !defined('VERSION') || version_compare(VERSION, '4.1.0.0', '>=');
    }

    public function uninstall(): void {
        $this->load->model('setting/event');
        foreach (['noveraile_header','noveraile_footer','noveraile_home','noveraile_product','noveraile_product_thumb','noveraile_category','noveraile_search','noveraile_special','noveraile_cart','noveraile_cart_list','noveraile_checkout','noveraile_success_capture','noveraile_success','noveraile_account_login','noveraile_blog','noveraile_blog_info','noveraile_information','noveraile_contact'] as $code) {
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
