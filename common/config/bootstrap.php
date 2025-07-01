<?php
Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@app', dirname(dirname(__DIR__)) . '/app');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');
Yii::setAlias('@bower', dirname(dirname(__DIR__)) . '/vendor/yidas/yii2-bower-asset/bower');

// 事件注册
\app\events\Register::register();