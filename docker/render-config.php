<?php
declare(strict_types=1);

function requiredEnvironment(string $name): string {
    $value = getenv($name);

    if ($value === false || $value === '') {
        fwrite(STDERR, "Missing required environment variable: {$name}\n");
        exit(1);
    }

    return $value;
}

function phpDefine(string $name, string $value): string {
    return "define(" . var_export($name, true) . ", " . var_export($value, true) . ");\n";
}

$root = '/var/www/html/';
$httpServer = rtrim(requiredEnvironment('OPENCART_HTTP_SERVER'), '/') . '/';

$database = [
    'DB_DRIVER'   => 'mysqli',
    'DB_HOSTNAME' => requiredEnvironment('OPENCART_DB_HOST'),
    'DB_USERNAME' => requiredEnvironment('OPENCART_DB_USER'),
    'DB_PASSWORD' => requiredEnvironment('OPENCART_DB_PASSWORD'),
    'DB_DATABASE' => requiredEnvironment('OPENCART_DB_NAME'),
    'DB_PREFIX'   => requiredEnvironment('OPENCART_DB_PREFIX'),
    'DB_PORT'     => getenv('OPENCART_DB_PORT') ?: '3306',
];

$catalog = "<?php\n";
$catalog .= phpDefine('APPLICATION', 'Catalog');
$catalog .= phpDefine('HTTP_SERVER', $httpServer) . "\n";
$catalog .= phpDefine('DIR_OPENCART', $root);
$catalog .= "define('DIR_APPLICATION', DIR_OPENCART . 'catalog/');\n";
$catalog .= "define('DIR_SYSTEM', DIR_OPENCART . 'system/');\n";
$catalog .= "define('DIR_EXTENSION', DIR_OPENCART . 'extension/');\n";
$catalog .= "define('DIR_IMAGE', DIR_OPENCART . 'image/');\n";
$catalog .= "define('DIR_STORAGE', DIR_SYSTEM . 'storage/');\n";
$catalog .= "define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');\n";
$catalog .= "define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');\n";
$catalog .= "define('DIR_CONFIG', DIR_SYSTEM . 'config/');\n";
$catalog .= "define('DIR_CACHE', DIR_STORAGE . 'cache/');\n";
$catalog .= "define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');\n";
$catalog .= "define('DIR_LOGS', DIR_STORAGE . 'logs/');\n";
$catalog .= "define('DIR_SESSION', DIR_STORAGE . 'session/');\n";
$catalog .= "define('DIR_UPLOAD', DIR_STORAGE . 'upload/');\n\n";

foreach ($database as $name => $value) {
    $catalog .= phpDefine($name, $value);
}

$admin = "<?php\n";
$admin .= phpDefine('APPLICATION', 'Admin');
$admin .= phpDefine('HTTP_SERVER', $httpServer . 'admin/');
$admin .= phpDefine('HTTP_CATALOG', $httpServer) . "\n";
$admin .= phpDefine('DIR_OPENCART', $root);
$admin .= "define('DIR_APPLICATION', DIR_OPENCART . 'admin/');\n";
$admin .= "define('DIR_SYSTEM', DIR_OPENCART . 'system/');\n";
$admin .= "define('DIR_EXTENSION', DIR_OPENCART . 'extension/');\n";
$admin .= "define('DIR_IMAGE', DIR_OPENCART . 'image/');\n";
$admin .= "define('DIR_STORAGE', DIR_SYSTEM . 'storage/');\n";
$admin .= "define('DIR_CATALOG', DIR_OPENCART . 'catalog/');\n";
$admin .= "define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');\n";
$admin .= "define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');\n";
$admin .= "define('DIR_CONFIG', DIR_SYSTEM . 'config/');\n";
$admin .= "define('DIR_CACHE', DIR_STORAGE . 'cache/');\n";
$admin .= "define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');\n";
$admin .= "define('DIR_LOGS', DIR_STORAGE . 'logs/');\n";
$admin .= "define('DIR_SESSION', DIR_STORAGE . 'session/');\n";
$admin .= "define('DIR_UPLOAD', DIR_STORAGE . 'upload/');\n\n";

foreach ($database as $name => $value) {
    $admin .= phpDefine($name, $value);
}

$admin .= "\ndefine('OPENCART_SERVER', 'https://www.opencart.com/');\n";

file_put_contents($root . 'config.php', $catalog, LOCK_EX);
file_put_contents($root . 'admin/config.php', $admin, LOCK_EX);
