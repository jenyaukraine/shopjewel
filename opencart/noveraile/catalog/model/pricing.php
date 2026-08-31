<?php
namespace Opencart\Catalog\Model\Extension\Noveraile;

class Pricing extends \Opencart\System\Engine\Model {
    private ?array $book = null;

    public function resolve(array $product, ?string $currency = null): array {
        $currency = strtoupper($currency ?: (string)($this->session->data['currency'] ?? $this->config->get('config_currency')));
        $model = (string)($product['model'] ?? '');
        $product_id = (string)(int)($product['product_id'] ?? 0);
        $entry = $this->book()[$currency][$model] ?? $this->book()[$currency][$product_id] ?? null;
        $fixed = is_array($entry) && isset($entry['price']) && is_numeric($entry['price']);
        $multiplier = $this->multiplier();
        $price = $fixed ? (float)$entry['price'] : (float)($product['price'] ?? 0);
        $special = $fixed && isset($entry['special']) && is_numeric($entry['special'])
            ? (float)$entry['special']
            : (float)($product['special'] ?? 0);

        return [
            'price' => $price * $multiplier,
            'special' => $special > 0 ? $special * $multiplier : 0.0,
            'fixed' => $fixed,
            'adjusted' => $fixed || abs($multiplier - 1.0) >= 0.0001,
            'multiplier' => $multiplier,
            'currency' => $currency
        ];
    }

    public function format(float $amount, string $currency, bool $fixed): string {
        return $this->currency->format($amount, $currency, $fixed ? 1.0 : 0.0);
    }

    public function cartAdjustment(array $products, string $currency): float {
        $rate = (float)$this->currency->getValue($currency);
        if ($rate <= 0) return 0.0;
        $adjustment = 0.0;
        foreach ($products as $product) {
            $price = $this->resolve($product, $currency);
            if (!$price['adjusted']) continue;

            if ($price['fixed']) {
                $target = ($price['special'] > 0 ? $price['special'] : $price['price']) / $rate;
                $adjustment += $target * max(1, (int)($product['quantity'] ?? 1)) - (float)($product['total'] ?? 0);
            } else {
                // The cart's raw total already includes option surcharges and
                // product discounts. Scaling that value keeps every selected
                // variant in step with the catalog-wide merchant coefficient.
                $adjustment += (float)($product['total'] ?? 0) * ($price['multiplier'] - 1.0);
            }
        }
        return $adjustment;
    }

    public function multiplier(): float {
        $value = $this->config->get('module_noveraile_price_multiplier');
        if (!is_numeric($value)) return 1.0;

        return min(100.0, max(0.01, (float)$value));
    }

    private function book(): array {
        if ($this->book === null) {
            $decoded = json_decode((string)$this->config->get('module_noveraile_price_book'), true);
            $this->book = is_array($decoded) ? $decoded : [];
        }
        return $this->book;
    }
}
