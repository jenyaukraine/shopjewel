<?php
namespace Opencart\Catalog\Model\Extension\Noveraile\Shipping;

/**
 * Destination-based shipping rates shared by the DPD and DHL methods.
 *
 * Each carrier holds an ordered list of tiers; the first tier whose geo zone
 * contains the delivery address wins. A tier with `geo_zone_id` 0 matches
 * everywhere and is how "rest of the world" is expressed. A carrier with no
 * matching tier simply does not quote, which is what keeps DPD off orders
 * going outside Ukraine and the EU.
 */
class Tier extends \Opencart\System\Engine\Model {
    public function resolve(string $carrier, array $address): array {
        if (!$this->config->get('shipping_' . $carrier . '_status')) return [];

        $country_id = (int)($address['country_id'] ?? 0);
        $zone_id = (int)($address['zone_id'] ?? 0);

        foreach ($this->tiers($carrier) as $tier) {
            $geo_zone_id = (int)($tier['geo_zone_id'] ?? 0);
            if ($geo_zone_id && !$this->inGeoZone($geo_zone_id, $country_id, $zone_id)) continue;
            return [
                'cost' => max(0.0, (float)($tier['cost'] ?? 0)),
                'days_min' => max(0, (int)($tier['days_min'] ?? 0)),
                'days_max' => max(0, (int)($tier['days_max'] ?? 0))
            ];
        }

        return [];
    }

    /**
     * OpenCart does not load an extension language file before asking a
     * shipping method for a quote, so the window is composed here rather than
     * looked up, exactly as the previous flat-rate labels were.
     */
    public function window(array $tier): string {
        $min = (int)($tier['days_min'] ?? 0);
        $max = (int)($tier['days_max'] ?? 0);
        if (!$min && !$max) return 'delivery time on request';

        $range = $min && $max && $min !== $max ? $min . '–' . $max : (string)($max ?: $min);
        return $range . ' business days';
    }

    private function tiers(string $carrier): array {
        $value = $this->config->get('shipping_' . $carrier . '_tiers');
        $tiers = is_array($value) ? $value : json_decode((string)$value, true);
        if (is_array($tiers) && $tiers) return $tiers;

        // No tier table configured: fall back to the flat cost so an upgraded
        // store keeps quoting until the merchant fills the table in.
        $cost = (float)$this->config->get('shipping_' . $carrier . '_cost');
        return [['geo_zone_id' => (int)$this->config->get('shipping_' . $carrier . '_geo_zone_id'), 'cost' => $cost, 'days_min' => 0, 'days_max' => 0]];
    }

    private function inGeoZone(int $geo_zone_id, int $country_id, int $zone_id): bool {
        if (!$country_id) return false;
        $query = $this->db->query("SELECT `zone_to_geo_zone_id` FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . $geo_zone_id . "' AND `country_id` = '" . $country_id . "' AND (`zone_id` = '0' OR `zone_id` = '" . $zone_id . "') LIMIT 1");
        return (bool)$query->num_rows;
    }
}
