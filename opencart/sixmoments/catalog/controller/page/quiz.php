<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments\Page;
class Quiz extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/sixmoments/module/sixmoments'); $this->document->setTitle($this->language->get('six_quiz_title') . ' | 6MOMENTS');
        $data=$this->language->all(); $data['header']=$this->load->controller('common/header');$data['footer']=$this->load->controller('common/footer');
        $data['products']=[]; $this->load->model('catalog/product');$this->load->model('tool/image');
        foreach($this->model_catalog_product->getProducts(['sort'=>'p.sort_order','order'=>'ASC','start'=>0,'limit'=>50]) as $product){$data['products'][]=['name'=>$product['name'],'tags'=>$product['tag'],'price'=>(float)($product['special']?:$product['price']),'price_text'=>$this->currency->format((float)($product['special']?:$product['price']),$this->session->data['currency']),'image'=>$this->model_tool_image->resize($product['image']?:'placeholder.png',600,600),'href'=>$this->url->link('product/product','language='.$this->config->get('config_language').'&product_id='.(int)$product['product_id'])];}
        $this->response->setOutput($this->load->view('extension/sixmoments/page/quiz',$data));
    }
}
