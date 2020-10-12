<?php

define('HELSINGBORG_PATH', get_stylesheet_directory() . '/');

//Include vendor files
if (file_exists(dirname(ABSPATH) . '/vendor/autoload.php')) {
    require_once dirname(ABSPATH) . '/vendor/autoload.php';
}

require_once HELSINGBORG_PATH . 'library/Vendor/Psr4ClassLoader.php';
$loader = new HELSINGBORG\Vendor\Psr4ClassLoader();
$loader->addPrefix('Helsingborg', HELSINGBORG_PATH . 'library');
$loader->addPrefix('Helsingborg', HELSINGBORG_PATH . 'source/php/');
$loader->register();

new Helsingborg\App();
