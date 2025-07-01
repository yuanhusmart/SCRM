<?php

namespace common\models\concerns\traits;

use common\helpers\Maker;

trait Helper
{
    use ToArray, With, DefaultScenario, Maker, UpdateBatch;
}