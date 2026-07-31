<?php
namespace Opencart\Catalog\Model\Extension\Sixmoments;

class Catalog extends \Opencart\System\Engine\Model {
    public function getProductIds(array $filter): array {
        $sql = $this->baseSql($filter, false);
        $sorts = [
            'popular' => 'COALESCE((SELECT SUM(`op`.`quantity`) FROM `' . DB_PREFIX . 'order_product` `op` WHERE `op`.`product_id` = `p`.`product_id`), 0) DESC, `p`.`sort_order` ASC',
            'price-asc' => '`effective_price` ASC, `pd`.`name` ASC',
            'price-desc' => '`effective_price` DESC, `pd`.`name` ASC',
            'newest' => '`p`.`date_added` DESC, `p`.`product_id` DESC'
        ];
        $sql .= ' ORDER BY ' . ($sorts[$filter['sort'] ?? 'popular'] ?? $sorts['popular']);
        $sql .= ' LIMIT ' . max(0, (int)($filter['start'] ?? 0)) . ',' . max(1, min(48, (int)($filter['limit'] ?? 12)));

        return array_map('intval', array_column($this->db->query($sql)->rows, 'product_id'));
    }

    public function getTotalProducts(array $filter): int {
        $query = $this->db->query($this->baseSql($filter, true));
        return (int)($query->row['total'] ?? 0);
    }

    public function getCategories(): array {
        $sql = "SELECT DISTINCT `c`.`category_id`, `cd`.`name`, `c`.`sort_order` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`category_id` = `c`.`category_id`) INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `p2c`.`product_id` AND `p`.`status` = '1' AND `p`.`model` LIKE '6M-%') WHERE `c`.`status` = '1' ORDER BY `c`.`sort_order` ASC, `cd`.`name` ASC";
        return $this->db->query($sql)->rows;
    }

    private function baseSql(array $filter, bool $count): string {
        $customer_group_id = (int)$this->config->get('config_customer_group_id');
        $special = "(SELECT `product_id`, MIN(`price`) AS `price` FROM `" . DB_PREFIX . "product_discount` WHERE `customer_group_id` = '" . $customer_group_id . "' AND `special` = '1' AND (`date_start` = '0000-00-00' OR `date_start` <= NOW()) AND (`date_end` = '0000-00-00' OR `date_end` >= NOW()) GROUP BY `product_id`)";
        $select = $count ? 'SELECT COUNT(DISTINCT `p`.`product_id`) AS `total`' : 'SELECT DISTINCT `p`.`product_id`, COALESCE(`ps`.`price`, `p`.`price`) AS `effective_price`';
        $sql = $select . " FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "') LEFT JOIN " . $special . " `ps` ON (`ps`.`product_id` = `p`.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`product_id` = `p`.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_code` `pc` ON (`pc`.`product_id` = `p`.`product_id`) WHERE `p`.`status` = '1' AND `p`.`date_available` <= NOW() AND `p`.`model` LIKE '6M-%'";

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
                $sql .= " AND (`p`.`model` LIKE '6M-" . $prefixes[$type] . "-%' OR FIND_IN_SET('" . $tags[$type] . "', REPLACE(LOWER(`pd`.`tag`), ' ', '')))";
            }
        }
        foreach (['moment', 'metal', 'fineness', 'stone', 'delivery'] as $key) {
            if (!empty($filter[$key])) {
                $tag = $this->db->escape(strtolower(preg_replace('/[^a-z0-9-]/i', '', (string)$filter[$key])));
                $sql .= " AND FIND_IN_SET('" . $tag . "', REPLACE(LOWER(`pd`.`tag`), ' ', ''))";
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
}
