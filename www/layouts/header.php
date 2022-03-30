<?php
require_once  'vendor/autoload.php';

use OsT\Base\Security;
use OsT\Base\System;
use OsT\User;

header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';
require 'config_version.php';

if (NO_CATCH) {
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Expires: " . date("r"));
}

$DB = new \OsT\Base\DB();

$SETTINGS = \OsT\Settings::getData();

$STRUCT_DATA = \OsT\Unit::getData(null, [
    'id',
    'parent',
    'title',
]);
$STRUCT_TREE = \OsT\Unit::getTree();

$USER = User::check();

session_start();

const MODE_INSERT = 0;
const MODE_EDIT = 1;

$pageData = [
    'description' => 'default',
    'keywords' => 'default',
    'shortcuticon' => 'img/favicon.ico',
    'title' => 'default',
    'footer' => true,
];
$pagesGroup = [];
$sysInfo = [
    'post_max_size' => System::return_bytes(ini_get('post_max_size')),
    'upload_max_filesize' => System::return_bytes(ini_get('upload_max_filesize')),
];

Security::timeLocker();
Security::checkSoStat();
