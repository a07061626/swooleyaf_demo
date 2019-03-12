<?php
define('SY_ROOT', __DIR__); //项目根目录
define('SY_ENV', 'dev'); //项目环境 dev:测试 product:正式
define('SY_PROJECT', 'a01'); //项目标识
define('SY_PROJECT_LIBS_ROOT', SY_ROOT . '/libs_project/');

$frameLibsDir = \Yaconf::get('project.' . SY_ENV . SY_PROJECT . '.dir.libs.frame');
if(substr($frameLibsDir, -1) == '/'){
    define('SY_FRAME_LIBS_ROOT', $frameLibsDir);
} else {
    define('SY_FRAME_LIBS_ROOT', $frameLibsDir . '/');
}
unset($frameLibsDir);
require_once SY_FRAME_LIBS_ROOT . 'helper_autoload.php';