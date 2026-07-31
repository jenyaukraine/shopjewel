<?php
namespace Opencart\Admin\Controller\Extension\Sixmoments\Module;

class Sixmoments extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [
            ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])],
            ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')],
            ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/sixmoments/module/sixmoments', 'user_token=' . $this->session->data['user_token'])]
        ];

        $data['save'] = $this->url->link('extension/sixmoments/module/sixmoments.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        $keys = [
            'module_sixmoments_status', 'module_sixmoments_instagram', 'module_sixmoments_email',
            'module_sixmoments_phone', 'module_sixmoments_legal_name', 'module_sixmoments_legal_form',
            'module_sixmoments_legal_representative', 'module_sixmoments_legal_address',
            'module_sixmoments_legal_register', 'module_sixmoments_vat_id',
            'module_sixmoments_supervisory_authority', 'module_sixmoments_content_responsible',
            'module_sixmoments_privacy_email', 'module_sixmoments_data_authority',
            'module_sixmoments_retention_periods', 'module_sixmoments_catalog_category_id',
            'module_sixmoments_lab_category_id', 'module_sixmoments_quiz_rules',
            'payment_stripe_secret_key', 'payment_stripe_webhook_secret',
            'payment_stripe_status', 'shipping_dhl_cost', 'shipping_dpd_cost'
        ];

        foreach ($keys as $key) {
            $data[$key] = $this->config->get($key);
        }

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

        if (!$json) {
            $this->load->model('setting/setting');
            foreach (['module_sixmoments', 'payment_stripe', 'shipping_dhl', 'shipping_dpd'] as $setting_code) {
                $current = $this->model_setting_setting->getSetting($setting_code);
                $this->model_setting_setting->editSetting($setting_code, array_merge($current, $this->request->post));
            }
            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void {
        if (defined('VERSION') && version_compare(VERSION, '4.1.0.0', '<')) {
            throw new \RuntimeException('6MOMENTS requires OpenCart 4.1.x. Installed version: ' . VERSION);
        }

        $this->load->model('extension/sixmoments/module/sixmoments');
        $this->model_extension_sixmoments_module_sixmoments->install();
    }

    public function uninstall(): void {
        $this->load->model('extension/sixmoments/module/sixmoments');
        $this->model_extension_sixmoments_module_sixmoments->uninstall();
    }
}
