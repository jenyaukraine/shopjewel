<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;
class Diamonds extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile'); $this->document->setTitle($this->language->get('six_diamonds_title') . ' | NOVERAILE');
        $data = $this->language->all(); $data['header'] = $this->load->controller('common/header'); $data['footer'] = $this->load->controller('common/footer');
        $data['catalog'] = $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&tag=lab-grown');
        $data['asset'] = 'image/catalog/noveraile/'; $this->response->setOutput($this->load->view('extension/noveraile/page/diamonds', $data));
    }
}
