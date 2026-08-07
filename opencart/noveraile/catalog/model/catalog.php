<?php
namespace Opencart\Catalog\Model\Extension\Noveraile;

class Catalog extends \Opencart\System\Engine\Model {
    public function getProductIds(array $filter): array {
        $sql = $this->baseSql($filter, false);
        $attribute_map = $this->attributeMap();
        $carat_attribute_id = (int)($attribute_map['carat'] ?? 0);
        $carat_sort = $carat_attribute_id
            ? "COALESCE((SELECT CAST(REPLACE(`pa_sort`.`text`, ',', '.') AS DECIMAL(10,3)) FROM `" . DB_PREFIX . "product_attribute` `pa_sort` WHERE `pa_sort`.`product_id` = `p`.`product_id` AND `pa_sort`.`attribute_id` = '" . $carat_attribute_id . "' AND `pa_sort`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1), 0)"
            : '0';
        $weight_sort = "(`p`.`weight` / NULLIF(COALESCE((SELECT `wc_sort`.`value` FROM `" . DB_PREFIX . "weight_class` `wc_sort` WHERE `wc_sort`.`weight_class_id` = `p`.`weight_class_id` LIMIT 1), 1), 0))";
        $sorts = [
            'popular' => 'COALESCE((SELECT SUM(`op`.`quantity`) FROM `' . DB_PREFIX . 'order_product` `op` WHERE `op`.`product_id` = `p`.`product_id`), 0) DESC, `p`.`sort_order` ASC',
            'price-asc' => '`effective_price` ASC, `pd`.`name` ASC',
            'price-desc' => '`effective_price` DESC, `pd`.`name` ASC',
            'newest' => '`p`.`date_added` DESC, `p`.`product_id` DESC',
            'carat-asc' => $carat_sort . ' ASC, `pd`.`name` ASC',
            'carat-desc' => $carat_sort . ' DESC, `pd`.`name` ASC',
            'weight-asc' => $weight_sort . ' ASC, `pd`.`name` ASC',
            'weight-desc' => $weight_sort . ' DESC, `pd`.`name` ASC',
            'name-asc' => '`pd`.`name` ASC'
        ];
        $sql .= ' ORDER BY ' . ($sorts[$filter['sort'] ?? 'popular'] ?? $sorts['popular']);
        $sql .= ' LIMIT ' . max(0, (int)($filter['start'] ?? 0)) . ',' . max(1, min(48, (int)($filter['limit'] ?? 12)));

        return array_map('intval', array_column($this->db->query($sql)->rows, 'product_id'));
    }

    public function getTotalProducts(array $filter): int {
        $query = $this->db->query($this->baseSql($filter, true));
        return (int)($query->row['total'] ?? 0);
    }

    public function getPriceBounds(): array {
        $customer_group_id = (int)$this->config->get('config_customer_group_id');
        $store_id = (int)$this->config->get('config_store_id');
        $special = "(SELECT `product_id`, MIN(`price`) AS `price` FROM `" . DB_PREFIX . "product_discount` WHERE `customer_group_id` = '" . $customer_group_id . "' AND `special` = '1' AND (`date_start` = '0000-00-00' OR `date_start` <= NOW()) AND (`date_end` = '0000-00-00' OR `date_end` >= NOW()) GROUP BY `product_id`)";
        $sql = "SELECT MIN(COALESCE(`ps`.`price`, `p`.`price`)) AS `price_min`, MAX(COALESCE(`ps`.`price`, `p`.`price`)) AS `price_max` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . $store_id . "') LEFT JOIN " . $special . " `ps` ON (`ps`.`product_id` = `p`.`product_id`) WHERE `p`.`status` = '1' AND `p`.`date_available` <= NOW()";
        $row = $this->db->query($sql)->row;

        return [
            'min' => max(0.0, (float)($row['price_min'] ?? 0)),
            'max' => max(0.0, (float)($row['price_max'] ?? 0))
        ];
    }

