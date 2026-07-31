<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments\Page;
class Diamonds extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/sixmoments/module/sixmoments'); $this->document->setTitle($this->language->get('six_diamonds_title') . ' | 6MOMENTS');
        $data = $this->language->all(); $data['header'] = $this->load->controller('common/header'); $data['footer'] = $this->load->controller('common/footer');
        $data['catalog'] = $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&tag=lab-grown');
        $data['asset'] = 'image/catalog/sixmoments/'; $this->response->setOutput($this->load->view('extension/sixmoments/page/diamonds', $data));
    }
}
