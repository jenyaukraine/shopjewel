<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;
class Shipping extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile'); $brand=$this->brand(); $this->document->setTitle($this->language->get('six_shipping_title') . ' | ' . $brand);
        $data = $this->language->all(); $data['six_brand_name']=$brand; $data['header'] = $this->load->controller('common/header'); $data['footer'] = $this->load->controller('common/footer');
        $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
        foreach ([
            'dpd_ukraine_cost' => ['shipping_dpd_ukraine_cost', 15],
            'dpd_eu_cost' => ['shipping_dpd_eu_cost', 15],
            'dhl_eu_cost' => ['shipping_dhl_eu_cost', 25],
            'dhl_world_cost' => ['shipping_dhl_world_cost', 25]
        ] as $data_key => [$setting_key, $fallback]) {
            $configured = $this->config->get($setting_key);
            $data[$data_key] = $this->currency->format((float)($configured === null || $configured === '' ? $fallback : $configured), $currency);
        }
        $this->response->setOutput($this->load->view('extension/noveraile/page/shipping', $data));
    }

    private function brand(): string { $brand=trim((string)($this->config->get('module_noveraile_brand_name')?:$this->config->get('config_name')));return in_array($brand,['','Your Store'],true)?'6 Moments':$brand; }
}
