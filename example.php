<?php
set_time_limit(0);
error_reporting(E_ALL^E_NOTICE);
ini_set('memory_limit','4028M');

spl_autoload_register(function($class) {
    $prefix = 'Scrapper\\';

    if (stripos($class, $prefix) === false) {
        return;
    }

    $class = substr($class, strlen($prefix));
    $location = __DIR__ . '/src/Scrapper/' . str_replace('\\', '/', $class) . '.php';

    if (is_file($location)) {
        require_once($location);
    }
});

$base_dir = 'D:\\TvShows\\';
$scrapper = new Scrapper\Scrapper($base_dir);

$links = array();

$link = array();
$links[]  = 'http://58.65.128.4:605/English%20TV%20Shows%20(L%20-%20Q)/Lost%20Season%20I-II-III-IV-V-VI/?sortby=';

$scrapper->scrap($links);