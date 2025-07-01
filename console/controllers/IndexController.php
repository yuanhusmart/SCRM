<?php
namespace console\controllers;

/**
 * Class IndexController
 * @package console\controllers
 */
class IndexController extends \yii\console\Controller
{

    /**
     * ./yii index/index
     */
    public function actionIndex()
    {
        echo 'console'.PHP_EOL;
    }

}