    public function getCategories(): array {
        $sql = "SELECT DISTINCT `c`.`category_id`, `cd`.`name`, `c`.`sort_order` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`category_id` = `c`.`category_id`) INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `p2c`.`product_id` AND `p`.`status` = '1') WHERE `c`.`status` = '1' ORDER BY `c`.`sort_order` ASC, `cd`.`name` ASC";
        return $this->db->query($sql)->rows;
    }

    /**
     * Values are read from OpenCart product attributes, so a merchant can add
     * a new cut, gemstone or style in admin without changing this template.
     */
    public function getAttributeFacets(): array {
        $cache_key = $this->facetCacheKey('attributes');
        $cached = $this->cache->get($cache_key);
        if (is_array($cached)) return $cached;

        $facets = ['gemstone' => [], 'stone_shape' => [], 'stone_quality' => [], 'style' => []];
        $attribute_map = $this->attributeMap();
        $language_id = (int)$this->config->get('config_language_id');
        $store_id = (int)$this->config->get('config_store_id');

        foreach ($facets as $key => $_) {
            $attribute_id = (int)($attribute_map[$key] ?? 0);
            if (!$attribute_id) continue;
            $sql = "SELECT TRIM(`pa`.`text`) AS `value`, COUNT(DISTINCT `p`.`product_id`) AS `total` FROM `" . DB_PREFIX . "product_attribute` `pa` INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `pa`.`product_id` AND `p`.`status` = '1' AND `p`.`date_available` <= NOW()) INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . $store_id . "') WHERE `pa`.`attribute_id` = '" . $attribute_id . "' AND `pa`.`language_id` = '" . $language_id . "' AND TRIM(`pa`.`text`) <> ''";
            if (in_array($key, ['stone_shape', 'stone_quality'], true) && !empty($attribute_map['carat'])) {
                $sql .= " AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_attribute` `pa_carat` WHERE `pa_carat`.`product_id` = `p`.`product_id` AND `pa_carat`.`attribute_id` = '" . (int)$attribute_map['carat'] . "' AND `pa_carat`.`language_id` = '" . $language_id . "' AND CAST(REPLACE(`pa_carat`.`text`, ',', '.') AS DECIMAL(10,3)) > 0)";
            }
            $sql .= " GROUP BY TRIM(`pa`.`text`) ORDER BY TRIM(`pa`.`text`) ASC";
            $facets[$key] = $this->db->query($sql)->rows;
        }

        // Supplier feeds often encode colour/clarity in a selectable variant
        // (for example "14K · D/VVS2") instead of an OpenCart attribute.
        // Merge those values so the customer-facing quality filter remains
        // useful without forcing merchants to rewrite the source feed.
        $quality_totals = [];
        foreach ($facets['stone_quality'] as $facet) {
            $quality_totals[(string)$facet['value']] = (int)$facet['total'];
        }
        foreach ($this->getOptionStoneQualityFacets() as $facet) {
            $quality_totals[(string)$facet['value']] = max($quality_totals[(string)$facet['value']] ?? 0, (int)$facet['total']);
        }
        ksort($quality_totals, SORT_NATURAL | SORT_FLAG_CASE);
        $facets['stone_quality'] = [];
        foreach ($quality_totals as $value => $total) {
            $facets['stone_quality'][] = ['value' => $value, 'total' => $total];
        }

        $this->cache->set($cache_key, $facets);

        return $facets;
    }

