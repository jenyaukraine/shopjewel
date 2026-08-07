<?php
namespace Opencart\Catalog\Model\Extension\Noveraile\Shipping;

/**
 * DPD ships Ukraine and the European Union. Rates and delivery windows come
 * from the tier table configured in the suite, so a merchant changes a price
 * or adds a destination without touching code.
 */
class Dpd extends \Opencart\System\Engine\Model {
    public function getQuote(array $address): array {
        $this->load->model('extension/noveraile/shipping/tier');
        $tier = $this->model_extension_noveraile_shipping_tier->resolve('dpd', $address);
        if (!$tier) return [];

        $tax_class_id = (int)$this->config->get('shipping_dpd_tax_class_id');
        $quote = ['dpd' => [
            'code' => 'dpd.dpd',
            'name' => 'DPD · ' . $this->model_extension_noveraile_shipping_tier->window($tier),
            'cost' => $tier['cost'],
            'tax_class_id' => $tax_class_id,
            'text' => $this->currency->format($this->tax->calculate($tier['cost'], $tax_class_id, $this->config->get('config_tax')), $this->session->data['currency'])
        ]];

        return ['code' => 'dpd', 'name' => 'DPD', 'quote' => $quote, 'sort_order' => (int)$this->config->get('shipping_dpd_sort_order'), 'error' => false];
    }
}
