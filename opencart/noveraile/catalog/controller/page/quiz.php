<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile\Page;
class Quiz extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/noveraile/module/noveraile'); $brand=$this->brand(); $this->document->setTitle($this->language->get('six_quiz_title') . ' | ' . $brand);
        $data=$this->language->all(); $data['six_brand_name']=$brand; $data['header']=$this->load->controller('common/header');$data['footer']=$this->load->controller('common/footer');
        $currency=(string)($this->session->data['currency']??$this->config->get('config_currency'));$multipliers=['USD'=>1.0,'EUR'=>0.94,'CZK'=>23.0,'UAH'=>41.0];$multiplier=$multipliers[$currency]??(float)$this->currency->getValue($currency);$data['quiz_budget_low']=(int)round(1200*$multiplier);$data['quiz_budget_high']=(int)round(2000*$multiplier);$data['quiz_budget_low_label']=$this->currency->format($data['quiz_budget_low'],$currency,1.0);$data['quiz_budget_high_label']=$this->currency->format($data['quiz_budget_high'],$currency,1.0);
        $data['products']=[]; $this->load->model('catalog/product');$this->load->model('extension/noveraile/catalog');
        foreach($this->model_extension_noveraile_catalog->getProductIds(['sort'=>'popular','start'=>0,'limit'=>48]) as $product_id){$product=$this->model_catalog_product->getProduct($product_id);if(!$product)continue;$data['products'][]=['card'=>$this->productThumb($product,trim((string)$product['tag'],','))];}
        $rules=json_decode((string)$this->config->get('module_noveraile_quiz_rules'),true);$data['quiz_rules']=json_encode(is_array($rules)?$rules:[],JSON_UNESCAPED_UNICODE|JSON_HEX_APOS|JSON_HEX_QUOT);
        $data['newsletter_action']=$this->url->link('extension/noveraile/newsletter.subscribe','language='.$this->config->get('config_language'));
        $this->response->setOutput($this->load->view('extension/noveraile/page/quiz',$data));
    }

    private function productThumb(array $result, string $filter_tags): string {
        $this->load->model('tool/image');
        $image = !empty($result['image']) && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')) ? $result['image'] : 'placeholder.png';
        $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
        $this->load->model('extension/noveraile/pricing');
        $market_price = $this->model_extension_noveraile_pricing->resolve($result, $currency);
        $effective_price = (float)($market_price['special'] ?: $market_price['price']);
        $data = array_merge($result, [
            'thumb' => $this->model_tool_image->resize($image, 700, 700),
            'description' => trim(strip_tags(html_entity_decode((string)$result['description'], ENT_QUOTES, 'UTF-8'))),
            'price' => $this->model_extension_noveraile_pricing->format($market_price['fixed'] ? $market_price['price'] : $this->tax->calculate($market_price['price'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $currency, $market_price['fixed']),
            'special' => $market_price['special'] > 0 ? $this->model_extension_noveraile_pricing->format($market_price['fixed'] ? $market_price['special'] : $this->tax->calculate($market_price['special'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $currency, $market_price['fixed']) : false,
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

    private function brand(): string { $brand=trim((string)($this->config->get('module_noveraile_brand_name')?:$this->config->get('config_name')));return in_array($brand,['','Your Store'],true)?'6 Moments':$brand; }
}
