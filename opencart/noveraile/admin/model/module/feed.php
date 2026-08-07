<?php
namespace Opencart\Admin\Model\Extension\Noveraile\Module;

/**
 * Supplier feed importer.
 *
 * The supplier exports one CSV row per sellable combination: an article is
 * repeated for every gold caratage and diamond quality it is offered in, so
 * 6855 rows describe 476 products. Everything except caratage, quality and
 * price is constant inside an article, which makes `articul` the product key.
 *
 * Prices are taken from `priceShowroom`. That column is rounded to whole euros
 * and is NOT additively separable across caratage and quality — 1952 of 6813
 * combinations deviate, by up to 145 EUR — so two independent OpenCart options
 * cannot reproduce it. One combined option carrying an exact adjustment per
 * combination can, and keeps cart, checkout and totals on native pricing.
 *
 * The feed carries no names, no descriptions, no stock and no metal colour.
 * Catalogue copy is generated from the structured columns in all five store
 * languages; stock is treated as made to order.
 */
class Feed extends \Opencart\System\Engine\Model {
    public const LANGUAGES = ['en-gb', 'de-de', 'cs-cz', 'ru-ru', 'uk-ua'];
    private const IMAGE_BASE = 'catalog/6moments';
    private const IMAGE_COLUMNS = 11;
    private const MADE_TO_ORDER_QUANTITY = 999;
    private const REQUIRED_COLUMNS = ['articul', 'categoryenum', 'kindenum', 'caratagegoldenum', 'qualityenum', 'priceshowroom'];

    private const CATEGORIES = [
        'RING' => 'rings',
        'EAR_RING' => 'earrings',
        'NECKLACE' => 'necklaces',
        'BRACELET' => 'bracelets'
    ];

    /** Caratage enum to gold fineness, used for the catalogue fineness facet. */
    private const FINENESS = ['CT_9' => '375', 'CT_14' => '585', 'CT_18' => '750'];
    private const CARATAGE_LABEL = ['CT_9' => '9', 'CT_14' => '14', 'CT_18' => '18'];
    private const QUALITY_LABEL = [
        'D_VVS2' => 'D/VVS2', 'F_VS2' => 'F/VS2', 'G_VS2' => 'G/VS2', 'G_SI' => 'G/SI', 'LAB' => 'LAB'
    ];
    private const CARATAGE_ORDER = ['CT_9', 'CT_14', 'CT_18'];
    private const QUALITY_ORDER = ['G_SI', 'G_VS2', 'F_VS2', 'D_VVS2', 'LAB'];

    /** Product names per kind, in self::LANGUAGES order. */
    private const KINDS = [
        'SOLITAIRE_RING' => ['Solitaire Ring', 'Solitär-Ring', 'Solitérový prsten', 'Кольцо «Солитер»', 'Каблучка «Солітер»'],
        'SOLITAIRE_ETERNITY_RING' => ['Solitaire Eternity Ring', 'Solitär-Eternity-Ring', 'Solitérový eternity prsten', 'Кольцо «Солитер Вечность»', 'Каблучка «Солітер Вічність»'],
        'ETERNITY_RING' => ['Eternity Ring', 'Eternity-Ring', 'Eternity prsten', 'Кольцо «Вечность»', 'Каблучка «Вічність»'],
        'ILLUSION_RING' => ['Illusion Ring', 'Illusion-Ring', 'Iluzní prsten', 'Кольцо «Иллюзия»', 'Каблучка «Ілюзія»'],
        'MINIMALISM_RING' => ['Minimalist Ring', 'Minimalistischer Ring', 'Minimalistický prsten', 'Кольцо «Минимализм»', 'Каблучка «Мінімалізм»'],
        'SOLITAIRE_BUTTERFLY_EAR_RING' => ['Solitaire Butterfly Earrings', 'Solitär-Ohrringe Butterfly', 'Solitérové náušnice Motýl', 'Серьги «Солитер Бабочка»', 'Сережки «Солітер Метелик»'],
        'SOLITAIRE_ENGLISH_LOCK_EAR_RING' => ['Solitaire Earrings with English Lock', 'Solitär-Ohrringe mit Brisur', 'Solitérové náušnice s anglickým zapínáním', 'Серьги «Солитер» с английским замком', 'Сережки «Солітер» з англійським замком'],
        'SOLITAIRE_ETERNITY_EAR_RING' => ['Solitaire Eternity Earrings', 'Solitär-Eternity-Ohrringe', 'Solitérové eternity náušnice', 'Серьги «Солитер Вечность»', 'Сережки «Солітер Вічність»'],
        'ETERNITY_EAR_RING' => ['Eternity Earrings', 'Eternity-Ohrringe', 'Eternity náušnice', 'Серьги «Вечность»', 'Сережки «Вічність»'],
        'ILLUSION_EAR_RING' => ['Illusion Earrings', 'Illusion-Ohrringe', 'Iluzní náušnice', 'Серьги «Иллюзия»', 'Сережки «Ілюзія»'],
        'MINIMALISM_EAR_RING' => ['Minimalist Earrings', 'Minimalistische Ohrringe', 'Minimalistické náušnice', 'Серьги «Минимализм»', 'Сережки «Мінімалізм»'],
        'SOLITAIRE_NECKLACE' => ['Solitaire Necklace', 'Solitär-Collier', 'Solitérový náhrdelník', 'Ожерелье «Солитер»', 'Кольє «Солітер»'],
        'SOLITAIRE_ETERNITY_NECKLACE' => ['Solitaire Eternity Necklace', 'Solitär-Eternity-Collier', 'Solitérový eternity náhrdelník', 'Ожерелье «Солитер Вечность»', 'Кольє «Солітер Вічність»'],
        'ILLUSION_NECKLACE' => ['Illusion Necklace', 'Illusion-Collier', 'Iluzní náhrdelník', 'Ожерелье «Иллюзия»', 'Кольє «Ілюзія»'],
        'MINIMALISM_NECKLACE' => ['Minimalist Necklace', 'Minimalistisches Collier', 'Minimalistický náhrdelník', 'Ожерелье «Минимализм»', 'Кольє «Мінімалізм»'],
        'MINIMALISM_BRACELET' => ['Minimalist Bracelet', 'Minimalistisches Armband', 'Minimalistický náramek', 'Браслет «Минимализм»', 'Браслет «Мінімалізм»']
    ];

    private const SHAPES = [
        'ROUND' => ['Round', 'Rund', 'Kulatý', 'Круглая', 'Кругла'],
        'PRINCESS' => ['Princess', 'Prinzess', 'Princess', 'Принцесса', 'Принцеса'],
        'MARQUISE' => ['Marquise', 'Marquise', 'Markýza', 'Маркиз', 'Маркіз'],
        'BAGUETTE' => ['Baguette', 'Baguette', 'Bageta', 'Багет', 'Багет'],
        'HEART' => ['Heart', 'Herz', 'Srdce', 'Сердце', 'Серце'],
        'OVAL' => ['Oval', 'Oval', 'Ovál', 'Овал', 'Овал'],
        'PEAR' => ['Pear', 'Tropfen', 'Hruška', 'Груша', 'Груша']
    ];

    private const COLLECTIONS = [
        'CLASSIC' => ['Classic', 'Classic', 'Classic', 'Classic', 'Classic'],
        'MINIMALISM' => ['Minimalism', 'Minimalism', 'Minimalism', 'Minimalism', 'Minimalism'],
        'ILUZJA' => ['Iluzja', 'Iluzja', 'Iluzja', 'Iluzja', 'Iluzja'],
        'ETERNITY' => ['Eternity', 'Eternity', 'Eternity', 'Eternity', 'Eternity'],
        'FANCY_CUT' => ['Fancy Cut', 'Fancy Cut', 'Fancy Cut', 'Fancy Cut', 'Fancy Cut'],
        'PURE_LOVE' => ['Pure Love', 'Pure Love', 'Pure Love', 'Pure Love', 'Pure Love'],
        'NIGHT_SKY' => ['Night Sky', 'Night Sky', 'Night Sky', 'Night Sky', 'Night Sky']
    ];

