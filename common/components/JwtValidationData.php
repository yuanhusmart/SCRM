<?php

namespace common\components;

class JwtValidationData extends \sizeg\jwt\JwtValidationData
{
    /**
     * @inheritdoc
     */
    public function init()
    {
//        $this->validationData->setIssuer('yuzhua');
//        $this->validationData->setAudience('yuzhua');
//        $this->validationData->setId('JgeaFzQnfdfAJFoE');

        parent::init();
    }
}