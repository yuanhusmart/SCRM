<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\crm\SuiteCorpCrmCustomerLink;
use common\services\Service;

class SuiteCorpCrmCustomerLinkService extends Service
{
    /**
     * @param array $params
     * @return array
     * @throws ErrException
     */
    public static function createCustomerVerify(array $params): array
    {
        $model = new SuiteCorpCrmCustomerLink();
        $model->bindCorp();
        $attributeLabels = $model->attributeLabels();
        $customerNo = self::getRequireString($params,$attributeLabels, 'customer_no');
        $accountId = self::getInt($params,'account_id');
        if (!$accountId){
            throw new ErrException(Code::PARAMS_ERROR, '员工不能为空');
        }
        $relational = self::getEnumInt($params,$attributeLabels, 'relational',array_keys(SuiteCorpCrmCustomerLink::RELATIONAL_MAP));
        $linkNo = self::getSnowflakeId();
        return [
            'suite_id' => $model->suite_id,
            'corp_id' => $model->corp_id,
            'customer_no' => $customerNo,
            'link_no' => $linkNo,
            'relational' => $relational,
            'account_id' => $accountId,
            'created_at' => time(),
            'updated_at' => time(),
        ];
    }
}
