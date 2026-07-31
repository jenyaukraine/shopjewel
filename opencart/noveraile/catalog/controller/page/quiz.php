<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;
class Quiz extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile'); $this->document->setTitle($this->language->get('six_quiz_title') . ' | NOVERAILE');
        $data=$this->language->all(); $data['header']=$this->load->controller('common/header');$data['footer']=$this->load->controller('common/footer');
        $data['products']=[]; $this->load->model('catalog/product');$this->load->model('extension/noveraile/catalog');
        $starter_tags=['RI'=>'ring,yellow-gold','WE'=>'ring,rose-gold','NE'=>'necklace,white-gold','EA'=>'earring,yellow-gold','BR'=>'bracelet,rose-gold'];
        foreach($this->model_extension_noveraile_catalog->getProductIds(['sort'=>'popular','start'=>0,'limit'=>48]) as $product_id){$product=$this->model_catalog_product->getProduct($product_id);if(!$product)continue;$parts=explode('-',(string)$product['model']);$tags=trim((string)$product['tag'].','.(string)($starter_tags[$parts[1]??'']??''),',');$data['products'][]=['card'=>$this->productThumb($product,$tags)];}
        $rules=json_decode((string)$this->config->get('module_noveraile_quiz_rules'),true);$data['quiz_rules']=json_encode(is_array($rules)?$rules:[],JSON_UNESCAPED_UNICODE|JSON_HEX_APOS|JSON_HEX_QUOT);
        $data['newsletter_action']=$this->url->link('extension/noveraile/newsletter.subscribe','language='.$this->config->get('config_language'));
        $this->response->setOutput($this->load->view('extension/noveraile/page/quiz',$data));
    }

    private function productThumb(array $result, string $filter_tags): string {
        $this->load->model('tool/image');
        $image = !empty($result['image']) && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')) ? $result['image'] : 'placeholder.png';
        $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
        $effective_price = (float)($result['special'] ?: $result['price']);
        $data = array_merge($result, [
            'thumb' => $this->model_tool_image->resize($image, 700, 700),
            'description' => trim(strip_tags(html_entity_decode((string)$result['description'], ENT_QUOTES, 'UTF-8'))),
            'price' => $this->currency->format($this->tax->calculate((float)$result['price'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $currency),
            'special' => !empty($result['special']) ? $this->currency->format($this->tax->calculate((float)$result['special'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $currency) : false,
            'tax' => false,
            'minimum' => max(1, (int)$result['minimum']),
            'href' => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$result['product_id']),
            'cart_add' => $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language')),
            'wishlist_add' => $this->url->link('account/wishlist.add', 'language=' . $this->config->get('config_language')),
            'compare_add' => $this->url->link('product/compare.add', 'language=' . $this->config->get('config_language')),
            'review_status' => false,
            'rating' => 0,
            'six_filter_tags' => strtolower($filter_tags),
            'six_filter_price' => $effective_price
        ]);
        return $this->load->view('product/thumb', $data);
    }
}
