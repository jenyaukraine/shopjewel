<?php
declare(strict_types=1);

define('VERSION', (string)(getenv('OPENCART_VERSION') ?: '4.1.0.3'));

$openCartRoot = rtrim((string)(getenv('OPENCART_ROOT') ?: '/var/www/html'), '/\\');
require_once $openCartRoot . '/admin/config.php';
require_once DIR_SYSTEM . 'startup.php';

$autoloader = new \Opencart\System\Engine\Autoloader();
$autoloader->register('Opencart\\Admin', DIR_APPLICATION);
$autoloader->register('Opencart\\System', DIR_SYSTEM);
$autoloader->register('Opencart\\Admin\\Model\\Extension\\Sixmoments', DIR_EXTENSION . 'sixmoments/admin/model/');

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
    $value = $setting['serialized'] ? json_decode($setting['value'], true) : $setting['value'];
    $config->set($setting['key'], $value);
}

$languageCode = (string)($config->get('config_language_admin') ?: $config->get('config_language'));
$language = $db->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` = '" . $db->escape($languageCode) . "' LIMIT 1");

if (!$language->num_rows) {
    throw new \RuntimeException('OpenCart default language is not available.');
}

$config->set('config_language_id', (int)$language->row['language_id']);

$event = new \Opencart\System\Engine\Event($registry);
$registry->set('event', $event);
$registry->set('factory', new \Opencart\System\Engine\Factory($registry));
$registry->set('load', new \Opencart\System\Engine\Loader($registry));

$model = new \Opencart\Admin\Model\Extension\Sixmoments\Module\Sixmoments($registry);
$model->bootstrap();

fwrite(STDOUT, "6MOMENTS storefront registration is ready.\n");
