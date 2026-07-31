<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments\Page;
class Faq extends \Opencart\System\Engine\Controller {
    public function index(): void { $this->render('faq', 'six_faq_title'); }
    private function render(string $view, string $title): void { $this->load->language('extension/sixmoments/module/sixmoments'); $this->document->setTitle($this->language->get($title) . ' | 6MOMENTS'); $data=$this->language->all(); $data['header']=$this->load->controller('common/header'); $data['footer']=$this->load->controller('common/footer'); $this->response->setOutput($this->load->view('extension/sixmoments/page/'.$view,$data)); }
}
