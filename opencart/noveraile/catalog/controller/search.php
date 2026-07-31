<?php
namespace Opencart\Catalog\Controller\Extension\Noveraile;

class Search extends \Opencart\System\Engine\Controller {
    public function suggest(): void {
        $json = ['results' => []];
        $q = trim((string)($this->request->get['q'] ?? ''));
        if (oc_strlen($q) >= 2) {
            $this->load->model('extension/noveraile/catalog');
            $this->load->model('catalog/product');
            $this->load->model('tool/image');
            $filter = ['q' => $q, 'sort' => 'popular', 'start' => 0, 'limit' => 6];
            foreach ($this->model_extension_noveraile_catalog->getProductIds($filter) as $product_id) {
                $product = $this->model_catalog_product->getProduct($product_id);
                if (!$product) continue;
                $image = !empty($product['image']) ? $product['image'] : 'placeholder.png';
                $price = (float)($product['special'] ?: $product['price']);
                $json['results'][] = [
                    'name' => $product['name'], 'model' => $product['model'],
                    'image' => $this->model_tool_image->resize($image, 120, 120),
                    'price' => $this->currency->format($price, $this->session->data['currency'] ?? $this->config->get('config_currency')),
                    'href' => html_entity_decode($this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$product_id), ENT_QUOTES, 'UTF-8')
                ];
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }
}
