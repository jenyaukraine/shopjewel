<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;
class Shipping extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile'); $brand=$this->brand(); $this->document->setTitle($this->language->get('six_shipping_title') . ' | ' . $brand);
        $data = $this->language->all(); $data['six_brand_name']=$brand; $data['header'] = $this->load->controller('common/header'); $data['footer'] = $this->load->controller('common/footer');
        $data['dhl_cost'] = $this->currency->format((float)$this->config->get('shipping_dhl_cost'), $this->session->data['currency']);
        $data['dpd_cost'] = $this->currency->format((float)$this->config->get('shipping_dpd_cost'), $this->session->data['currency']);
        $this->response->setOutput($this->load->view('extension/noveraile/page/shipping', $data));
    }

    private function brand(): string { $brand=trim((string)($this->config->get('module_noveraile_brand_name')?:$this->config->get('config_name')));return in_array($brand,['','Your Store'],true)?'6 Moments':$brand; }
}