    /**
     * Shapes are recognised from free text, which means one pass over every
     * published product. That is cheap for a hand-built catalog and expensive
     * for an imported one, so the answer is cached until the catalog changes.
     */
    public function getStoneShapeFacets(): array {
        $cache_key = $this->facetCacheKey('shapes');
        $cached = $this->cache->get($cache_key);
        if (is_array($cached)) return $cached;

        $language_id = (int)$this->config->get('config_language_id');
        $store_id = (int)$this->config->get('config_store_id');
        $attribute_id = (int)($this->attributeMap()['stone_shape'] ?? 0);
        $join = $attribute_id
            ? " LEFT JOIN `" . DB_PREFIX . "product_attribute` `pa_shape` ON (`pa_shape`.`product_id` = `p`.`product_id` AND `pa_shape`.`language_id` = '" . $language_id . "' AND `pa_shape`.`attribute_id` = '" . $attribute_id . "')"
            : '';
        $attribute_text = $attribute_id ? ", GROUP_CONCAT(`pa_shape`.`text` SEPARATOR ' ') AS `shape_text`" : ", '' AS `shape_text`";
        $sql = "SELECT `p`.`product_id`, `pd`.`name`, `pd`.`description`, `pd`.`tag`" . $attribute_text . " FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . $language_id . "') INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . $store_id . "')" . $join . " WHERE `p`.`status` = '1' AND `p`.`date_available` <= NOW() GROUP BY `p`.`product_id`, `pd`.`name`, `pd`.`description`, `pd`.`tag`";
        $totals = array_fill_keys(array_keys($this->stoneShapeAliases()), 0);
        foreach ($this->db->query($sql)->rows as $row) {
            $haystack = mb_strtolower(strip_tags(html_entity_decode(implode(' ', [$row['name'], $row['description'], $row['tag'], $row['shape_text']]), ENT_QUOTES, 'UTF-8')));
            foreach ($this->stoneShapeAliases() as $shape => $aliases) {
                foreach ($aliases as $alias) {
                    $pattern = '/(?<![\p{L}\p{N}])' . preg_quote(mb_strtolower($alias), '/') . '(?![\p{L}\p{N}])/u';
                    if (preg_match($pattern, $haystack)) {
                        $totals[$shape]++;
                        break;
                    }
                }
            }
        }

        $this->cache->set($cache_key, $totals);

