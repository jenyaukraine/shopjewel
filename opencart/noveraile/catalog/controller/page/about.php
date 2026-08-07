<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;

class About extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile');
        $brand = $this->brand();
        $this->document->setTitle($this->language->get('six_about_title') . ' | ' . $brand);
        $data = $this->language->all();
        $data['six_brand_name'] = $brand;
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        $data['asset'] = 'image/catalog/noveraile/';
        $language = 'language=' . $this->config->get('config_language');
        $data['six_catalog_url'] = $this->url->link('extension/noveraile/page/catalog', $language);
        $data['six_diamonds_url'] = $this->url->link('extension/noveraile/page/diamonds', $language);
        $data['six_contact_url'] = (string)($this->config->get('module_noveraile_whatsapp') ?: 'https://wa.me/491707647729');
        $this->response->setOutput($this->load->view('extension/noveraile/page/about', $data));
    }

    private function brand(): string {
        $brand = trim((string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name')));
        return in_array($brand, ['', 'Your Store'], true) ? '6 Moments' : $brand;
    }
}
