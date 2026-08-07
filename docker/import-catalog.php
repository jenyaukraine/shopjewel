<?php
/**
 * Imports the supplier catalog feed into the store.
 *
 * The run is idempotent and resumable, so it is safe to call on every
 * deployment: an unchanged feed with every image already on disk finishes
 * without touching the catalog.
 *
 *   noveraile-import-catalog [--budget=SECONDS] [--force] [--status]
 *                           [--no-images] [--images-only] [--if-needed]
 *
 * --budget bounds the image download only. Products are always written in full
 * because a half written catalog would be visible in the storefront.
 */
declare(strict_types=1);

define('VERSION', (string)(getenv('OPENCART_VERSION') ?: '4.1.0.3'));

$openCartRoot = rtrim((string)(getenv('OPENCART_ROOT') ?: '/var/www/html'), '/\\');
require_once $openCartRoot . '/admin/config.php';
require_once DIR_SYSTEM . 'startup.php';

$autoloader = new \Opencart\System\Engine\Autoloader();
$autoloader->register('Opencart\\Admin', DIR_APPLICATION);
$autoloader->register('Opencart\\System', DIR_SYSTEM);
$autoloader->register('Opencart\\Admin\\Model\\Extension\\Noveraile', DIR_EXTENSION . 'noveraile/admin/model/');

require_once DIR_SYSTEM . 'vendor.php';

$registry = new \Opencart\System\Engine\Registry();
$registry->set('autoloader', $autoloader);

$config = new \Opencart\System\Engine\Config();
$config->addPath(DIR_CONFIG);
$config->load('default');
$config->load('admin');
$config->set('application', APPLICATION);
$registry->set('config', $config);

$db = new \Opencart\System\Library\DB(
    (string)$config->get('db_engine'),
    (string)$config->get('db_hostname'),
    (string)$config->get('db_username'),
    (string)$config->get('db_password'),
    (string)$config->get('db_database'),
    (string)$config->get('db_port'),
    (string)($config->get('db_ssl_key') ?? ''),
    (string)($config->get('db_ssl_cert') ?? ''),
    (string)($config->get('db_ssl_ca') ?? '')
);
$registry->set('db', $db);
$registry->set('cache', new \Opencart\System\Library\Cache(
    (string)$config->get('cache_engine'),
    (int)$config->get('cache_expire')
));

foreach ($db->query("SELECT `key`, `value`, `serialized` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0'")->rows as $setting) {
    $config->set($setting['key'], $setting['serialized'] ? json_decode($setting['value'], true) : $setting['value']);
}

$languageCode = (string)($config->get('config_language_admin') ?: $config->get('config_language'));
$language = $db->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` = '" . $db->escape($languageCode) . "' LIMIT 1");

if (!$language->num_rows) {
    fwrite(STDERR, "OpenCart default language is not available.\n");
    exit(1);
}

$config->set('config_language_id', (int)$language->row['language_id']);

$registry->set('event', new \Opencart\System\Engine\Event($registry));
$registry->set('factory', new \Opencart\System\Engine\Factory($registry));
$registry->set('load', new \Opencart\System\Engine\Loader($registry));

$options = ['budget' => 0.0, 'force' => false, 'products' => true, 'images' => true, 'if_needed' => false];
$status = false;

foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--force') $options['force'] = true;
    elseif ($argument === '--status') $status = true;
    elseif ($argument === '--if-needed') $options['if_needed'] = true;
    elseif ($argument === '--no-images') $options['images'] = false;
    elseif ($argument === '--images-only') $options['products'] = false;
    elseif (preg_match('/^--budget=(\d+)$/', $argument, $matches)) $options['budget'] = (float)$matches[1];
    else {
        fwrite(STDERR, sprintf("Unknown argument \"%s\".\n", $argument));
        exit(1);
    }
}

$model = new \Opencart\Admin\Model\Extension\Noveraile\Module\CatalogFeed($registry);

try {
    if ($status) {
        $state = $model->status();
        fwrite(STDOUT, sprintf(
            "Feed: %d products / %d variants. Imported: %d. Images missing: %d.\n",
            $state['feed_products'],
            $state['feed_variants'],
            $state['imported'],
            $state['images_missing']
        ));
        exit(0);
    }

    $report = $model->sync($options);
} catch (\Throwable $exception) {
    fwrite(STDERR, 'Catalog import failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf(
    "Catalog import: %d created, %d updated, %d unchanged, %d removed, %d duplicates disabled. Images: %d downloaded, %d failed, %d pending.\n",
    $report['created'],
    $report['updated'],
    $report['skipped'],
    $report['removed'],
    $report['retired'],
    $report['images'],
    $report['failed'],
    $report['pending']
));

// Pending images are expected when the run is budgeted, so only a hard failure
// with nothing left to retry is worth a non-zero exit.
exit($report['failed'] > 0 && $report['pending'] === 0 ? 1 : 0);