    private const DELIVERY = ['THREE_DAYS' => 3, 'TEN_DAYS' => 10];

    /** Attributes the feed fills, added to the existing jewelry attribute group. */
    private const FEED_ATTRIBUTES = [
        'stone_count' => ['Stone count', 'Anzahl der Steine', 'Počet kamenů', 'Количество камней', 'Кількість каменів'],
        'central_carat' => ['Central stone carat', 'Karat des Mittelsteins', 'Karát středového kamene', 'Каратность центрального камня', 'Каратність центрального каменю'],
        'collection' => ['Collection', 'Kollektion', 'Kolekce', 'Коллекция', 'Колекція'],
        'certificate' => ['Certificate', 'Zertifikat', 'Certifikát', 'Сертификат', 'Сертифікат'],
        'article' => ['Article number', 'Artikelnummer', 'Číslo artiklu', 'Артикул', 'Артикул'],
        'production_time' => ['Production time', 'Produktionszeit', 'Doba výroby', 'Срок изготовления', 'Термін виготовлення']
    ];

    private array $language_cache = [];
    private array $attribute_cache = [];
    private array $option_cache = [];

    public function install(): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "noveraile_feed_run` (`run_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `filename` VARCHAR(190) NOT NULL DEFAULT '', `total` INT UNSIGNED NOT NULL DEFAULT 0, `processed` INT UNSIGNED NOT NULL DEFAULT 0, `created` INT UNSIGNED NOT NULL DEFAULT 0, `updated` INT UNSIGNED NOT NULL DEFAULT 0, `failed` INT UNSIGNED NOT NULL DEFAULT 0, `images` INT UNSIGNED NOT NULL DEFAULT 0, `retired` INT UNSIGNED NOT NULL DEFAULT 0, `rows_total` INT UNSIGNED NOT NULL DEFAULT 0, `status` VARCHAR(16) NOT NULL DEFAULT 'queued', `message` TEXT NOT NULL, `date_added` DATETIME NOT NULL, `date_modified` DATETIME NOT NULL, PRIMARY KEY (`run_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "noveraile_feed_item` (`item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `run_id` INT UNSIGNED NOT NULL, `articul` VARCHAR(64) NOT NULL, `payload` MEDIUMTEXT NOT NULL, `status` TINYINT NOT NULL DEFAULT 0, `message` VARCHAR(255) NOT NULL DEFAULT '', PRIMARY KEY (`item_id`), KEY `run_status` (`run_id`, `status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "noveraile_feed_media` (`media_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `url_hash` CHAR(40) NOT NULL, `path` VARCHAR(255) NOT NULL, `date_added` DATETIME NOT NULL, PRIMARY KEY (`media_id`), UNIQUE KEY `url_hash` (`url_hash`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "noveraile_feed_product` (`product_id` INT UNSIGNED NOT NULL, `articul` VARCHAR(64) NOT NULL, `video` VARCHAR(500) NOT NULL DEFAULT '', `run_id` INT UNSIGNED NOT NULL, `date_modified` DATETIME NOT NULL, PRIMARY KEY (`product_id`), UNIQUE KEY `articul` (`articul`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Installations created before the video column shipped are upgraded in place.
        $columns = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "noveraile_feed_product` LIKE 'video'");
        if (!$columns->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "noveraile_feed_product` ADD `video` VARCHAR(500) NOT NULL DEFAULT '' AFTER `articul`");
        }
    }

    // ---------------------------------------------------------------- queueing

    /**
     * Read the supplier CSV, group it by article and queue one job per product.
     * Parsing is separated from writing so a 10 MB feed never has to finish
     * inside a single PHP request.
     */
    public function queue(string $path, string $filename): array {
        $this->install();

        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new \RuntimeException('The feed file could not be opened.');
        }

        try {
            $header = $this->readHeader($handle);
            $groups = [];
            $line = 1;
            $rows = 0;

            while (($values = fgetcsv($handle, 0, $header['delimiter'], '"', '')) !== false) {
                $line++;
                if (count(array_filter($values, static fn($value): bool => trim((string)$value) !== '')) === 0) continue;
                if (count($values) > count($header['columns'])) {
                    throw new \RuntimeException(sprintf('Row %d has more columns than the header.', $line));
                }

                $row = array_combine($header['columns'], array_pad($values, count($header['columns']), ''));
                $articul = trim((string)$row['articul']);
                if ($articul === '') {
                    throw new \RuntimeException(sprintf('Row %d has no articul.', $line));
                }
                if (function_exists('mb_strlen') ? mb_strlen($articul) > 64 : strlen($articul) > 64) {
                    throw new \RuntimeException(sprintf('Row %d: articul is longer than 64 characters.', $line));
                }

                $groups[$articul][] = $this->normalizeRow($row, $line);
                $rows++;
                if (count($groups) > 20000) {
                    throw new \RuntimeException('The feed contains more than 20000 articles.');
                }
            }
        } finally {
            fclose($handle);
        }

        if (!$groups) {
            throw new \RuntimeException('The feed does not contain any product rows.');
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "noveraile_feed_run` SET `status` = 'cancelled', `date_modified` = NOW() WHERE `status` IN ('queued','running')");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "noveraile_feed_run` SET `filename` = '" . $this->db->escape(substr($filename, 0, 190)) . "', `total` = '" . count($groups) . "', `rows_total` = '" . (int)$rows . "', `status` = 'running', `message` = '', `date_added` = NOW(), `date_modified` = NOW()");
        $run_id = (int)$this->db->getLastId();

