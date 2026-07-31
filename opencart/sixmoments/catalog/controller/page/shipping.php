<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments\Page;
class Shipping extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/sixmoments/module/sixmoments'); $this->document->setTitle($this->language->get('six_shipping_title') . ' | 6MOMENTS');
        $data = $this->language->all(); $data['header'] = $this->load->controller('common/header'); $data['footer'] = $this->load->controller('common/footer');
        $data['dhl_cost'] = $this->currency->format((float)$this->config->get('shipping_sixmoments_dhl_cost'), $this->session->data['currency']);
        $data['dpd_cost'] = $this->currency->format((float)$this->config->get('shipping_sixmoments_dpd_cost'), $this->session->data['currency']);
        $this->response->setOutput($this->load->view('extension/sixmoments/page/shipping', $data));
    }
}
