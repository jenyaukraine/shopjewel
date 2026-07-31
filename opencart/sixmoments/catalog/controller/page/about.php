<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments\Page;

class About extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        $this->document->setTitle($this->language->get('six_about_title') . ' | 6MOMENTS');
        $data = $this->language->all();
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        $data['asset'] = 'image/catalog/sixmoments/';
        $language = 'language=' . $this->config->get('config_language');
        $data['six_catalog_url'] = $this->url->link('extension/sixmoments/page/catalog', $language);
        $data['six_diamonds_url'] = $this->url->link('extension/sixmoments/page/diamonds', $language);
        $data['six_contact_url'] = $this->url->link('information/contact', $language);
        $this->response->setOutput($this->load->view('extension/sixmoments/page/about', $data));
    }
}
