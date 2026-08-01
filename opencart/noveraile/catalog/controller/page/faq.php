<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;
class Faq extends \Opencart\System\Engine\Controller {
    public function index(): void { $this->render('faq', 'six_faq_title'); }
    private function render(string $view, string $title): void { $this->load->language('extension/noveraile/module/noveraile'); $brand=$this->brand(); $this->document->setTitle($this->language->get($title) . ' | ' . $brand); $data=$this->language->all(); $data['six_brand_name']=$brand; $data['header']=$this->load->controller('common/header'); $data['footer']=$this->load->controller('common/footer'); $this->response->setOutput($this->load->view('extension/noveraile/page/'.$view,$data)); }
    private function brand(): string { $brand=trim((string)($this->config->get('module_noveraile_brand_name')?:$this->config->get('config_name')));return in_array($brand,['','Your Store'],true)?'6 Moments':$brand; }
}
