<?php
//defined('YII_DEBUG') or define('YII_DEBUG', true);
//defined('YII_ENV') or define('YII_ENV', 'prod');

require __DIR__ . '/../../vendor/autoload.php';

// Environment
require(__DIR__ . '/../../env.php');
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../config/main.php'
);

$root = dirname($_SERVER['DOCUMENT_ROOT']);
$_SERVER['DOCUMENT_ROOT'] = $root.'/app/web';
$_SERVER['SCRIPT_FILENAME'] = $root.'/app/web/index.php';

(new yii\web\Application($config))->run();
