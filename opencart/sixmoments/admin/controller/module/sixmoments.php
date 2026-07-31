<?php
namespace Opencart\Admin\Controller\Extension\Sixmoments\Module;

class Sixmoments extends \Opencart\System\Engine\Controller {
    private const VERSION = '2.1.0';

    public function index(): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [
            ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])],
            ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')],
            ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/sixmoments/module/sixmoments', 'user_token=' . $this->session->data['user_token'])]
        ];

        $data['save'] = $this->url->link('extension/sixmoments/module/sixmoments.save', 'user_token=' . $this->session->data['user_token']);
        $data['install_demo'] = $this->url->link('extension/sixmoments/module/sixmoments.installDemo', 'user_token=' . $this->session->data['user_token']);
        $data['ai_generate'] = $this->url->link('extension/sixmoments/module/sixmoments.aiGenerate', 'user_token=' . $this->session->data['user_token']);
        $data['ai_apply'] = $this->url->link('extension/sixmoments/module/sixmoments.aiApply', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
        $data['demo_installed'] = (int)$this->config->get('module_sixmoments_catalog_version') >= 5;
        $data['sixmoments_version'] = self::VERSION;
        $data['opencart_version'] = defined('VERSION') ? VERSION : '4.x';
        $data['update_feed'] = 'https://katya-dev.duckdns.org/updates/sixmoments.json';

        $keys = [
            'module_sixmoments_status', 'module_sixmoments_brand_name', 'module_sixmoments_instagram', 'module_sixmoments_email',
            'module_sixmoments_phone', 'module_sixmoments_legal_name', 'module_sixmoments_legal_form',
            'module_sixmoments_legal_representative', 'module_sixmoments_legal_address',
            'module_sixmoments_legal_register', 'module_sixmoments_vat_id',
            'module_sixmoments_supervisory_authority', 'module_sixmoments_content_responsible',
            'module_sixmoments_privacy_email', 'module_sixmoments_data_authority',
            'module_sixmoments_retention_periods', 'module_sixmoments_catalog_category_id',
            'module_sixmoments_lab_category_id', 'module_sixmoments_quiz_rules',
            'module_sixmoments_page_builder', 'module_sixmoments_hero_kicker', 'module_sixmoments_hero_title',
            'module_sixmoments_hero_cta', 'module_sixmoments_mega_menu_status', 'module_sixmoments_mega_menu_title',
            'module_sixmoments_mega_menu_promo_text', 'module_sixmoments_mega_menu_promo_url',
            'module_sixmoments_ajax_filter_status', 'module_sixmoments_one_page_checkout_status',
            'module_sixmoments_ai_endpoint', 'module_sixmoments_ai_model', 'module_sixmoments_ai_tone',
            'module_sixmoments_color_mode', 'module_sixmoments_blog_route',
            'module_sixmoments_native_menu_status',
            'payment_stripe_secret_key', 'payment_stripe_webhook_secret',
            'payment_stripe_status', 'shipping_dhl_cost', 'shipping_dpd_cost'
        ];

        foreach ($keys as $key) {
            $data[$key] = $this->config->get($key);
        }
        $data['module_sixmoments_color_mode'] = $data['module_sixmoments_color_mode'] ?: 'auto';
        $data['module_sixmoments_blog_route'] = $data['module_sixmoments_blog_route'] ?: 'cms/blog';

        $data['module_sixmoments_ai_api_key'] = '';
        $data['ai_key_configured'] = (bool)$this->config->get('module_sixmoments_ai_api_key');

        $data['stripe_webhook_url'] = HTTP_CATALOG . 'index.php?route=extension/sixmoments/payment/stripe.webhook';
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/sixmoments/module/sixmoments', $data));
    }

    public function save(): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/sixmoments/module/sixmoments')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $rules = (string)($this->request->post['module_sixmoments_quiz_rules'] ?? '[]');
        json_decode($rules, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $json['error'] = $this->language->get('error_json');
        }

        $builder = json_decode((string)($this->request->post['module_sixmoments_page_builder'] ?? '[]'), true);
        if (!is_array($builder)) {
            $json['error'] = $this->language->get('error_builder_json');
        }

        $color_mode = (string)($this->request->post['module_sixmoments_color_mode'] ?? 'auto');
        if (!in_array($color_mode, ['auto', 'light', 'dark'], true)) {
            $json['error'] = $this->language->get('error_color_mode');
        }

        $blog_route = trim((string)($this->request->post['module_sixmoments_blog_route'] ?? 'cms/blog'));
        if (!preg_match('#^[a-z0-9_]+(?:/[a-z0-9_]+)+(?:\.[a-zA-Z0-9_]+)?$#', $blog_route)) {
            $json['error'] = $this->language->get('error_blog_route');
        }

        if (!$json) {
            $this->load->model('setting/setting');
            $groups = [
                'module_sixmoments' => 'module_sixmoments_',
                'payment_stripe' => 'payment_stripe_',
                'shipping_dhl' => 'shipping_dhl_',
                'shipping_dpd' => 'shipping_dpd_'
            ];
            foreach ($groups as $setting_code => $prefix) {
                $current = $this->model_setting_setting->getSetting($setting_code);
                $submitted = array_filter($this->request->post, static fn($key) => str_starts_with((string)$key, $prefix), ARRAY_FILTER_USE_KEY);
                if ($setting_code === 'module_sixmoments' && empty($submitted['module_sixmoments_ai_api_key'])) {
                    unset($submitted['module_sixmoments_ai_api_key']);
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
            throw new \RuntimeException('6MOMENTS supports OpenCart 4.0.2.3 through 4.1.x. Installed version: ' . VERSION);
        }

        $this->load->model('extension/sixmoments/module/sixmoments');
        $this->model_extension_sixmoments_module_sixmoments->install();
    }

    public function installDemo(): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/sixmoments/module/sixmoments')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('extension/sixmoments/module/sixmoments');
            $this->model_extension_sixmoments_module_sixmoments->installDemo();
            $json['success'] = $this->language->get('text_demo_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function aiGenerate(): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        $json = [];
        if (!$this->user->hasPermission('modify', 'extension/sixmoments/module/sixmoments')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $product_id = max(0, (int)($this->request->post['product_id'] ?? 0));
        $mode = ($this->request->post['mode'] ?? '') === 'seo' ? 'seo' : 'description';
        $language_id = max(1, (int)($this->request->post['language_id'] ?? $this->config->get('config_language_id')));
        $product = $this->getProductContext($product_id, $language_id);
        if (!$product) $json['error'] = $this->language->get('error_ai_product');

        $api_key = trim((string)$this->config->get('module_sixmoments_ai_api_key'));
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
        $this->load->language('extension/sixmoments/module/sixmoments');
        $json = [];
        if (!$this->user->hasPermission('modify', 'extension/sixmoments/module/sixmoments')) {
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
        $endpoint = trim((string)$this->config->get('module_sixmoments_ai_endpoint')) ?: 'https://api.openai.com/v1/responses';
        if (!str_starts_with($endpoint, 'https://')) throw new \RuntimeException($this->language->get('error_ai_endpoint'));
        $tone = trim((string)$this->config->get('module_sixmoments_ai_tone')) ?: 'premium, clear and specific';
        $model = trim((string)$this->config->get('module_sixmoments_ai_model')) ?: 'gpt-5-mini';
        $task = $mode === 'seo'
            ? 'Return only JSON with keys meta_title, meta_description, meta_keyword. Keep title under 60 characters and description under 155 characters.'
            : 'Return only JSON with key description containing useful semantic HTML using p, h2, ul and li. Write 180-300 words, avoid unsupported claims and never invent specifications.';
        $input = $task . "\nTone: " . $tone . "\nProduct data: " . json_encode($product, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $payload = json_encode(['model' => $model, 'input' => $input]);
        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->config->get('module_sixmoments_ai_api_key'), 'Content-Type: application/json'], CURLOPT_POSTFIELDS => $payload]);
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

    private function cleanGeneratedHtml(string $html): string {
        $html = strip_tags($html, '<p><h2><h3><ul><ol><li><strong><em><br>');
        return trim((string)preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $html));
    }

    public function uninstall(): void {
        $this->load->model('extension/sixmoments/module/sixmoments');
        $this->model_extension_sixmoments_module_sixmoments->uninstall();
    }
}
