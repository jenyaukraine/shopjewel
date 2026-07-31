<?php
namespace Opencart\Catalog\Model\Extension\Noveraile\Total;

class Bundle extends \Opencart\System\Engine\Model {
    public function getTotal(array &$totals, array &$taxes, float &$total): void {
        $pairs = $this->session->data['noveraile_bundle_pairs'] ?? [];
        if (!$pairs || !$this->config->get('total_bundle_status')) return;
        $products = $this->cart->getProducts();
        $by_id = [];
        foreach ($products as $product) $by_id[(int)$product['product_id']][] = $product;
        $discount_total = 0.0;
        foreach ($pairs as $pair) {
            $first = (int)($pair['product_id'] ?? 0); $second = (int)($pair['paired_id'] ?? 0);
            if (empty($by_id[$first]) || empty($by_id[$second])) continue;
            $first_product = $by_id[$first][0]; $second_product = $by_id[$second][0];
            $discounted_product = $first_product['total'] <= $second_product['total'] ? $first_product : $second_product;
            $discount = min((float)$discounted_product['total'] * .1, $total - $discount_total);
            if ($discount <= 0) continue;
            if (!empty($discounted_product['tax_class_id'])) {
                foreach ($this->tax->getRates($discount, (int)$discounted_product['tax_class_id']) as $rate) {
                    if ($rate['type'] === 'P' && isset($taxes[$rate['tax_rate_id']])) $taxes[$rate['tax_rate_id']] -= $rate['amount'];
                }
            }
            $discount_total += $discount;
        }
        if ($discount_total > 0) {
            $this->load->language('extension/noveraile/total/bundle');
            $totals[] = ['extension'=>'noveraile','code'=>'bundle','title'=>$this->language->get('text_bundle_discount'),'value'=>-$discount_total,'sort_order'=>(int)$this->config->get('total_bundle_sort_order')];
            $total -= $discount_total;
        }
    }
}
