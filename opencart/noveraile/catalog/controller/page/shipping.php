<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;
class Shipping extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile'); $this->document->setTitle($this->language->get('six_shipping_title') . ' | NOVERAILE');
        $data = $this->language->all(); $data['header'] = $this->load->controller('common/header'); $data['footer'] = $this->load->controller('common/footer');
        $data['dhl_cost'] = $this->currency->format((float)$this->config->get('shipping_dhl_cost'), $this->session->data['currency']);
        $data['dpd_cost'] = $this->currency->format((float)$this->config->get('shipping_dpd_cost'), $this->session->data['currency']);
        $this->response->setOutput($this->load->view('extension/noveraile/page/shipping', $data));
    }
}
