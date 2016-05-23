<?php

set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
ini_set('memory_limit', '4028M');

include __DIR__.'/vendor/autoload.php';
/*
$links[] = '';

$links[] = [
    'link' => [
        'url'      => 'http://58.65.128.62:803/',
        'location' => $base_dir.'/telugu.txt',
    ],
    'type'      => 'file',
    'directory' => 'TeluguHD',
];

$links[] = [
    'link'      => 'http://58.65.128.61:808/Game%20of%20Thrones%20Season%206?sortby=',
    'directory' => 'TvShows',
];

 */

$base_dir = 'D:\\videos\\';
$scrapper = new Scrapper\Scrapper($base_dir);

$links[] = '';

$scrapper->scrap($links);