        return $totals;
    }

    private function getOptionStoneQualityFacets(): array {
        $language_id = (int)$this->config->get('config_language_id');
        $store_id = (int)$this->config->get('config_store_id');
        $sql = "SELECT DISTINCT `p`.`product_id`, `ovd`.`name` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . $store_id . "') INNER JOIN `" . DB_PREFIX . "product_option_value` `pov` ON (`pov`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`ovd`.`option_value_id` = `pov`.`option_value_id` AND `ovd`.`language_id` = '" . $language_id . "') WHERE `p`.`status` = '1' AND `p`.`date_available` <= NOW()";
        $products = [];
        foreach ($this->db->query($sql)->rows as $row) {
            if (!preg_match_all('/\b(?:[D-J]\/(?:FL|IF|VVS[12]?|VS[12]?|SI[12]?|I[123]?)|LAB)\b/i', (string)$row['name'], $matches)) continue;
            foreach (array_unique(array_map('strtoupper', $matches[0])) as $quality) {
                $products[$quality][(int)$row['product_id']] = true;
            }
        }
        ksort($products, SORT_NATURAL | SORT_FLAG_CASE);
        $facets = [];
        foreach ($products as $quality => $product_ids) {
            $facets[] = ['value' => $quality, 'total' => count($product_ids)];
        }
        return $facets;
    }

    public function getRingSizes(): array {
        $option_id = (int)$this->config->get('module_noveraile_ring_size_option_id');
        if (!$option_id) return [];
        $sql = "SELECT DISTINCT `ovd`.`name` AS `value`, `ov`.`sort_order` FROM `" . DB_PREFIX . "product_option` `po` INNER JOIN `" . DB_PREFIX . "product_option_value` `pov` ON (`pov`.`product_option_id` = `po`.`product_option_id` AND `pov`.`product_id` = `po`.`product_id`) INNER JOIN `" . DB_PREFIX . "option_value` `ov` ON (`ov`.`option_value_id` = `pov`.`option_value_id`) INNER JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`ovd`.`option_value_id` = `ov`.`option_value_id` AND `ovd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `po`.`product_id` AND `p`.`status` = '1') INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "') WHERE `po`.`option_id` = '" . $option_id . "' ORDER BY `ov`.`sort_order` ASC, `ovd`.`name` ASC";
        return $this->db->query($sql)->rows;
    }

    private function baseSql(array $filter, bool $count): string {
        $customer_group_id = (int)$this->config->get('config_customer_group_id');
        $special = "(SELECT `product_id`, MIN(`price`) AS `price` FROM `" . DB_PREFIX . "product_discount` WHERE `customer_group_id` = '" . $customer_group_id . "' AND `special` = '1' AND (`date_start` = '0000-00-00' OR `date_start` <= NOW()) AND (`date_end` = '0000-00-00' OR `date_end` >= NOW()) GROUP BY `product_id`)";
        $select = $count ? 'SELECT COUNT(DISTINCT `p`.`product_id`) AS `total`' : 'SELECT DISTINCT `p`.`product_id`, COALESCE(`ps`.`price`, `p`.`price`) AS `effective_price`';
        $sql = $select . " FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "') LEFT JOIN " . $special . " `ps` ON (`ps`.`product_id` = `p`.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`product_id` = `p`.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_code` `pc` ON (`pc`.`product_id` = `p`.`product_id`) WHERE `p`.`status` = '1' AND `p`.`date_available` <= NOW()";

        if (!empty($filter['q'])) {
            $q = $this->db->escape('%' . trim((string)$filter['q']) . '%');
            $sql .= " AND (`pd`.`name` LIKE '" . $q . "' OR `pd`.`description` LIKE '" . $q . "' OR `pd`.`tag` LIKE '" . $q . "' OR `p`.`model` LIKE '" . $q . "' OR `pc`.`value` LIKE '" . $q . "')";
        }
        if (!empty($filter['category_id'])) {
            $sql .= " AND `p2c`.`category_id` = '" . (int)$filter['category_id'] . "'";
        }
        if (!empty($filter['type'])) {
            $prefixes = ['rings' => 'RI', 'earrings' => 'EA', 'necklaces' => 'NE', 'bracelets' => 'BR', 'wedding' => 'WE'];
            $tags = ['rings' => 'ring', 'earrings' => 'earrings', 'necklaces' => 'necklace', 'bracelets' => 'bracelet', 'wedding' => 'wedding'];
            $type = (string)$filter['type'];
            if (isset($prefixes[$type])) {
                $sql .= " AND (`p`.`model` LIKE 'NVR-" . $prefixes[$type] . "-%' OR FIND_IN_SET('" . $tags[$type] . "', REPLACE(LOWER(`pd`.`tag`), ' ', '')))";
            }
        }
        foreach (['moment', 'delivery'] as $key) {
            if (!empty($filter[$key])) {
                $tag = $this->db->escape(strtolower(preg_replace('/[^a-z0-9-]/i', '', (string)$filter[$key])));
                $sql .= " AND FIND_IN_SET('" . $tag . "', REPLACE(LOWER(`pd`.`tag`), ' ', ''))";
            }
        }
        $attribute_map = $this->attributeMap();
        foreach (['metal' => 'metal', 'fineness' => 'fineness', 'stone' => 'stone_origin'] as $filter_key => $attribute_key) {
            if (empty($filter[$filter_key])) continue;
            $predicate = $this->specificationPredicate($filter_key, (string)$filter[$filter_key]);
            if ($predicate !== '') $sql .= ' AND ' . $predicate;
        }
        foreach (['gemstone', 'style'] as $key) {
            $attribute_id = (int)($attribute_map[$key] ?? 0);
            if ($attribute_id && !empty($filter[$key])) {
                $filter_value = trim((string)$filter[$key]);
                $value = $this->db->escape($filter_value);
                $sql .= " AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_attribute` `pa_" . $key . "` WHERE `pa_" . $key . "`.`product_id` = `p`.`product_id` AND `pa_" . $key . "`.`attribute_id` = '" . $attribute_id . "' AND `pa_" . $key . "`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND TRIM(`pa_" . $key . "`.`text`) = '" . $value . "')";
            }
        }
        if (!empty($filter['stone_shape']) && isset($this->stoneShapeAliases()[(string)$filter['stone_shape']])) {
            $shape = (string)$filter['stone_shape'];
            $predicates = [];
            foreach ($this->stoneShapeAliases()[$shape] as $alias) {
                $value = $this->db->escape('(^|[^[:alnum:]_])' . mb_strtolower($alias) . '([^[:alnum:]_]|$)');
                $predicates[] = "LOWER(CONCAT_WS(' ', `pd`.`name`, `pd`.`description`, `pd`.`tag`)) REGEXP '" . $value . "'";
                if (!empty($attribute_map['stone_shape'])) {
                    $predicates[] = "EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_attribute` `pa_shape` WHERE `pa_shape`.`product_id` = `p`.`product_id` AND `pa_shape`.`attribute_id` = '" . (int)$attribute_map['stone_shape'] . "' AND `pa_shape`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND LOWER(TRIM(`pa_shape`.`text`)) REGEXP '" . $value . "')";
                }
            }
            $sql .= ' AND (' . implode(' OR ', $predicates) . ')';
        }
        if (!empty($filter['stone_quality'])) {
            $quality = strtoupper(preg_replace('/[^A-Z0-9\/-]/i', '', (string)$filter['stone_quality']));
            if ($quality !== '') {
                $value = $this->db->escape($quality);
                $predicates = ["EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_option_value` `pov_quality` INNER JOIN `" . DB_PREFIX . "option_value_description` `ovd_quality` ON (`ovd_quality`.`option_value_id` = `pov_quality`.`option_value_id` AND `ovd_quality`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `pov_quality`.`product_id` = `p`.`product_id` AND UPPER(`ovd_quality`.`name`) LIKE '%" . $value . "%')"];
                if (!empty($attribute_map['stone_quality'])) {
                    $predicates[] = "EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_attribute` `pa_quality` WHERE `pa_quality`.`product_id` = `p`.`product_id` AND `pa_quality`.`attribute_id` = '" . (int)$attribute_map['stone_quality'] . "' AND `pa_quality`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND UPPER(TRIM(`pa_quality`.`text`)) = '" . $value . "')";
                }
                $sql .= ' AND (' . implode(' OR ', $predicates) . ')';
            }
        }
        $carat_attribute_id = (int)($attribute_map['carat'] ?? 0);
        if ($carat_attribute_id && (($filter['carat_min'] ?? '') !== '' || ($filter['carat_max'] ?? '') !== '')) {
            $conditions = [];
            if (($filter['carat_min'] ?? '') !== '') $conditions[] = "CAST(REPLACE(`pa_carat`.`text`, ',', '.') AS DECIMAL(10,3)) >= '" . (float)$filter['carat_min'] . "'";
            if (($filter['carat_max'] ?? '') !== '') $conditions[] = "CAST(REPLACE(`pa_carat`.`text`, ',', '.') AS DECIMAL(10,3)) <= '" . (float)$filter['carat_max'] . "'";
            $sql .= " AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_attribute` `pa_carat` WHERE `pa_carat`.`product_id` = `p`.`product_id` AND `pa_carat`.`attribute_id` = '" . $carat_attribute_id . "' AND `pa_carat`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND " . implode(' AND ', $conditions) . ")";
        }
        if (!empty($filter['ring_size'])) {
            $ring_option_id = (int)$this->config->get('module_noveraile_ring_size_option_id');
            if ($ring_option_id) {
                $ring_size = $this->db->escape(trim((string)$filter['ring_size']));
                $sql .= " AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_option` `po_size` INNER JOIN `" . DB_PREFIX . "product_option_value` `pov_size` ON (`pov_size`.`product_option_id` = `po_size`.`product_option_id` AND `pov_size`.`product_id` = `po_size`.`product_id`) INNER JOIN `" . DB_PREFIX . "option_value_description` `ovd_size` ON (`ovd_size`.`option_value_id` = `pov_size`.`option_value_id` AND `ovd_size`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `po_size`.`product_id` = `p`.`product_id` AND `po_size`.`option_id` = '" . $ring_option_id . "' AND `ovd_size`.`name` = '" . $ring_size . "')";
            }
        }
        if (($filter['availability'] ?? '') === 'ready') {
            $sql .= " AND `p`.`quantity` > '0'";
        } elseif (($filter['availability'] ?? '') === 'preorder') {
            $sql .= " AND `p`.`quantity` <= '0'";
        }
        if (!empty($filter['sale'])) {
            $sql .= ' AND `ps`.`price` IS NOT NULL';
        }
        if (isset($filter['price_min']) && $filter['price_min'] !== '') {
            $sql .= " AND COALESCE(`ps`.`price`, `p`.`price`) >= '" . (float)$filter['price_min'] . "'";
        }
        if (isset($filter['price_max']) && $filter['price_max'] !== '') {
            $sql .= " AND COALESCE(`ps`.`price`, `p`.`price`) <= '" . (float)$filter['price_max'] . "'";
        }

        return $sql;
    }

    /**
     * Metal, fineness and stone origin are single attribute fields, but a
     * supplier feed can offer several of each on one product and express the
     * choice as a selectable option instead ("14K · D/VVS2"). Match both, so a
     * merchant maintained product and an imported one answer the same filter.
     */
    private function specificationPredicate(string $key, string $value): string {
        $attribute_key = ['metal' => 'metal', 'fineness' => 'fineness', 'stone' => 'stone_origin'][$key] ?? $key;
        $attribute_id = (int)($this->attributeMap()[$attribute_key] ?? 0);
        $language_id = (int)$this->config->get('config_language_id');
        $predicates = [];

        if ($attribute_id) {
            $text = $this->db->escape($this->localizedAttributeValue($key, $value));
            $predicates[] = "EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_attribute` `pa_spec` WHERE `pa_spec`.`product_id` = `p`.`product_id` AND `pa_spec`.`attribute_id` = '" . $attribute_id . "' AND `pa_spec`.`language_id` = '" . $language_id . "' AND (TRIM(`pa_spec`.`text`) = '" . $text . "' OR `pa_spec`.`text` LIKE '%" . $this->db->escape($this->likeWildcards($this->localizedAttributeValue($key, $value))) . "%'))";
        } else {
            $tag = $this->db->escape(strtolower(preg_replace('/[^a-z0-9-]/i', '', $value)));
            $predicates[] = "FIND_IN_SET('" . $tag . "', REPLACE(LOWER(`pd`.`tag`), ' ', ''))";
        }

        $option = $this->optionNamePattern($key, $value);
        if ($option !== '') {
            $predicates[] = "EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_option_value` `pov_spec` INNER JOIN `" . DB_PREFIX . "option_value_description` `ovd_spec` ON (`ovd_spec`.`option_value_id` = `pov_spec`.`option_value_id` AND `ovd_spec`.`language_id` = '" . $language_id . "') WHERE `pov_spec`.`product_id` = `p`.`product_id` AND UPPER(`ovd_spec`.`name`) " . $option . ")";
        }

        return $predicates ? '(' . implode(' OR ', $predicates) . ')' : '';
    }

    private function optionNamePattern(string $key, string $value): string {
        if ($key === 'fineness') {
            $karat = ['375' => '9K', '585' => '14K', '750' => '18K'][$value] ?? '';
            return $karat === '' ? '' : "LIKE '" . $this->db->escape($karat) . " %'";
        }

        if ($key === 'stone') {
            if ($value === 'lab-grown') return "LIKE '%LAB%'";
            if ($value === 'natural') return "REGEXP '[D-J]/(FL|IF|VVS|VS|SI|I)'";
        }

        return '';
    }

    private function likeWildcards(string $value): string {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Counts for the specification filters so the storefront can hide a choice
     * no product in the catalog can satisfy.
     */
    public function getSpecificationFacets(string $key, array $values): array {
        $cache_key = $this->facetCacheKey($key);
        $cached = $this->cache->get($cache_key);
        if (is_array($cached) && !array_diff_key(array_flip($values), $cached)) return $cached;

        $totals = [];
        foreach ($values as $value) {
            $predicate = $this->specificationPredicate($key, $value);
            if ($predicate === '') {
                $totals[$value] = 0;
                continue;
            }
            $sql = "SELECT COUNT(DISTINCT `p`.`product_id`) AS `total` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "') WHERE `p`.`status` = '1' AND `p`.`date_available` <= NOW() AND " . $predicate;
            $totals[$value] = (int)($this->db->query($sql)->row['total'] ?? 0);
        }

        $this->cache->set($cache_key, $totals);

        return $totals;
    }

    /**
     * The catalog importer clears every entry under this prefix, so the facets
     * follow the assortment without waiting for the cache to expire.
     */
    private function facetCacheKey(string $name): string {
        return 'noveraile.facet.' . $name . '.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id');
    }

    private function attributeMap(): array {
        $value = $this->config->get('module_noveraile_attribute_map');
        if (is_array($value)) return $value;
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function stoneShapeAliases(): array {
        return [
            'round' => ['round', 'rund', 'kulatý', 'круглый', 'кругла'],
            'princess' => ['princess', 'prinzess', 'принцесса', 'принцеса'],
            'marquise' => ['marquise', 'markýza', 'маркиз', 'маркіз'],
            'baguette' => ['baguette', 'bageta', 'багет'],
            'cushion' => ['cushion', 'kissen', 'polštářek', 'кушон'],
            'heart' => ['heart', 'herz', 'srdce', 'сердце', 'серце'],
            'oval' => ['oval', 'ovál', 'овал'],
            'pear' => ['pear', 'birne', 'hruška', 'груша'],
            'emerald' => ['emerald', 'smaragd', 'smaragdový', 'изумруд', 'смарагд'],
            'radiant' => ['radiant', 'radiant-cut', 'радиант'],
            'asscher' => ['asscher', 'ашер']
        ];
    }

    private function localizedAttributeValue(string $key, string $value): string {
        $language = (string)$this->config->get('config_language');
        $language_index = ['en-gb' => 0, 'de-de' => 1, 'cs-cz' => 2, 'ru-ru' => 3, 'uk-ua' => 4][$language] ?? 0;
        $values = [
            'metal' => [
                'white-gold' => ['White gold','Weißgold','Bílé zlato','Белое золото','Біле золото'],
                'yellow-gold' => ['Yellow gold','Gelbgold','Žluté zlato','Жёлтое золото','Жовте золото'],
                'rose-gold' => ['Rose gold','Roségold','Růžové zlato','Розовое золото','Рожеве золото'],
                'platinum' => ['Platinum','Platin','Platina','Платина','Платина']
            ],
            'stone' => [
                'natural' => ['Natural','Natürlich','Přírodní','Натуральный','Натуральний'],
                'lab-grown' => ['Lab-grown','Laborgezüchtet','Laboratorní','Лабораторный','Лабораторний'],
                'no-stones' => ['Not applicable','Nicht zutreffend','Nevztahuje se','Не применяется','Не застосовується']
            ],
            'stone_shape' => [
                'round' => ['Round','Rund','Kulatý','Круглая','Кругла'],
                'princess' => ['Princess','Prinzess','Princess','Принцесса','Принцеса'],
                'marquise' => ['Marquise','Marquise','Markýza','Маркиз','Маркіз'],
                'baguette' => ['Baguette','Baguette','Bageta','Багет','Багет'],
                'cushion' => ['Cushion','Kissen','Polštářek','Кушон','Кушон'],
                'heart' => ['Heart','Herz','Srdce','Сердце','Серце'],
                'oval' => ['Oval','Oval','Ovál','Овал','Овал']
            ]
        ];
        return $values[$key][$value][$language_index] ?? $value;
    }
}
