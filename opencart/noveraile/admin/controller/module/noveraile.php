<?php
namespace Opencart\Admin\Controller\Extension\Noveraile\Module;

class Noveraile extends \Opencart\System\Engine\Controller {
    private const VERSION = '2.6.0';
    private const CATALOG_HEADERS = [
        'product_id', 'model', 'sku', 'language_code', 'name', 'description', 'meta_title',
        'meta_description', 'meta_keyword', 'tag', 'price', 'quantity', 'status',
        'category_ids', 'image', 'additional_images', 'weight', 'sort_order', 'date_available',
        'metal', 'fineness', 'stone_origin', 'gemstone', 'stone_shape',
        'stone_quality', 'carat', 'stone_count', 'style'
    ];

    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [
            ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])],
            ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')],
            ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/noveraile/module/noveraile', 'user_token=' . $this->session->data['user_token'])]
        ];

        $data['save'] = $this->url->link('extension/noveraile/module/noveraile.save', 'user_token=' . $this->session->data['user_token']);
        $data['update'] = $this->url->link('extension/noveraile/module/noveraile.update', 'user_token=' . $this->session->data['user_token']);
        $data['catalog_import'] = $this->url->link('extension/noveraile/module/noveraile.importProducts', 'user_token=' . $this->session->data['user_token']);
        $data['catalog_export'] = $this->url->link('extension/noveraile/module/noveraile.exportProducts', 'user_token=' . $this->session->data['user_token']);
        $data['catalog_template'] = $this->url->link('extension/noveraile/module/noveraile.downloadCatalogTemplate', 'user_token=' . $this->session->data['user_token']);
        $data['install_demo'] = $this->url->link('extension/noveraile/module/noveraile.installDemo', 'user_token=' . $this->session->data['user_token']);
        $data['ai_generate'] = $this->url->link('extension/noveraile/module/noveraile.aiGenerate', 'user_token=' . $this->session->data['user_token']);
        $data['ai_apply'] = $this->url->link('extension/noveraile/module/noveraile.aiApply', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
        $data['demo_installed'] = (int)$this->config->get('module_noveraile_catalog_version') >= 6;
        $data['noveraile_version'] = self::VERSION;
        $data['opencart_version'] = defined('VERSION') ? VERSION : '4.x';
        $this->load->model('extension/noveraile/module/noveraile');
        $data['catalog_summary'] = $this->model_extension_noveraile_module_noveraile->getCatalogSummary();
        $data['success'] = (string)($this->session->data['success'] ?? '');
        $data['error_warning'] = (string)($this->session->data['error_warning'] ?? '');
        unset($this->session->data['success'], $this->session->data['error_warning']);

        $keys = [
            'module_noveraile_status', 'module_noveraile_brand_name', 'module_noveraile_instagram', 'module_noveraile_email',
            'module_noveraile_phone', 'module_noveraile_whatsapp', 'module_noveraile_telegram', 'module_noveraile_facebook',
            'module_noveraile_legal_name', 'module_noveraile_legal_form',
            'module_noveraile_legal_representative', 'module_noveraile_legal_address',
            'module_noveraile_legal_register', 'module_noveraile_vat_id',
            'module_noveraile_supervisory_authority', 'module_noveraile_content_responsible',
            'module_noveraile_privacy_email', 'module_noveraile_data_authority',
            'module_noveraile_retention_periods', 'module_noveraile_catalog_category_id',
            'module_noveraile_lab_category_id', 'module_noveraile_quiz_rules', 'module_noveraile_price_book',
            'module_noveraile_price_multiplier',
            'module_noveraile_page_builder', 'module_noveraile_hero_kicker', 'module_noveraile_hero_title',
            'module_noveraile_hero_cta', 'module_noveraile_mega_menu_status', 'module_noveraile_mega_menu_title',
            'module_noveraile_mega_menu_promo_text', 'module_noveraile_mega_menu_promo_url',
            'module_noveraile_ajax_filter_status', 'module_noveraile_one_page_checkout_status',
            'module_noveraile_ai_endpoint', 'module_noveraile_ai_model', 'module_noveraile_ai_tone',
            'module_noveraile_blog_route',
            'module_noveraile_native_menu_status',
            'payment_stripe_secret_key', 'payment_stripe_webhook_secret',
            'payment_stripe_status',
            'shipping_dhl_status', 'shipping_dhl_eu_cost', 'shipping_dhl_world_cost',
            'shipping_dpd_status', 'shipping_dpd_ukraine_cost', 'shipping_dpd_eu_cost'
        ];

        foreach ($keys as $key) {
            $data[$key] = $this->config->get($key);
        }
        foreach ([
            'shipping_dhl_eu_cost' => 25,
            'shipping_dhl_world_cost' => 25,
            'shipping_dpd_ukraine_cost' => 15,
            'shipping_dpd_eu_cost' => 15
        ] as $key => $default) {
            if ($data[$key] === null || $data[$key] === '') {
                $data[$key] = $default;
            }
        }
        $data['module_noveraile_blog_route'] = $data['module_noveraile_blog_route'] ?: 'cms/blog';
        if (!is_numeric($data['module_noveraile_price_multiplier']) || (float)$data['module_noveraile_price_multiplier'] <= 0) {
            $data['module_noveraile_price_multiplier'] = '1';
        }

        $data['module_noveraile_ai_api_key'] = '';
        $data['ai_key_configured'] = (bool)$this->config->get('module_noveraile_ai_api_key');
        $data['payment_stripe_secret_key'] = '';
        $data['payment_stripe_webhook_secret'] = '';
        $data['stripe_key_configured'] = (bool)$this->config->get('payment_stripe_secret_key');
        $data['stripe_webhook_configured'] = (bool)$this->config->get('payment_stripe_webhook_secret');

        $data['stripe_webhook_url'] = HTTP_CATALOG . 'index.php?route=extension/noveraile/payment/stripe.webhook';
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/noveraile/module/noveraile', $data));
    }

    public function save(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/noveraile/module/noveraile')) {
            $json['error'] = $this->language->get('error_permission');
        }

        // OpenCart sanitizes request strings with htmlspecialchars(), which turns
        // JSON quotes into &quot;. Decode that transport escaping before parsing and
        // store normalized JSON instead of persisting the escaped representation.
        $rules_json = htmlspecialchars_decode((string)($this->request->post['module_noveraile_quiz_rules'] ?? '[]'), ENT_COMPAT);
        $rules = json_decode($rules_json, true);
        if (!is_array($rules)) {
            $json['error'] = $this->language->get('error_json');
        } else {
            $this->request->post['module_noveraile_quiz_rules'] = json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $price_book_json = htmlspecialchars_decode((string)($this->request->post['module_noveraile_price_book'] ?? '{}'), ENT_COMPAT);
        $price_book = json_decode($price_book_json, true);
        if (!is_array($price_book)) {
            $json['error'] = $this->language->get('error_json');
        } else {
            $this->request->post['module_noveraile_price_book'] = json_encode($price_book, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $builder_json = htmlspecialchars_decode((string)($this->request->post['module_noveraile_page_builder'] ?? '[]'), ENT_COMPAT);
        $builder = json_decode($builder_json, true);
        if (!is_array($builder)) {
            $json['error'] = $this->language->get('error_builder_json');
        } else {
            $this->request->post['module_noveraile_page_builder'] = json_encode($builder, JSON_UNESCAPED_SLASHES);
        }

        $blog_route = trim((string)($this->request->post['module_noveraile_blog_route'] ?? 'cms/blog'));
        if (!preg_match('#^[a-z0-9_]+(?:/[a-z0-9_]+)+(?:\.[a-zA-Z0-9_]+)?$#', $blog_route)) {
            $json['error'] = $this->language->get('error_blog_route');
        }

        foreach (['shipping_dhl_eu_cost', 'shipping_dhl_world_cost', 'shipping_dpd_ukraine_cost', 'shipping_dpd_eu_cost'] as $rate_key) {
            $rate = $this->request->post[$rate_key] ?? null;
            if (!is_numeric($rate) || (float)$rate < 0 || (float)$rate > 10000) {
                $json['error'] = $this->language->get('error_shipping_rate');
                break;
            }
        }
        $price_multiplier = $this->request->post['module_noveraile_price_multiplier'] ?? null;
        if (!is_numeric($price_multiplier) || (float)$price_multiplier < 0.01 || (float)$price_multiplier > 100) {
            $json['error'] = $this->language->get('error_price_multiplier');
        } else {
            $this->request->post['module_noveraile_price_multiplier'] = rtrim(rtrim(number_format((float)$price_multiplier, 4, '.', ''), '0'), '.');
        }
        if (!empty($this->request->post['payment_stripe_status'])) {
            $stripe_secret = trim((string)($this->request->post['payment_stripe_secret_key'] ?? ''));
            $stripe_webhook = trim((string)($this->request->post['payment_stripe_webhook_secret'] ?? ''));
            if ($stripe_secret === '') {
                $stripe_secret = trim((string)$this->config->get('payment_stripe_secret_key'));
            }
            if ($stripe_webhook === '') {
                $stripe_webhook = trim((string)$this->config->get('payment_stripe_webhook_secret'));
            }
            if (!preg_match('/^sk_(?:test|live)_[A-Za-z0-9]+$/', $stripe_secret) || !preg_match('/^whsec_[A-Za-z0-9]+$/', $stripe_webhook)) {
                $json['error'] = $this->language->get('error_stripe_config');
            }
        }

        if (!$json) {
            $this->load->model('setting/setting');
            $groups = [
                'module_noveraile' => 'module_noveraile_',
                'payment_stripe' => 'payment_stripe_',
                'shipping_dhl' => 'shipping_dhl_',
                'shipping_dpd' => 'shipping_dpd_'
            ];
            foreach ($groups as $setting_code => $prefix) {
                $current = $this->model_setting_setting->getSetting($setting_code);
                $submitted = array_filter($this->request->post, static fn($key) => str_starts_with((string)$key, $prefix), ARRAY_FILTER_USE_KEY);
                if ($setting_code === 'module_noveraile' && empty($submitted['module_noveraile_ai_api_key'])) {
                    unset($submitted['module_noveraile_ai_api_key']);
                }
                if ($setting_code === 'payment_stripe') {
                    foreach (['payment_stripe_secret_key', 'payment_stripe_webhook_secret'] as $secret_key) {
                        if (empty($submitted[$secret_key])) {
                            unset($submitted[$secret_key]);
                        }
                    }
                }
                $this->model_setting_setting->editSetting($setting_code, array_merge($current, $submitted));
            }
            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void {
        if (defined('VERSION') && (version_compare(VERSION, '4.0.2.3', '<') || version_compare(VERSION, '4.2.0.0', '>='))) {
            throw new \RuntimeException('NOVERAILE supports OpenCart 4.0.2.3 through 4.1.x. Installed version: ' . VERSION);
        }

        $this->load->model('extension/noveraile/module/noveraile');
        $this->model_extension_noveraile_module_noveraile->install();
    }

    public function update(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $redirect = $this->url->link('extension/noveraile/module/noveraile', 'user_token=' . $this->session->data['user_token']);

        try {
            if (!$this->user->hasPermission('modify', 'extension/noveraile/module/noveraile')) {
                throw new \RuntimeException($this->language->get('error_permission'));
            }

            $upload = $this->request->files['file'] ?? [];
            if (!$upload || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)($upload['tmp_name'] ?? ''))) {
                throw new \RuntimeException($this->language->get('error_update_upload'));
            }

            $filename = basename((string)($upload['name'] ?? ''));
            if (!str_ends_with(strtolower($filename), '.ocmod.zip')) {
                throw new \RuntimeException($this->language->get('error_update_file'));
            }

            $zip = new \ZipArchive();
            if ($zip->open((string)$upload['tmp_name'], \ZipArchive::RDONLY) !== true) {
                throw new \RuntimeException($this->language->get('error_update_zip'));
            }

            try {
                $manifest_json = $zip->getFromName('install.json');
                $manifest = is_string($manifest_json) ? json_decode($manifest_json, true) : null;
                $next_version = trim((string)($manifest['version'] ?? ''));

                if (!is_array($manifest) || ($manifest['author'] ?? '') !== 'NOVERAILE' || !preg_match('/^\d+\.\d+\.\d+$/', $next_version)) {
                    throw new \RuntimeException($this->language->get('error_update_manifest'));
                }

                if (version_compare($next_version, self::VERSION, '<=')) {
                    throw new \RuntimeException(sprintf($this->language->get('error_update_version'), self::VERSION, $next_version));
                }

                $entries = $this->validateUpdateArchive($zip);
                $this->deployUpdate($zip, $entries, $next_version);
            } finally {
                $zip->close();
            }

            $this->session->data['success'] = sprintf($this->language->get('text_update_success'), $next_version);
        } catch (\Throwable $error) {
            $this->session->data['error_warning'] = $error->getMessage();
        }

        $this->response->redirect($redirect);
    }

    public function installDemo(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/noveraile/module/noveraile')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('extension/noveraile/module/noveraile');
            $this->model_extension_noveraile_module_noveraile->installDemo();
            $json['success'] = $this->language->get('text_demo_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function exportProducts(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        if (!$this->user->hasPermission('access', 'extension/noveraile/module/noveraile')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
            return;
        }

        $this->load->model('extension/noveraile/module/noveraile');
        $this->sendCatalogCsv('noveraile-products-' . date('Y-m-d') . '.csv', $this->model_extension_noveraile_module_noveraile->exportProducts());
    }

    public function downloadCatalogTemplate(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        if (!$this->user->hasPermission('access', 'extension/noveraile/module/noveraile')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
            return;
        }

        $this->sendCatalogCsv('noveraile-products-template.csv', [[
            'product_id' => '',
            'model' => 'EXAMPLE-001',
            'sku' => 'EXAMPLE-001',
            'language_code' => (string)($this->config->get('config_language') ?: 'en-gb'),
            'name' => 'Example product',
            'description' => '<p>Product description</p>',
            'meta_title' => 'Example product',
            'meta_description' => 'Short description for search results',
            'meta_keyword' => '',
            'tag' => 'example,new',
            'price' => '149.00',
            'quantity' => '10',
            'status' => '1',
            'category_ids' => '',
            'image' => 'catalog/products/example.jpg',
            'additional_images' => 'catalog/products/example-side.jpg|catalog/products/example-detail.jpg',
            'weight' => '0',
            'sort_order' => '0',
            'date_available' => date('Y-m-d'),
            'metal' => 'Yellow gold',
            'fineness' => '750',
            'stone_origin' => 'Natural',
            'gemstone' => 'Diamond',
            'stone_shape' => 'Round',
            'stone_quality' => 'G/VS2',
            'carat' => '0.50',
            'stone_count' => '1',
            'style' => 'Classic'
        ]]);
    }

    public function importProducts(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $redirect = $this->url->link('extension/noveraile/module/noveraile', 'user_token=' . $this->session->data['user_token']) . '#tab-catalog';

        try {
            if (!$this->user->hasPermission('modify', 'extension/noveraile/module/noveraile')) {
                throw new \RuntimeException($this->language->get('error_permission'));
            }

            $upload = $this->request->files['catalog_file'] ?? [];
            $path = (string)($upload['tmp_name'] ?? '');
            $name = strtolower(basename((string)($upload['name'] ?? '')));
            if (!$upload || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($path)) {
                throw new \RuntimeException($this->language->get('error_catalog_upload'));
            }
            if (!str_ends_with($name, '.csv')) {
                throw new \RuntimeException($this->language->get('error_catalog_file'));
            }
            if ((int)($upload['size'] ?? 0) > 8 * 1024 * 1024) {
                throw new \RuntimeException($this->language->get('error_catalog_size'));
            }

            $rows = $this->readCatalogCsv($path);
            $this->load->model('extension/noveraile/module/noveraile');
            $result = $this->model_extension_noveraile_module_noveraile->importProducts($rows, !empty($this->request->post['update_existing']));
            $this->session->data['success'] = sprintf(
                $this->language->get('text_catalog_import_success'),
                (int)$result['created'],
                (int)$result['updated'],
                (int)$result['translations']
            );
        } catch (\Throwable $error) {
            $this->session->data['error_warning'] = $error->getMessage();
        }

        $this->response->redirect($redirect);
    }

    private function readCatalogCsv(string $path): array {
        $sample = file_get_contents($path, false, null, 0, 65536);
        if (!is_string($sample) || $sample === '') {
            throw new \RuntimeException($this->language->get('error_catalog_empty'));
        }

        $first_line = strtok($sample, "\r\n") ?: '';
        $delimiters = [',' => substr_count($first_line, ','), ';' => substr_count($first_line, ';'), "\t" => substr_count($first_line, "\t")];
        arsort($delimiters);
        $delimiter = (string)array_key_first($delimiters);

        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new \RuntimeException($this->language->get('error_catalog_upload'));
        }

        try {
            $header = fgetcsv($handle, 0, $delimiter, '"', '');
            if (!is_array($header)) {
                throw new \RuntimeException($this->language->get('error_catalog_header'));
            }
            $header = array_map(static function ($value): string {
                $value = preg_replace('/^\xEF\xBB\xBF/', '', trim((string)$value));
                return strtolower((string)$value);
            }, $header);
            if (count($header) !== count(array_unique($header)) || array_diff(['model', 'language_code', 'name'], $header)) {
                throw new \RuntimeException($this->language->get('error_catalog_header'));
            }

            $rows = [];
            $line = 1;
            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if (count(array_filter($values, static fn($value): bool => trim((string)$value) !== '')) === 0) continue;
                if (count($values) > count($header)) {
                    throw new \RuntimeException(sprintf($this->language->get('error_catalog_columns'), $line));
                }
                $values = array_pad($values, count($header), '');
                $row = array_combine($header, $values);
                foreach ($row as $key => $value) {
                    $value = (string)$value;
                    if (strlen($value) > 1 && $value[0] === "'" && str_contains('=+-@', $value[1])) {
                        $value = substr($value, 1);
                    }
                    $row[$key] = $value;
                }
                $row['_line'] = $line;
                $rows[] = $row;
                if (count($rows) > 10000) {
                    throw new \RuntimeException($this->language->get('error_catalog_rows'));
                }
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    private function sendCatalogCsv(string $filename, array $rows): void {
        $handle = fopen('php://temp', 'w+b');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, self::CATALOG_HEADERS, ',', '"', '');
        foreach ($rows as $row) {
            $values = [];
            foreach (self::CATALOG_HEADERS as $header) {
                $value = (string)($row[$header] ?? '');
                if ($value !== '' && str_contains('=+-@', $value[0])) $value = "'" . $value;
                $values[] = $value;
            }
            fputcsv($handle, $values, ',', '"', '');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $this->response->addHeader('Content-Type: text/csv; charset=UTF-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        $this->response->setOutput($csv);
    }

    private function validateUpdateArchive(\ZipArchive $zip): array {
        $entries = [];
        $required = [
            'install.json',
            'admin/controller/module/noveraile.php',
            'admin/view/template/module/noveraile.twig',
            'catalog/controller/event/theme.php'
        ];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $source = (string)$zip->getNameIndex($index);
            $path = str_replace('\\', '/', $source);

            if ($path === '' || str_contains($path, "\0") || str_starts_with($path, '/') || preg_match('#^[a-zA-Z]:/#', $path)) {
                throw new \RuntimeException($this->language->get('error_update_paths'));
            }

            $parts = array_values(array_filter(explode('/', rtrim($path, '/')), static fn(string $part): bool => $part !== ''));
            if (in_array('..', $parts, true) || in_array('.', $parts, true)) {
                throw new \RuntimeException($this->language->get('error_update_paths'));
            }

            $is_directory = str_ends_with($path, '/');
            $is_root_file = !$is_directory && !str_contains($path, '/');
            $is_extension_file = str_starts_with($path, 'admin/') || str_starts_with($path, 'catalog/') || str_starts_with($path, 'system/');
            $is_image = str_starts_with($path, 'image/catalog/noveraile/');

            if (!$is_directory && !$is_root_file && !$is_extension_file && !$is_image) {
                throw new \RuntimeException($this->language->get('error_update_paths'));
            }

            if (!$is_directory) {
                $entries[$path] = ['index' => $index, 'image' => $is_image];
            }
        }

        foreach ($required as $path) {
            if (!isset($entries[$path])) {
                throw new \RuntimeException(sprintf($this->language->get('error_update_required'), $path));
            }
        }

        return $entries;
    }

    private function deployUpdate(\ZipArchive $zip, array $entries, string $next_version): void {
        $extension_root = rtrim(DIR_EXTENSION, '/\\') . '/noveraile';
        $image_root = rtrim(DIR_IMAGE, '/\\') . '/catalog/noveraile';
        $suffix = date('YmdHis') . '-' . bin2hex(random_bytes(4));
        $extension_stage = rtrim(DIR_EXTENSION, '/\\') . '/.noveraile-update-' . $suffix;
        $extension_backup = rtrim(DIR_EXTENSION, '/\\') . '/.noveraile-backup-' . $suffix;
        $image_stage = rtrim(DIR_IMAGE, '/\\') . '/catalog/.noveraile-update-' . $suffix;
        $image_backup = rtrim(DIR_IMAGE, '/\\') . '/catalog/.noveraile-backup-' . $suffix;
        $extension_backed_up = false;
        $extension_swapped = false;
        $image_backed_up = false;
        $image_swapped = false;
        $database_updated = false;

        try {
            $this->copyDirectory($extension_root, $extension_stage);
            if (is_dir($image_root)) {
                $this->copyDirectory($image_root, $image_stage);
            } elseif (!mkdir($image_stage, 0777, true) && !is_dir($image_stage)) {
                throw new \RuntimeException($this->language->get('error_update_write'));
            }

            foreach ($entries as $path => $entry) {
                $contents = $zip->getFromIndex((int)$entry['index']);
                if ($contents === false) {
                    throw new \RuntimeException($this->language->get('error_update_zip'));
                }

                if ($entry['image']) {
                    $relative = substr($path, strlen('image/catalog/noveraile/'));
                    $target = $image_stage . '/' . $relative;
                } else {
                    $target = $extension_stage . '/' . $path;
                }

                $directory = dirname($target);
                if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                    throw new \RuntimeException($this->language->get('error_update_write'));
                }
                if (file_put_contents($target, $contents, LOCK_EX) === false) {
                    throw new \RuntimeException($this->language->get('error_update_write'));
                }
            }

            if (!rename($extension_root, $extension_backup)) {
                throw new \RuntimeException($this->language->get('error_update_swap'));
            }
            $extension_backed_up = true;
            if (!rename($extension_stage, $extension_root)) {
                throw new \RuntimeException($this->language->get('error_update_swap'));
            }
            $extension_swapped = true;

            if (is_dir($image_root)) {
                if (!rename($image_root, $image_backup)) {
                    throw new \RuntimeException($this->language->get('error_update_swap'));
                }
                $image_backed_up = true;
            }
            if (!rename($image_stage, $image_root)) {
                throw new \RuntimeException($this->language->get('error_update_swap'));
            }
            $image_swapped = true;

            $backup_root = rtrim(DIR_STORAGE, '/\\') . '/backup/noveraile-' . self::VERSION . '-' . $suffix;
            $this->copyDirectory($extension_backup, $backup_root . '/extension');
            if (is_dir($image_backup)) {
                $this->copyDirectory($image_backup, $backup_root . '/image');
            }

            $this->db->query("UPDATE `" . DB_PREFIX . "extension_install` SET `version` = '" . $this->db->escape($next_version) . "', `status` = '1' WHERE `code` = 'noveraile'");
            $database_updated = true;
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            $this->removeDirectory($extension_backup);
            $this->removeDirectory($image_backup);
        } catch (\Throwable $error) {
            if ($database_updated) {
                $this->db->query("UPDATE `" . DB_PREFIX . "extension_install` SET `version` = '" . self::VERSION . "' WHERE `code` = 'noveraile'");
            }

            if ($image_swapped && is_dir($image_root)) {
                rename($image_root, $image_stage);
            }
            if ($image_backed_up && is_dir($image_backup)) {
                rename($image_backup, $image_root);
            }
            if ($extension_swapped && is_dir($extension_root)) {
                rename($extension_root, $extension_stage);
            }
            if ($extension_backed_up && is_dir($extension_backup)) {
                rename($extension_backup, $extension_root);
            }
            $this->removeDirectory($extension_stage);
            $this->removeDirectory($image_stage);
            throw $error;
        }
    }

    private function copyDirectory(string $source, string $destination): void {
        if (!is_dir($source)) {
            throw new \RuntimeException($this->language->get('error_update_source'));
        }
        if (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
            throw new \RuntimeException($this->language->get('error_update_write'));
        }

        foreach (new \FilesystemIterator($source, \FilesystemIterator::SKIP_DOTS) as $item) {
            $target = $destination . '/' . $item->getFilename();
            if ($item->isLink()) {
                throw new \RuntimeException($this->language->get('error_update_paths'));
            }
            if ($item->isDir()) {
                $this->copyDirectory($item->getPathname(), $target);
            } elseif (!copy($item->getPathname(), $target)) {
                throw new \RuntimeException($this->language->get('error_update_write'));
            }
        }
    }

    private function removeDirectory(string $directory): void {
        $resolved_parent = str_replace('\\', '/', dirname($directory)) . '/';
        $allowed = [str_replace('\\', '/', rtrim(DIR_EXTENSION, '/\\')) . '/', str_replace('\\', '/', rtrim(DIR_IMAGE, '/\\')) . '/catalog/'];

        if (!in_array($resolved_parent, $allowed, true) || !preg_match('/^\.noveraile-(?:update|backup)-[0-9]{14}-[a-f0-9]{8}$/', basename($directory))) {
            return;
        }
        if (!is_dir($directory)) {
            return;
        }

        $items = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->removeUpdateTree($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($directory);
    }

    private function removeUpdateTree(string $directory): void {
        foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->removeUpdateTree($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($directory);
    }

    public function aiGenerate(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $json = [];
        if (!$this->user->hasPermission('modify', 'extension/noveraile/module/noveraile')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $product_id = max(0, (int)($this->request->post['product_id'] ?? 0));
        $mode = ($this->request->post['mode'] ?? '') === 'seo' ? 'seo' : 'description';
        $language_id = max(1, (int)($this->request->post['language_id'] ?? $this->config->get('config_language_id')));
        $product = $this->getProductContext($product_id, $language_id);
        if (!$product) $json['error'] = $this->language->get('error_ai_product');

        $api_key = trim((string)$this->config->get('module_noveraile_ai_api_key'));
        if (!$api_key) $json['error'] = $this->language->get('error_ai_key');

        if (!$json) {
            try {
                $json['result'] = $this->requestAi($product, $mode);
                $json['product'] = ['product_id' => $product_id, 'name' => $product['name'], 'language_id' => $language_id];
            } catch (\Throwable $error) {
                $json['error'] = $error->getMessage();
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function aiApply(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $json = [];
        if (!$this->user->hasPermission('modify', 'extension/noveraile/module/noveraile')) {
            $json['error'] = $this->language->get('error_permission');
        }
        $product_id = max(0, (int)($this->request->post['product_id'] ?? 0));
        $language_id = max(1, (int)($this->request->post['language_id'] ?? $this->config->get('config_language_id')));
        if (!$this->getProductContext($product_id, $language_id)) $json['error'] = $this->language->get('error_ai_product');

        if (!$json) {
            $description = $this->cleanGeneratedHtml((string)($this->request->post['description'] ?? ''));
            $meta_title = trim(strip_tags((string)($this->request->post['meta_title'] ?? '')));
            $meta_description = trim(strip_tags((string)($this->request->post['meta_description'] ?? '')));
            $meta_keyword = trim(strip_tags((string)($this->request->post['meta_keyword'] ?? '')));
            $updates = [];
            if ($description !== '') $updates[] = "`description` = '" . $this->db->escape($description) . "'";
            if ($meta_title !== '') $updates[] = "`meta_title` = '" . $this->db->escape(oc_substr($meta_title, 0, 255)) . "'";
            if ($meta_description !== '') $updates[] = "`meta_description` = '" . $this->db->escape(oc_substr($meta_description, 0, 255)) . "'";
            if ($meta_keyword !== '') $updates[] = "`meta_keyword` = '" . $this->db->escape(oc_substr($meta_keyword, 0, 255)) . "'";
            if (!$updates) {
                $json['error'] = $this->language->get('error_ai_empty');
            } else {
                $this->db->query("UPDATE `" . DB_PREFIX . "product_description` SET " . implode(', ', $updates) . " WHERE `product_id` = '" . $product_id . "' AND `language_id` = '" . $language_id . "'");
                $json['success'] = $this->language->get('text_ai_applied');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getProductContext(int $product_id, int $language_id): array {
        if (!$product_id) return [];
        $query = $this->db->query("SELECT p.product_id, p.model, p.sku, p.price, p.quantity, pd.name, pd.description, pd.tag, pd.meta_title, pd.meta_description FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id) WHERE p.product_id = '" . $product_id . "' AND pd.language_id = '" . $language_id . "' LIMIT 1");
        return $query->row ?: [];
    }

    private function requestAi(array $product, string $mode): array {
        $endpoint = trim((string)$this->config->get('module_noveraile_ai_endpoint')) ?: 'https://api.openai.com/v1/responses';
        $this->assertPublicHttpsEndpoint($endpoint);
        $tone = trim((string)$this->config->get('module_noveraile_ai_tone')) ?: 'premium, clear and specific';
        $model = trim((string)$this->config->get('module_noveraile_ai_model')) ?: 'gpt-5-mini';
        $task = $mode === 'seo'
            ? 'Return only JSON with keys meta_title, meta_description, meta_keyword. Keep title under 60 characters and description under 155 characters.'
            : 'Return only JSON with key description containing useful semantic HTML using p, h2, ul and li. Write 180-300 words, avoid unsupported claims and never invent specifications.';
        $input = $task . "\nTone: " . $tone . "\nProduct data: " . json_encode($product, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $payload = json_encode(['model' => $model, 'input' => $input]);
        $curl = curl_init($endpoint);
        $curl_options = [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->config->get('module_noveraile_ai_api_key'), 'Content-Type: application/json'], CURLOPT_POSTFIELDS => $payload];
        if (defined('CURLPROTO_HTTPS')) $curl_options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        curl_setopt_array($curl, $curl_options);
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
        if ($body === false || $curl_error) throw new \RuntimeException($this->language->get('error_ai_network'));
        $response = json_decode((string)$body, true);
        if ($status < 200 || $status >= 300) throw new \RuntimeException((string)($response['error']['message'] ?? $this->language->get('error_ai_network')));
        $text = (string)($response['output_text'] ?? '');
        if ($text === '' && !empty($response['output'])) {
            foreach ($response['output'] as $output) foreach (($output['content'] ?? []) as $content) if (!empty($content['text'])) $text .= $content['text'];
        }
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)));
        $result = json_decode($text, true);
        if (!is_array($result)) throw new \RuntimeException($this->language->get('error_ai_response'));
        if (isset($result['description'])) $result['description'] = $this->cleanGeneratedHtml((string)$result['description']);
        return $result;
    }

    private function assertPublicHttpsEndpoint(string $endpoint): void {
        $parts = parse_url($endpoint);
        $host = strtolower(trim((string)($parts['host'] ?? ''), '[]'));
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || $host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
            throw new \RuntimeException($this->language->get('error_ai_endpoint'));
        }

        $addresses = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses[] = $host;
        } else {
            $addresses = array_merge($addresses, gethostbynamel($host) ?: []);
            foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
                if (!empty($record['ipv6'])) $addresses[] = $record['ipv6'];
            }
        }

        if (!$addresses) throw new \RuntimeException($this->language->get('error_ai_endpoint'));
        foreach (array_unique($addresses) as $address) {
            if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new \RuntimeException($this->language->get('error_ai_endpoint'));
            }
        }
    }

    private function cleanGeneratedHtml(string $html): string {
        $html = strip_tags($html, '<p><h2><h3><ul><ol><li><strong><em><br>');
        return trim((string)preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $html));
    }

    public function uninstall(): void {
        $this->load->model('extension/noveraile/module/noveraile');
        $this->model_extension_noveraile_module_noveraile->uninstall();
    }
}
