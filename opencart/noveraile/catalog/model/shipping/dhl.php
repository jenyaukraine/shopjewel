<?php
namespace Opencart\Catalog\Model\Extension\Noveraile\Shipping;

/**
 * DHL Express covers the European Union and the rest of the world. Rates and
 * delivery windows come from the tier table configured in the suite.
 */
class Dhl extends \Opencart\System\Engine\Model {
    public function getQuote(array $address): array {
        $this->load->model('extension/noveraile/shipping/tier');
        $tier = $this->model_extension_noveraile_shipping_tier->resolve('dhl', $address);
        if (!$tier) return [];

        $tax_class_id = (int)$this->config->get('shipping_dhl_tax_class_id');
        $quote = ['dhl' => [
            'code' => 'dhl.dhl',
            'name' => 'DHL Express · ' . $this->model_extension_noveraile_shipping_tier->window($tier),
            'cost' => $tier['cost'],
            'tax_class_id' => $tax_class_id,
            'text' => $this->currency->format($this->tax->calculate($tier['cost'], $tax_class_id, $this->config->get('config_tax')), $this->session->data['currency'])
        ]];

        return ['code' => 'dhl', 'name' => 'DHL Express', 'quote' => $quote, 'sort_order' => (int)$this->config->get('shipping_dhl_sort_order'), 'error' => false];
    }
}
