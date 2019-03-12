<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
date_default_timezone_set('PRC');
define('SY_VERSION', '1.0.0');
$syLogPath = ini_get('seaslog.default_basepath');
if(substr($syLogPath, -1) == '/'){
    define('SY_LOG_PATH', $syLogPath . 'sy' . SY_PROJECT);
} else {
    define('SY_LOG_PATH', $syLogPath . '/sy' . SY_PROJECT);
}
unset($syLogPath);