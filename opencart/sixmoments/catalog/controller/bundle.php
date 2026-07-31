<?php
namespace Opencart\Catalog\Controller\Extension\Sixmoments;

class Bundle extends \Opencart\System\Engine\Controller {
    public function add(): void {
        $this->load->language('extension/sixmoments/module/sixmoments');
        $json = [];
        $product_id = (int)($this->request->post['product_id'] ?? 0);
        $paired_id = (int)($this->request->post['paired_id'] ?? 0);
        $this->load->model('catalog/product');
        $product = $this->model_catalog_product->getProduct($product_id);
        $paired = $this->model_catalog_product->getProduct($paired_id);
        if (!$product || !$paired || $product_id === $paired_id || !str_starts_with((string)$product['model'], '6M-') || !str_starts_with((string)$paired['model'], '6M-')) {
            $json['error'] = $this->language->get('six_bundle_error');
        } else {
            $this->cart->add($paired_id, 1);
            $pair_key = min($product_id, $paired_id) . ':' . max($product_id, $paired_id);
            $pairs = $this->session->data['sixmoments_bundle_pairs'] ?? [];
            $pairs[$pair_key] = ['product_id'=>$product_id, 'paired_id'=>$paired_id];
            $this->session->data['sixmoments_bundle_pairs'] = $pairs;
            $json['success'] = $this->language->get('six_bundle_added');
            $json['total'] = $this->cart->countProducts();
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }
}