        $batch = [];
        foreach ($groups as $articul => $group) {
            $batch[] = "('" . (int)$run_id . "','" . $this->db->escape($articul) . "','" . $this->db->escape(json_encode($group, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "')";
            if (count($batch) >= 100) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "noveraile_feed_item` (`run_id`, `articul`, `payload`) VALUES " . implode(',', $batch));
                $batch = [];
            }
        }
        if ($batch) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "noveraile_feed_item` (`run_id`, `articul`, `payload`) VALUES " . implode(',', $batch));
        }

        return ['run_id' => $run_id, 'total' => count($groups), 'rows' => $rows];
    }

    private function readHeader($handle): array {
        $first_line = (string)fgets($handle);
        if ($first_line === '') {
            throw new \RuntimeException('The feed file is empty.');
        }
        $counts = [',' => substr_count($first_line, ','), ';' => substr_count($first_line, ';'), "\t" => substr_count($first_line, "\t")];
        arsort($counts);
        $delimiter = (string)array_key_first($counts);

        rewind($handle);
        $columns = fgetcsv($handle, 0, $delimiter, '"', '');
        if (!is_array($columns)) {
            throw new \RuntimeException('The feed header could not be read.');
        }
        $columns = array_map(static function ($value): string {
            return strtolower((string)preg_replace('/^\xEF\xBB\xBF/', '', trim((string)$value)));
        }, $columns);

        if (count($columns) !== count(array_unique($columns))) {
            throw new \RuntimeException('The feed header contains duplicate columns.');
        }
        $missing = array_diff(self::REQUIRED_COLUMNS, $columns);
        if ($missing) {
            throw new \RuntimeException('The feed is missing required columns: ' . implode(', ', $missing) . '.');
        }

        return ['columns' => $columns, 'delimiter' => $delimiter];
    }

    /** Keep only the columns the importer uses, normalised and length-checked. */
    private function normalizeRow(array $row, int $line): array {
        $images = [];
        for ($index = 1; $index <= self::IMAGE_COLUMNS; $index++) {
            $url = $this->supplierUrl((string)($row['imagelink' . $index] ?? ''), $line);
            if ($url !== '') $images[] = $url;
        }

        $caratage = strtoupper(trim((string)$row['caratagegoldenum']));
        $quality = strtoupper(trim((string)$row['qualityenum']));
        if (!isset(self::FINENESS[$caratage])) {
            throw new \RuntimeException(sprintf('Row %d: unknown gold caratage "%s".', $line, $caratage));
        }
        if (!isset(self::QUALITY_LABEL[$quality])) {
            throw new \RuntimeException(sprintf('Row %d: unknown diamond quality "%s".', $line, $quality));
        }

        return [
            'line' => $line,
            'category' => strtoupper(trim((string)$row['categoryenum'])),
            'kind' => strtoupper(trim((string)$row['kindenum'])),
            // The supplier double-quotes this value inside the CSV field.
            'shape' => strtoupper(trim((string)($row['shapeenum'] ?? ''), " \t\n\r\0\x0B\"'")),
            'caratage' => $caratage,
            'quality' => $quality,
            'price' => $this->positiveDecimal($row['priceshowroom'] ?? '', $line, 'priceShowroom'),
            'currency' => strtoupper(trim((string)($row['currencyshowroom'] ?? 'EUR'))),
            'carats' => $this->decimal($row['carats'] ?? '0'),
            'carats_central' => $this->decimal($row['caratscentral'] ?? '0'),
            'stones' => max(0, (int)($row['stonescount'] ?? 0)),
            'weight' => $this->decimal($row['weight'] ?? '0'),
            'collections' => array_values(array_filter(array_map(
                static fn(string $value): string => strtoupper(trim($value)),
                preg_split('/[;,]/', (string)($row['collections'] ?? '')) ?: []
            ))),
            'certificated' => in_array(strtolower(trim((string)($row['iscertificated'] ?? ''))), ['1', 'true', 'yes'], true),
            'delivery' => strtoupper(trim((string)($row['shippingoptionenum'] ?? ''))),
            'images' => $images,
            'video' => $this->supplierUrl((string)($row['videolink1'] ?? ''), $line)
        ];
    }

    /** Only plain https media URLs are accepted; anything else is a feed error. */
    private function supplierUrl(string $url, int $line): string {
        $url = trim($url);
        if ($url === '') return '';
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            throw new \RuntimeException(sprintf('Row %d: media links must be absolute https URLs.', $line));
        }
        if (strlen($url) > 500) {
            throw new \RuntimeException(sprintf('Row %d: media link is longer than 500 characters.', $line));
        }
        return $url;
    }

    private function decimal(mixed $value): float {
        $value = str_replace(',', '.', trim((string)$value));
        return is_numeric($value) ? (float)$value : 0.0;
    }

    private function positiveDecimal(mixed $value, int $line, string $field): float {
        $number = $this->decimal($value);
        if ($number <= 0) {
            throw new \RuntimeException(sprintf('Row %d: %s must be a positive number.', $line, $field));
        }
        return $number;
    }

    // -------------------------------------------------------------- processing

    public function getRun(int $run_id): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "noveraile_feed_run` WHERE `run_id` = '" . (int)$run_id . "' LIMIT 1");
        return $query->row ?: [];
    }

    public function getLatestRun(): array {
        $this->install();
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "noveraile_feed_run` ORDER BY `run_id` DESC LIMIT 1");
        return $query->row ?: [];
    }

    public function cancel(int $run_id): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "noveraile_feed_run` SET `status` = 'cancelled', `date_modified` = NOW() WHERE `run_id` = '" . (int)$run_id . "' AND `status` IN ('queued','running')");
    }

    /**
     * Import the next slice of queued articles. Each call is sized to stay well
     * inside max_execution_time even though every article downloads up to
     * eleven images from the supplier CDN.
     */
    public function process(int $run_id, int $limit): array {
        $run = $this->getRun($run_id);
        if (!$run) {
            throw new \RuntimeException('Unknown import run.');
        }
        if ($run['status'] !== 'running') {
            return $this->progress($run_id);
        }

        $limit = max(1, min(25, $limit));
        $items = $this->db->query("SELECT `item_id`, `articul`, `payload` FROM `" . DB_PREFIX . "noveraile_feed_item` WHERE `run_id` = '" . (int)$run_id . "' AND `status` = '0' ORDER BY `item_id` LIMIT " . $limit)->rows;

        if (!$items) {
            return $this->finish($run_id);
        }

        $created = 0;
        $updated = 0;
        $failed = 0;
        $images = 0;

        foreach ($items as $item) {
            $rows = json_decode((string)$item['payload'], true);
            try {
                if (!is_array($rows) || !$rows) {
                    throw new \RuntimeException('The queued payload is unreadable.');
                }
                $result = $this->writeProduct((string)$item['articul'], $rows, (int)$run_id);
                $images += (int)$result['images'];
                if ($result['created']) $created++; else $updated++;
                $this->db->query("UPDATE `" . DB_PREFIX . "noveraile_feed_item` SET `status` = '1', `message` = '' WHERE `item_id` = '" . (int)$item['item_id'] . "'");
            } catch (\Throwable $error) {
                $failed++;
                $this->db->query("UPDATE `" . DB_PREFIX . "noveraile_feed_item` SET `status` = '2', `message` = '" . $this->db->escape(substr($error->getMessage(), 0, 255)) . "' WHERE `item_id` = '" . (int)$item['item_id'] . "'");
            }
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "noveraile_feed_run` SET `processed` = `processed` + '" . count($items) . "', `created` = `created` + '" . $created . "', `updated` = `updated` + '" . $updated . "', `failed` = `failed` + '" . $failed . "', `images` = `images` + '" . $images . "', `date_modified` = NOW() WHERE `run_id` = '" . (int)$run_id . "'");

        $remaining = (int)($this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "noveraile_feed_item` WHERE `run_id` = '" . (int)$run_id . "' AND `status` = '0'")->row['total'] ?? 0);
        if (!$remaining) {
            return $this->finish($run_id);
        }

        return $this->progress($run_id);
    }

    private function progress(int $run_id): array {
        $run = $this->getRun($run_id);
        $run['remaining'] = (int)($this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "noveraile_feed_item` WHERE `run_id` = '" . (int)$run_id . "' AND `status` = '0'")->row['total'] ?? 0);
        return $run;
    }

    /**
     * Close the run: articles that disappeared from the feed are disabled
     * rather than deleted, so their orders, reviews and URLs stay intact.
     */
    private function finish(int $run_id): array {
        $retired = 0;
        // Only articles absent from this feed are retired. An article that
        // failed mid-run is still on offer, so it keeps its previous run_id but
        // must not be taken off the storefront.
        $stale = $this->db->query("SELECT `fp`.`product_id` FROM `" . DB_PREFIX . "noveraile_feed_product` `fp` WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "noveraile_feed_item` `fi` WHERE `fi`.`run_id` = '" . (int)$run_id . "' AND `fi`.`articul` = `fp`.`articul`)")->rows;
        // The demo catalog shipped under the NVR- model prefix duplicates the
        // real assortment once a supplier feed is in place. It is only cleared
        // when the feed actually produced products, so a failed first run never
        // empties the storefront.
        $imported = (int)($this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "noveraile_feed_product`")->row['total'] ?? 0);
        if ($imported > 0) {
            foreach ($this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` LIKE 'NVR-%' AND `status` = '1'")->rows as $demo) {
                $stale[] = $demo;
            }
        }

        foreach ($stale as $product) {
            $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `status` = '0', `date_modified` = NOW() WHERE `product_id` = '" . (int)$product['product_id'] . "'");
            $retired++;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "noveraile_feed_run` SET `status` = 'done', `retired` = '" . (int)$retired . "', `date_modified` = NOW() WHERE `run_id` = '" . (int)$run_id . "'");
        $this->cache->delete('product');
        $this->cache->delete('category');

        return $this->progress($run_id);
    }

    // ------------------------------------------------------------------ writer

    private function writeProduct(string $articul, array $rows, int $run_id): array {
        $first = $rows[0];
        $languages = $this->languages();
        if (!$languages) {
            throw new \RuntimeException('No store languages are installed.');
        }
        if (!isset(self::CATEGORIES[$first['category']])) {
            throw new \RuntimeException(sprintf('Unknown category "%s".', $first['category']));
        }
        if (!isset(self::KINDS[$first['kind']])) {
            throw new \RuntimeException(sprintf('Unknown kind "%s".', $first['kind']));
        }

        $prices = [];
        foreach ($rows as $row) {
            $prices[$row['caratage'] . '|' . $row['quality']] = (float)$row['price'];
        }
        $base = min($prices);

        $existing = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "noveraile_feed_product` WHERE `articul` = '" . $this->db->escape($articul) . "' LIMIT 1");
        $product_id = $existing->num_rows ? (int)$existing->row['product_id'] : 0;
        if (!$product_id) {
            $legacy = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` = '" . $this->db->escape($articul) . "' LIMIT 1");
            $product_id = $legacy->num_rows ? (int)$legacy->row['product_id'] : 0;
        }
        $created = !$product_id;

        $media = $this->collectMedia($first['images']);
        $weight_kg = round(((float)$first['weight']) / 1000, 6);

        // New rows go through the core model so every column the running
        // OpenCart build expects is populated; the update below then applies the
        // feed values, and is also the whole write path for existing products.
        if (!$product_id) {
            $product_id = $this->createProductRow($articul);
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET "
            . "`model` = '" . $this->db->escape($articul) . "'"
            . ", `quantity` = '" . self::MADE_TO_ORDER_QUANTITY . "'"
            . ", `subtract` = '0'"
            . ", `minimum` = '1'"
            . ", `stock_status_id` = '" . (int)$this->config->get('config_stock_status_id') . "'"
            . ", `shipping` = '1'"
            . ", `price` = '" . (float)$base . "'"
            . ", `points` = '0'"
            . ", `weight` = '" . $weight_kg . "'"
            . ", `weight_class_id` = '" . (int)$this->config->get('config_weight_class_id') . "'"
            . ", `status` = '1'"
            . ", `tax_class_id` = '0'"
            . ", `image` = '" . $this->db->escape((string)($media[0]['path'] ?? '')) . "'"
            . ", `date_available` = '" . $this->db->escape(date('Y-m-d')) . "'"
            . ", `date_modified` = NOW()"
            . " WHERE `product_id` = '" . $product_id . "'");

        $this->writeDescriptions($product_id, $articul, $rows, $languages);
        $this->writeStoreAndCategory($product_id, self::CATEGORIES[$first['category']]);
        $this->writeSku($product_id, $articul);
        $this->writeImages($product_id, $media);
        $this->writeAttributes($product_id, $articul, $rows, $languages);
        $this->writeOptions($product_id, $rows, $base, $first['category']);
        $this->writeSeoUrls($product_id, $articul, $rows, $languages);

        $this->db->query("REPLACE INTO `" . DB_PREFIX . "noveraile_feed_product` SET `product_id` = '" . $product_id . "', `articul` = '" . $this->db->escape($articul) . "', `video` = '" . $this->db->escape((string)$first['video']) . "', `run_id` = '" . (int)$run_id . "', `date_modified` = NOW()");

        return ['created' => $created, 'images' => count($media), 'product_id' => $product_id];
    }

    /**
     * Create the bare product row with the core model, which knows the column
     * set of the OpenCart build in use. Everything meaningful is written by the
     * feed writers straight afterwards.
     */
    private function createProductRow(string $articul): int {
        $this->load->model('catalog/product');

        return (int)$this->model_catalog_product->addProduct([
            'master_id' => 0, 'model' => $articul, 'sku' => $articul, 'upc' => '', 'ean' => '', 'jan' => '',
            'isbn' => '', 'mpn' => '', 'location' => '', 'variant' => [], 'override' => [],
            'quantity' => self::MADE_TO_ORDER_QUANTITY, 'minimum' => 1, 'subtract' => 0,
            'stock_status_id' => (int)$this->config->get('config_stock_status_id'),
            'date_available' => date('Y-m-d'), 'manufacturer_id' => 0, 'shipping' => 1,
            'price' => 0, 'points' => 0, 'weight' => 0,
            'weight_class_id' => (int)$this->config->get('config_weight_class_id'),
            'length' => 0, 'width' => 0, 'height' => 0,
            'length_class_id' => (int)$this->config->get('config_length_class_id'),
            'status' => 0, 'tax_class_id' => 0, 'sort_order' => 0, 'image' => '',
            'product_description' => [], 'product_code' => [], 'product_category' => [],
            'product_store' => [0], 'product_seo_url' => [0 => []]
        ]);
    }

    /**
     * Every installed language gets a description row. A language the copy
     * templates do not cover falls back to English rather than being skipped,
     * because a product without a description row for the active language is
     * invisible on the storefront.
     */
    private function languages(): array {
        if ($this->language_cache) return $this->language_cache;
        foreach ($this->db->query("SELECT `language_id`, `code` FROM `" . DB_PREFIX . "language`")->rows as $language) {
            $this->language_cache[strtolower((string)$language['code'])] = (int)$language['language_id'];
        }
        return $this->language_cache;
    }

    private function languageIndex(string $code): int {
        $index = array_search($code, self::LANGUAGES, true);
        return $index === false ? 0 : (int)$index;
    }

    // ------------------------------------------------------------- description

    private function writeDescriptions(int $product_id, string $articul, array $rows, array $languages): void {
        $first = $rows[0];
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . $product_id . "'");

        foreach ($languages as $code => $language_id) {
            $index = $this->languageIndex($code);
            $name = $this->productName($first, $index);
            $name = $this->uniqueName($name, $product_id, $language_id, $articul);
            $description = $this->productDescription($articul, $rows, $index);
            $tags = $this->productTags($rows, $index);
            $meta_description = $this->metaDescription($first, $index, $name);

            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_description` SET `product_id` = '" . $product_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($name) . "', `description` = '" . $this->db->escape($description) . "', `tag` = '" . $this->db->escape($tags) . "', `meta_title` = '" . $this->db->escape($this->truncate($name . ' | ' . $this->brand(), 255)) . "', `meta_description` = '" . $this->db->escape($meta_description) . "', `meta_keyword` = ''");
        }
    }

    private function productName(array $first, int $index): string {
        $name = self::KINDS[$first['kind']][$index] ?? self::KINDS[$first['kind']][0];
        $carats = (float)$first['carats'];
        if ($carats > 0) {
            $name .= ', ' . $this->formatCarat($carats) . ' ct';
        }
        $shape = $first['shape'];
        if ($shape !== '' && $shape !== 'ROUND' && isset(self::SHAPES[$shape])) {
            $name .= ', ' . (self::SHAPES[$shape][$index] ?? self::SHAPES[$shape][0]);
        }
        return $this->truncate($name, 255);
    }

    /**
     * Names are generated, so two articles of the same kind and carat weight
     * collide. The article number disambiguates them for shoppers and search.
     */
    private function uniqueName(string $name, int $product_id, int $language_id, string $articul): string {
        $taken = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product_description` WHERE `language_id` = '" . (int)$language_id . "' AND `name` = '" . $this->db->escape($name) . "' AND `product_id` != '" . (int)$product_id . "' LIMIT 1");
        if (!$taken->num_rows) return $name;
        return $this->truncate($name . ' · ' . $articul, 255);
    }

    private function productDescription(string $articul, array $rows, int $index): string {
        $first = $rows[0];
        $labels = $this->labels($index);
        $intro = $this->intro($first, $index);

        $caratages = [];
        $qualities = [];
        foreach ($rows as $row) {
            $caratages[self::CARATAGE_LABEL[$row['caratage']]] = true;
            $qualities[self::QUALITY_LABEL[$row['quality']]] = true;
        }
        $caratages = array_keys($caratages);
        $qualities = array_keys($qualities);
        sort($caratages, SORT_NUMERIC);

        $specs = [];
        $collections = $this->collectionNames($first['collections'], $index);
        if ($collections) $specs[$labels['collection']] = implode(', ', $collections);
        if ($first['shape'] !== '' && isset(self::SHAPES[$first['shape']])) {
            $specs[$labels['shape']] = self::SHAPES[$first['shape']][$index] ?? self::SHAPES[$first['shape']][0];
        }
        if ((float)$first['carats'] > 0) $specs[$labels['carat']] = $this->formatCarat((float)$first['carats']) . ' ct';
        if ((float)$first['carats_central'] > 0) $specs[$labels['central']] = $this->formatCarat((float)$first['carats_central']) . ' ct';
        if ((int)$first['stones'] > 0) $specs[$labels['stones']] = (string)(int)$first['stones'];
        if ((float)$first['weight'] > 0) $specs[$labels['weight']] = $this->formatCarat((float)$first['weight']) . ' ' . $labels['gram'];
        $specs[$labels['gold']] = implode(' / ', $caratages) . ' ' . $labels['kt'];
        $specs[$labels['quality']] = implode(', ', $qualities);
        $specs[$labels['certificate']] = $first['certificated'] ? $labels['yes'] : $labels['no'];
        if (isset(self::DELIVERY[$first['delivery']])) {
            $specs[$labels['production']] = sprintf($labels['days'], self::DELIVERY[$first['delivery']]);
        }
        $specs[$labels['article']] = $articul;

        $items = '';
        foreach ($specs as $label => $value) {
            $items .= '<li><strong>' . $this->escapeHtml($label) . ':</strong> ' . $this->escapeHtml($value) . '</li>';
        }

        return '<p>' . $this->escapeHtml($intro) . '</p><ul class="six-spec-list">' . $items . '</ul><p>' . $this->escapeHtml($labels['made_to_order']) . '</p>';
    }

    private function intro(array $first, int $index): string {
        $kind = self::KINDS[$first['kind']][$index] ?? self::KINDS[$first['kind']][0];
        $templates = [
            '%s in 9, 14 or 18 carat gold, set with diamonds and made to order in the quality you choose.',
            '%s aus 9, 14 oder 18 Karat Gold, mit Diamanten besetzt und in der von Ihnen gewählten Qualität gefertigt.',
            '%s z 9, 14 nebo 18karátového zlata s diamanty, vyrobený na zakázku ve zvolené kvalitě.',
            '%s из золота 9, 14 или 18 карат с бриллиантами — изготавливается под заказ в выбранном вами качестве.',
            '%s із золота 9, 14 або 18 карат із діамантами — виготовляється на замовлення в обраній вами якості.'
        ];
        return sprintf($templates[$index] ?? $templates[0], $kind);
    }

    private function metaDescription(array $first, int $index, string $name): string {
        $templates = [
            '%s by %s. Choose 9, 14 or 18 carat gold and your diamond quality. Made to order.',
            '%s von %s. Wählen Sie 9, 14 oder 18 Karat Gold und Ihre Diamantqualität. Auf Bestellung gefertigt.',
            '%s od %s. Vyberte si 9, 14 nebo 18karátové zlato a kvalitu diamantu. Vyrobeno na zakázku.',
            '%s от %s. Выберите золото 9, 14 или 18 карат и качество бриллианта. Изготовление под заказ.',
            '%s від %s. Оберіть золото 9, 14 або 18 карат і якість діаманта. Виготовлення на замовлення.'
        ];
        return $this->truncate(sprintf($templates[$index] ?? $templates[0], $name, $this->brand()), 255);
    }

    private function productTags(array $rows, int $index): string {
        $first = $rows[0];
        $tags = [];
        $type = ['RING' => 'ring', 'EAR_RING' => 'earrings', 'NECKLACE' => 'necklace', 'BRACELET' => 'bracelet'];
        if (isset($type[$first['category']])) $tags[] = $type[$first['category']];
        foreach ($this->collectionNames($first['collections'], $index) as $collection) {
            $tags[] = $collection;
        }
        if (isset(self::DELIVERY[$first['delivery']])) {
            $tags[] = 'delivery-' . self::DELIVERY[$first['delivery']];
        }
        if ($first['shape'] !== '' && isset(self::SHAPES[$first['shape']])) {
            $tags[] = self::SHAPES[$first['shape']][$index] ?? self::SHAPES[$first['shape']][0];
        }
        $tags[] = 'diamond';
        $tags[] = 'stones-' . (int)$first['stones'];

        return $this->truncate(implode(',', array_unique($tags)), 255);
    }

    private function collectionNames(array $collections, int $index): array {
        $names = [];
        foreach ($collections as $collection) {
            if (isset(self::COLLECTIONS[$collection])) {
                $names[] = self::COLLECTIONS[$collection][$index] ?? self::COLLECTIONS[$collection][0];
            }
        }
        return array_values(array_unique($names));
    }

    private function labels(int $index): array {
        $sets = [
            ['collection' => 'Collection', 'shape' => 'Cut', 'carat' => 'Total carat weight', 'central' => 'Central stone', 'stones' => 'Stone count', 'weight' => 'Gold weight', 'gram' => 'g', 'gold' => 'Gold caratage', 'kt' => 'kt', 'quality' => 'Diamond quality', 'certificate' => 'Certificate', 'production' => 'Production time', 'article' => 'Article number', 'yes' => 'Yes', 'no' => 'No', 'days' => '%d days', 'made_to_order' => 'Every piece is made to order. Gold caratage and diamond quality are chosen above and change the final price.'],
            ['collection' => 'Kollektion', 'shape' => 'Schliff', 'carat' => 'Gesamtkaratgewicht', 'central' => 'Mittelstein', 'stones' => 'Anzahl der Steine', 'weight' => 'Goldgewicht', 'gram' => 'g', 'gold' => 'Goldlegierung', 'kt' => 'kt', 'quality' => 'Diamantqualität', 'certificate' => 'Zertifikat', 'production' => 'Produktionszeit', 'article' => 'Artikelnummer', 'yes' => 'Ja', 'no' => 'Nein', 'days' => '%d Tage', 'made_to_order' => 'Jedes Stück wird auf Bestellung gefertigt. Goldlegierung und Diamantqualität werden oben gewählt und bestimmen den Endpreis.'],
            ['collection' => 'Kolekce', 'shape' => 'Brus', 'carat' => 'Celková karátová hmotnost', 'central' => 'Středový kámen', 'stones' => 'Počet kamenů', 'weight' => 'Hmotnost zlata', 'gram' => 'g', 'gold' => 'Ryzost zlata', 'kt' => 'kt', 'quality' => 'Kvalita diamantu', 'certificate' => 'Certifikát', 'production' => 'Doba výroby', 'article' => 'Číslo artiklu', 'yes' => 'Ano', 'no' => 'Ne', 'days' => '%d dní', 'made_to_order' => 'Každý šperk se vyrábí na zakázku. Ryzost zlata a kvalitu diamantu zvolíte výše, obojí ovlivňuje výslednou cenu.'],
            ['collection' => 'Коллекция', 'shape' => 'Огранка', 'carat' => 'Общая каратность', 'central' => 'Центральный камень', 'stones' => 'Количество камней', 'weight' => 'Вес золота', 'gram' => 'г', 'gold' => 'Проба золота', 'kt' => 'карат', 'quality' => 'Качество бриллианта', 'certificate' => 'Сертификат', 'production' => 'Срок изготовления', 'article' => 'Артикул', 'yes' => 'Да', 'no' => 'Нет', 'days' => '%d дней', 'made_to_order' => 'Каждое изделие изготавливается под заказ. Проба золота и качество бриллианта выбираются выше и влияют на итоговую цену.'],
            ['collection' => 'Колекція', 'shape' => 'Огранка', 'carat' => 'Загальна каратність', 'central' => 'Центральний камінь', 'stones' => 'Кількість каменів', 'weight' => 'Вага золота', 'gram' => 'г', 'gold' => 'Проба золота', 'kt' => 'карат', 'quality' => 'Якість діаманта', 'certificate' => 'Сертифікат', 'production' => 'Термін виготовлення', 'article' => 'Артикул', 'yes' => 'Так', 'no' => 'Ні', 'days' => '%d днів', 'made_to_order' => 'Кожен виріб виготовляється на замовлення. Проба золота та якість діаманта обираються вище і впливають на підсумкову ціну.']
        ];
        return $sets[$index] ?? $sets[0];
    }

    private function formatCarat(float $value): string {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') ?: '0';
    }

    private function escapeHtml(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function truncate(string $value, int $length): string {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private function brand(): string {
        $brand = trim((string)($this->config->get('module_noveraile_brand_name') ?: $this->config->get('config_name')));
        return in_array($brand, ['', 'Your Store'], true) ? '6 Moments' : $brand;
    }

    // ------------------------------------------------------ stores, categories

    private function writeStoreAndCategory(int $product_id, string $slug): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_store` WHERE `product_id` = '" . $product_id . "'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET `product_id` = '" . $product_id . "', `store_id` = '0'");

        $category_id = $this->categoryId($slug);
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . $product_id . "'");
        if ($category_id) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = '" . $product_id . "', `category_id` = '" . $category_id . "'");
        }
    }

    /** Reuse the storefront categories created at install time, else create one. */
    private function categoryId(string $slug): int {
        $query = $this->db->query("SELECT CAST(`value` AS UNSIGNED) AS `category_id` FROM `" . DB_PREFIX . "seo_url` WHERE `key` = 'category_id' AND `keyword` LIKE 'noveraile-" . $this->db->escape($slug) . "-%' LIMIT 1");
        if ($query->num_rows && (int)$query->row['category_id']) {
            return (int)$query->row['category_id'];
        }

        $names = [
            'rings' => ['Rings', 'Ringe', 'Prsteny', 'Кольца', 'Каблучки'],
            'earrings' => ['Earrings', 'Ohrringe', 'Náušnice', 'Серьги', 'Сережки'],
            'necklaces' => ['Necklaces', 'Halsketten', 'Náhrdelníky', 'Подвески', 'Підвіски'],
            'bracelets' => ['Bracelets', 'Armbänder', 'Náramky', 'Браслеты', 'Браслети']
        ];
        if (!isset($names[$slug])) return 0;

        $this->db->query("INSERT INTO `" . DB_PREFIX . "category` SET `image` = '', `parent_id` = '0', `top` = '1', `column` = '1', `sort_order` = '1', `status` = '1', `date_added` = NOW(), `date_modified` = NOW()");
        $category_id = (int)$this->db->getLastId();
        $this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_store` SET `category_id` = '" . $category_id . "', `store_id` = '0'");

        foreach ($this->languages() as $code => $language_id) {
            $index = $this->languageIndex($code);
            $name = $names[$slug][$index] ?? $names[$slug][0];
            $this->db->query("INSERT INTO `" . DB_PREFIX . "category_description` SET `category_id` = '" . $category_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($name) . "', `description` = '', `meta_title` = '" . $this->db->escape($name . ' | ' . $this->brand()) . "', `meta_description` = '', `meta_keyword` = ''");
            $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET `store_id` = '0', `language_id` = '" . (int)$language_id . "', `key` = 'category_id', `value` = '" . $category_id . "', `keyword` = '" . $this->db->escape('noveraile-' . $slug . '-' . $code) . "', `sort_order` = '0'");
        }

        return $category_id;
    }

    private function writeSku(int $product_id, string $articul): void {
        if (!defined('VERSION') || version_compare(VERSION, '4.1.0.0', '>=')) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "product_code` WHERE `product_id` = '" . $product_id . "' AND `code` = 'sku'");
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_code` SET `product_id` = '" . $product_id . "', `code` = 'sku', `value` = '" . $this->db->escape($articul) . "'");
            return;
        }
        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `sku` = '" . $this->db->escape($articul) . "' WHERE `product_id` = '" . $product_id . "'");
    }

    // ------------------------------------------------------------------ images

    /**
     * Download every image once and reuse it across runs. Images are keyed by
     * URL, so re-importing the same feed costs no bandwidth.
     */
    private function collectMedia(array $urls): array {
        $media = [];
        foreach ($urls as $url) {
            $path = $this->downloadImage($url);
            if ($path !== '') {
                $media[] = ['url' => $url, 'path' => $path];
            }
        }
        return $media;
    }

    private function downloadImage(string $url): string {
        $hash = sha1($url);
        $cached = $this->db->query("SELECT `path` FROM `" . DB_PREFIX . "noveraile_feed_media` WHERE `url_hash` = '" . $this->db->escape($hash) . "' LIMIT 1");
        if ($cached->num_rows) {
            $path = (string)$cached->row['path'];
            if (is_file(DIR_IMAGE . $path)) {
                return $path;
            }
            $this->db->query("DELETE FROM `" . DB_PREFIX . "noveraile_feed_media` WHERE `url_hash` = '" . $this->db->escape($hash) . "'");
        }

        $body = $this->fetch($url);
        if ($body === '') return '';

        $extension = $this->imageExtension($body);
        if ($extension === '') {
            throw new \RuntimeException('The supplier returned a file that is not a supported image: ' . $url);
        }

        $relative = self::IMAGE_BASE . '/' . substr($hash, 0, 2) . '/' . $hash . '.' . $extension;
        $target = DIR_IMAGE . $relative;
        $directory = dirname($target);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('The image directory could not be created: ' . $directory);
        }
        if (file_put_contents($target, $body, LOCK_EX) === false) {
            throw new \RuntimeException('The image could not be written: ' . $relative);
        }

        $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "noveraile_feed_media` SET `url_hash` = '" . $this->db->escape($hash) . "', `path` = '" . $this->db->escape($relative) . "', `date_added` = NOW()");

        return $relative;
    }

    private function fetch(string $url): string {
        $curl = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'NOVERAILE feed importer'
        ];
        if (defined('CURLPROTO_HTTPS')) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        curl_setopt_array($curl, $options);
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $error !== '') {
            throw new \RuntimeException('The supplier media could not be downloaded: ' . $url);
        }
        if ($status !== 200) {
            throw new \RuntimeException(sprintf('The supplier returned HTTP %d for %s', $status, $url));
        }
        if (strlen((string)$body) > 16 * 1024 * 1024) {
            throw new \RuntimeException('The supplier media is larger than 16 MB: ' . $url);
        }

        return (string)$body;
    }

    /** Trust the bytes, not the URL or the declared content type. */
    private function imageExtension(string $body): string {
        $info = @getimagesizefromstring($body);
        if (is_array($info) && !empty($info['mime'])) {
            $types = ['image/webp' => 'webp', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            return $types[strtolower((string)$info['mime'])] ?? '';
        }
        // getimagesizefromstring predates WebP support in some PHP builds.
        if (strncmp($body, "RIFF", 4) === 0 && substr($body, 8, 4) === 'WEBP') {
            return 'webp';
        }
        return '';
    }

    private function writeImages(int $product_id, array $media): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_image` WHERE `product_id` = '" . $product_id . "'");
        $sort_order = 0;
        foreach (array_slice($media, 1) as $item) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_image` SET `product_id` = '" . $product_id . "', `image` = '" . $this->db->escape((string)$item['path']) . "', `sort_order` = '" . (int)$sort_order++ . "'");
        }
    }

    // -------------------------------------------------------------- attributes

    private function writeAttributes(int $product_id, string $articul, array $rows, array $languages): void {
        $first = $rows[0];
        $map = $this->attributes();

        $finenesses = [];
        $qualities = [];
        foreach ($rows as $row) {
            $finenesses[self::FINENESS[$row['caratage']]] = true;
            $qualities[self::QUALITY_LABEL[$row['quality']]] = true;
        }
        $finenesses = array_keys($finenesses);
        $qualities = array_keys($qualities);
        sort($finenesses, SORT_NUMERIC);
        usort($qualities, static function (string $left, string $right): int {
            $order = array_values(self::QUALITY_LABEL);
            return array_search($left, $order, true) <=> array_search($right, $order, true);
        });

        $values = [];
        // Multi-valued facets are stored comma separated; the storefront filter
        // matches individual members with FIND_IN_SET.
        $values['fineness'] = array_fill(0, count(self::LANGUAGES), implode(', ', $finenesses));
        $values['stone_quality'] = array_fill(0, count(self::LANGUAGES), implode(', ', $qualities));
        $values['gemstone'] = ['Diamond', 'Diamant', 'Diamant', 'Бриллиант', 'Діамант'];

        // An article is normally offered with both natural and lab-grown
        // stones, so origin is a list of what can be ordered, not one value.
        $offered = array_unique(array_column($rows, 'quality'));
        $lab = ['Lab-grown', 'Laborgezüchtet', 'Laboratorní', 'Лабораторный', 'Лабораторний'];
        $natural = ['Natural', 'Natürlich', 'Přírodní', 'Натуральный', 'Натуральний'];
        $has_lab = in_array('LAB', $offered, true);
        $has_natural = (bool)array_diff($offered, ['LAB']);
        $values['stone_origin'] = [];
        foreach (array_keys(self::LANGUAGES) as $index) {
            $parts = [];
            if ($has_natural) $parts[] = $natural[$index];
            if ($has_lab) $parts[] = $lab[$index];
            $values['stone_origin'][$index] = implode(', ', $parts);
        }
        if ($first['shape'] !== '' && isset(self::SHAPES[$first['shape']])) {
            $values['stone_shape'] = self::SHAPES[$first['shape']];
        }
        $values['carat'] = array_fill(0, count(self::LANGUAGES), $this->formatCarat((float)$first['carats']));
        if ((float)$first['carats_central'] > 0) {
            $values['central_carat'] = array_fill(0, count(self::LANGUAGES), $this->formatCarat((float)$first['carats_central']));
        }
        $values['stone_count'] = array_fill(0, count(self::LANGUAGES), (string)(int)$first['stones']);
        $values['article'] = array_fill(0, count(self::LANGUAGES), $articul);
        $values['style'] = $this->styleNames($first['kind']);

        $collections = [];
        foreach (self::LANGUAGES as $index => $code) {
            $collections[$index] = implode(', ', $this->collectionNames($first['collections'], $index));
        }
        if (trim($collections[0]) !== '') $values['collection'] = $collections;

        $values['certificate'] = $first['certificated']
            ? ['Yes', 'Ja', 'Ano', 'Да', 'Так']
            : ['No', 'Nein', 'Ne', 'Нет', 'Ні'];

        if (isset(self::DELIVERY[$first['delivery']])) {
            $days = self::DELIVERY[$first['delivery']];
            $values['production_time'] = [
                $days . ' days', $days . ' Tage', $days . ' dní', $days . ' дней', $days . ' днів'
            ];
        }

        $attribute_ids = array_values(array_filter(array_map(
            static fn(string $key): int => (int)($map[$key] ?? 0),
            array_keys($values)
        )));
        if ($attribute_ids) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = '" . $product_id . "' AND `attribute_id` IN (" . implode(',', $attribute_ids) . ")");
        }

        foreach ($values as $key => $translations) {
            $attribute_id = (int)($map[$key] ?? 0);
            if (!$attribute_id) continue;
            foreach ($languages as $code => $language_id) {
                $index = $this->languageIndex($code);
                $text = trim((string)($translations[$index] ?? $translations[0] ?? ''));
                if ($text === '') continue;
                $this->db->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET `product_id` = '" . $product_id . "', `attribute_id` = '" . $attribute_id . "', `language_id` = '" . (int)$language_id . "', `text` = '" . $this->db->escape($text) . "'");
            }
        }
    }

    private function styleNames(string $kind): array {
        $styles = [
            'SOLITAIRE' => ['Solitaire', 'Solitär', 'Solitér', 'Солитер', 'Солітер'],
            'ETERNITY' => ['Eternity', 'Eternity', 'Eternity', 'Вечность', 'Вічність'],
            'ILLUSION' => ['Illusion', 'Illusion', 'Iluze', 'Иллюзия', 'Ілюзія'],
            'MINIMALISM' => ['Minimalism', 'Minimalismus', 'Minimalismus', 'Минимализм', 'Мінімалізм']
        ];
        if (str_starts_with($kind, 'SOLITAIRE')) return $styles['SOLITAIRE'];
        if (str_starts_with($kind, 'ETERNITY')) return $styles['ETERNITY'];
        if (str_starts_with($kind, 'ILLUSION')) return $styles['ILLUSION'];
        return $styles['MINIMALISM'];
    }

    /**
     * Resolve the jewelry attribute ids, extending the group installed by the
     * suite with the extra facets this feed provides.
     */
    private function attributes(): array {
        if ($this->attribute_cache) return $this->attribute_cache;

        $map = $this->config->get('module_noveraile_attribute_map');
        if (!is_array($map)) {
            $decoded = json_decode((string)$map, true);
            $map = is_array($decoded) ? $decoded : [];
        }

        $group_id = (int)($map['group'] ?? 0);
        if (!$group_id) {
            $group = $this->db->query("SELECT `attribute_group_id` FROM `" . DB_PREFIX . "attribute_group_description` WHERE `name` = 'Jewelry specifications' LIMIT 1");
            $group_id = $group->num_rows ? (int)$group->row['attribute_group_id'] : 0;
        }
        if (!$group_id) {
            throw new \RuntimeException('The jewelry attribute group is missing. Reinstall the NOVERAILE module first.');
        }

        $languages = $this->languages();
        $changed = false;
        foreach (self::FEED_ATTRIBUTES as $key => $names) {
            if (!empty($map[$key])) continue;
            $existing = $this->db->query("SELECT `a`.`attribute_id` FROM `" . DB_PREFIX . "attribute` `a` INNER JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`ad`.`attribute_id` = `a`.`attribute_id`) WHERE `a`.`attribute_group_id` = '" . $group_id . "' AND `ad`.`name` = '" . $this->db->escape($names[0]) . "' LIMIT 1");
            if ($existing->num_rows) {
                $map[$key] = (int)$existing->row['attribute_id'];
                $changed = true;
                continue;
            }

            $this->db->query("INSERT INTO `" . DB_PREFIX . "attribute` SET `attribute_group_id` = '" . $group_id . "', `sort_order` = '" . (count($map) + 1) . "'");
            $attribute_id = (int)$this->db->getLastId();
            foreach ($languages as $code => $language_id) {
                $index = $this->languageIndex($code);
                $this->db->query("INSERT INTO `" . DB_PREFIX . "attribute_description` SET `attribute_id` = '" . $attribute_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($names[$index] ?? $names[0]) . "'");
            }
            $map[$key] = $attribute_id;
            $changed = true;
        }

        if ($changed) {
            $this->storeSetting('module_noveraile_attribute_map', json_encode($map));
        }

        $this->attribute_cache = $map;
        return $map;
    }

    // ----------------------------------------------------------------- options

    /**
     * One option carries the caratage/quality matrix. Two separate options
     * would have to price additively, which this feed is not.
     */
    private function writeOptions(int $product_id, array $rows, float $base, string $category): void {
        $combo = $this->comboOption();
        $ring_option_id = (int)$this->config->get('module_noveraile_ring_size_option_id');

        $this->db->query("DELETE `pov` FROM `" . DB_PREFIX . "product_option_value` `pov` WHERE `pov`.`product_id` = '" . $product_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_option` WHERE `product_id` = '" . $product_id . "'");

        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_option` SET `product_id` = '" . $product_id . "', `option_id` = '" . (int)$combo['option_id'] . "', `value` = '', `required` = '1'");
        $product_option_id = (int)$this->db->getLastId();

        $ordered = $rows;
        usort($ordered, static function (array $left, array $right): int {
            $caratage = array_search($left['caratage'], self::CARATAGE_ORDER, true) <=> array_search($right['caratage'], self::CARATAGE_ORDER, true);
            if ($caratage !== 0) return $caratage;
            return array_search($left['quality'], self::QUALITY_ORDER, true) <=> array_search($right['quality'], self::QUALITY_ORDER, true);
        });

        foreach ($ordered as $row) {
            $key = $row['caratage'] . '|' . $row['quality'];
            $option_value_id = (int)($combo['values'][$key] ?? 0);
            if (!$option_value_id) continue;
            $adjustment = round((float)$row['price'] - $base, 4);
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_option_value` SET `product_option_id` = '" . $product_option_id . "', `product_id` = '" . $product_id . "', `option_id` = '" . (int)$combo['option_id'] . "', `option_value_id` = '" . $option_value_id . "', `quantity` = '" . self::MADE_TO_ORDER_QUANTITY . "', `subtract` = '0', `price` = '" . $adjustment . "', `price_prefix` = '+', `points` = '0', `points_prefix` = '+', `weight` = '0', `weight_prefix` = '+'");
        }

        if ($category === 'RING' && $ring_option_id) {
            $sizes = $this->db->query("SELECT `option_value_id` FROM `" . DB_PREFIX . "option_value` WHERE `option_id` = '" . $ring_option_id . "' ORDER BY `sort_order`")->rows;
            if ($sizes) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "product_option` SET `product_id` = '" . $product_id . "', `option_id` = '" . $ring_option_id . "', `value` = '', `required` = '1'");
                $size_option_id = (int)$this->db->getLastId();
                foreach ($sizes as $size) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "product_option_value` SET `product_option_id` = '" . $size_option_id . "', `product_id` = '" . $product_id . "', `option_id` = '" . $ring_option_id . "', `option_value_id` = '" . (int)$size['option_value_id'] . "', `quantity` = '" . self::MADE_TO_ORDER_QUANTITY . "', `subtract` = '0', `price` = '0', `price_prefix` = '+', `points` = '0', `points_prefix` = '+', `weight` = '0', `weight_prefix` = '+'");
                }
            }
        }
    }

    /** The shared caratage/quality option, created once and reused. */
    private function comboOption(): array {
        if ($this->option_cache) return $this->option_cache;

        $stored = $this->config->get('module_noveraile_feed_option');
        $option = is_array($stored) ? $stored : json_decode((string)$stored, true);
        if (is_array($option) && !empty($option['option_id']) && !empty($option['values'])) {
            $exists = $this->db->query("SELECT `option_id` FROM `" . DB_PREFIX . "option` WHERE `option_id` = '" . (int)$option['option_id'] . "' LIMIT 1");
            if ($exists->num_rows) {
                $this->option_cache = $option;
                return $option;
            }
        }

        $languages = $this->languages();
        $names = ['Gold and diamond quality', 'Gold und Diamantqualität', 'Zlato a kvalita diamantu', 'Золото и качество бриллианта', 'Золото та якість діаманта'];

        $this->db->query("INSERT INTO `" . DB_PREFIX . "option` SET `type` = 'select', `sort_order` = '0'");
        $option_id = (int)$this->db->getLastId();
        foreach ($languages as $code => $language_id) {
            $index = $this->languageIndex($code);
            $this->db->query("INSERT INTO `" . DB_PREFIX . "option_description` SET `option_id` = '" . $option_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($names[$index] ?? $names[0]) . "'");
        }

        $units = ['kt', 'kt', 'kt', 'карат', 'карат'];
        $values = [];
        $sort_order = 0;
        foreach (self::CARATAGE_ORDER as $caratage) {
            foreach (self::QUALITY_ORDER as $quality) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "option_value` SET `option_id` = '" . $option_id . "', `image` = '', `sort_order` = '" . (int)$sort_order++ . "'");
                $option_value_id = (int)$this->db->getLastId();
                foreach ($languages as $code => $language_id) {
                    $index = $this->languageIndex($code);
                    $label = self::CARATAGE_LABEL[$caratage] . ' ' . ($units[$index] ?? $units[0]) . ' · ' . self::QUALITY_LABEL[$quality];
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "option_value_description` SET `option_value_id` = '" . $option_value_id . "', `language_id` = '" . (int)$language_id . "', `option_id` = '" . $option_id . "', `name` = '" . $this->db->escape($label) . "'");
                }
                $values[$caratage . '|' . $quality] = $option_value_id;
            }
        }

        $option = ['option_id' => $option_id, 'values' => $values];
        $this->storeSetting('module_noveraile_feed_option', json_encode($option));
        $this->option_cache = $option;

        return $option;
    }

    /**
     * Persist a setting whether or not a row already exists. The core
     * `editValue` helper only issues an UPDATE, so a first-time key would be
     * silently dropped — and the shared option would then be recreated for
     * every single article.
     */
    private function storeSetting(string $key, string $value): void {
        $store_id = (int)$this->config->get('config_store_id');
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '" . $store_id . "' AND `key` = '" . $this->db->escape($key) . "'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = '" . $store_id . "', `code` = 'module_noveraile', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "', `serialized` = '0'");
        // Keep the in-request config in step so later articles in this batch
        // reuse the option instead of creating another one.
        $this->config->set($key, $value);
    }

    // -------------------------------------------------------------- seo urls

    private function writeSeoUrls(int $product_id, string $articul, array $rows, array $languages): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `key` = 'product_id' AND `value` = '" . $product_id . "'");

        foreach ($languages as $code => $language_id) {
            $index = $this->languageIndex($code);
            $keyword = $this->slug($this->productName($rows[0], $index) . '-' . $articul);
            if ($keyword === '') $keyword = 'product-' . $product_id;
            $keyword = $this->uniqueKeyword($keyword . '-' . $code, $product_id);
            $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET `store_id` = '0', `language_id` = '" . (int)$language_id . "', `key` = 'product_id', `value` = '" . $product_id . "', `keyword` = '" . $this->db->escape($keyword) . "', `sort_order` = '0'");
        }
    }

    private function uniqueKeyword(string $keyword, int $product_id): string {
        $candidate = $keyword;
        $suffix = 1;
        while (true) {
            $taken = $this->db->query("SELECT `seo_url_id` FROM `" . DB_PREFIX . "seo_url` WHERE `keyword` = '" . $this->db->escape($candidate) . "' AND NOT (`key` = 'product_id' AND `value` = '" . (int)$product_id . "') LIMIT 1");
            if (!$taken->num_rows) return $candidate;
            $candidate = $keyword . '-' . ++$suffix;
            if ($suffix > 50) return $keyword . '-' . $product_id;
        }
    }

    private function slug(string $value): string {
        $value = $this->transliterate($value);
        $value = strtolower($value);
        $value = (string)preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    private function transliterate(string $value): string {
        $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'h','ґ'=>'g','д'=>'d','е'=>'e','є'=>'ie','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'y','і'=>'i','ї'=>'i','й'=>'i','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'iu','я'=>'ia',
            'ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','á'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ň'=>'n','ó'=>'o','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ý'=>'y','ž'=>'z'
        ];
        $lower = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        return strtr($lower, $map);
    }
}
