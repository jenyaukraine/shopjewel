<?php
namespace Opencart\Catalog\Model\Extension\Noveraile\Shipping;

class Dpd extends \Opencart\System\Engine\Model {
    public function getQuote(array $address): array {
        $key = 'shipping_dpd';
        if (!$this->config->get($key . '_status') || !$this->availableForAddress($address, (int)$this->config->get($key . '_geo_zone_id'))) return [];

        $cost = (float)$this->config->get($key . '_cost');
        $tax_class_id = (int)$this->config->get($key . '_tax_class_id');
        $quote = ['dpd' => [
            'code' => 'dpd.dpd',
            'name' => 'DPD · 1–7 business days',
            'cost' => $cost,
            'tax_class_id' => $tax_class_id,
            'text' => $this->currency->format($this->tax->calculate($cost, $tax_class_id, $this->config->get('config_tax')), $this->session->data['currency'])
        ]];

        return ['code' => 'dpd', 'name' => 'DPD', 'quote' => $quote, 'sort_order' => (int)$this->config->get($key . '_sort_order'), 'error' => false];
    }

    private function availableForAddress(array $address, int $geo_zone_id): bool {
        if (!$geo_zone_id) return true;
        $country_id = (int)($address['country_id'] ?? 0);
        $zone_id = (int)($address['zone_id'] ?? 0);
        if (!$country_id) return false;
        $query = $this->db->query("SELECT `zone_to_geo_zone_id` FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . $geo_zone_id . "' AND `country_id` = '" . $country_id . "' AND (`zone_id` = '0' OR `zone_id` = '" . $zone_id . "') LIMIT 1");
        return (bool)$query->num_rows;
    }
}
