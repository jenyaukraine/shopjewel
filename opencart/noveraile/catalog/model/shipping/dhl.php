<?php
namespace Opencart\Catalog\Model\Extension\Noveraile\Shipping;

/**
 * DHL Express covers the European Union and the rest of the world. Rates and
 * delivery windows come from the tier table configured in the suite.
 */
class Dhl extends \Opencart\System\Engine\Model {
    public function getQuote(array $address): array {
        $key = 'shipping_dhl';
        if (!$this->config->get($key . '_status')) return [];

        $region = $this->regionForAddress($address);
        if ($region === '' || $region === 'ukraine') return [];

        $cost_key = $region === 'eu' ? $key . '_eu_cost' : $key . '_world_cost';
        $cost = $this->config->get($cost_key) !== null
            ? (float)$this->config->get($cost_key)
            : (float)$this->config->get($key . '_cost');
        $days = $region === 'eu' ? '3–7' : '5–10';
        $tax_class_id = (int)$this->config->get($key . '_tax_class_id');
        $quote = ['dhl' => [
            'code' => 'dhl.dhl',
            'name' => 'DHL Express · ' . $days . ' business days',
            'cost' => $cost,
            'tax_class_id' => $tax_class_id,
            'text' => $this->currency->format($this->tax->calculate($cost, $tax_class_id, $this->config->get('config_tax')), $this->session->data['currency'])
        ]];

        return ['code' => 'dhl', 'name' => 'DHL Express', 'quote' => $quote, 'sort_order' => (int)$this->config->get($key . '_sort_order'), 'error' => false];
    }

    private function regionForAddress(array $address): string {
        $code = strtoupper(trim((string)($address['iso_code_2'] ?? '')));
        if ($code === '' && !empty($address['country_id'])) {
            $query = $this->db->query("SELECT `iso_code_2` FROM `" . DB_PREFIX . "country` WHERE `country_id` = '" . (int)$address['country_id'] . "' LIMIT 1");
            $code = strtoupper((string)($query->row['iso_code_2'] ?? ''));
        }
        if ($code === '') return '';
        if ($code === 'UA') return 'ukraine';
        $eu = ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE'];
        return in_array($code, $eu, true) ? 'eu' : 'world';
    }
}
