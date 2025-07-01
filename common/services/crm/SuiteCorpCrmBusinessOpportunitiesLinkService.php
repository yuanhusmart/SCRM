<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesLink;
use common\services\Service;

class SuiteCorpCrmBusinessOpportunitiesLinkService extends Service
{
    /**
     * 新增商机关系人
     * @param array $params
     * @return array
     * @throws ErrException
     */
    public static function createVerify(array $params)
    {
        $model = new SuiteCorpCrmBusinessOpportunitiesLink();
        $model->bindCorp();
        $attributeLabels = $model->attributeLabels();
        $customerNo = self::getRequireString($params,$attributeLabels, 'customer_no');
        $businessOpportunitiesNo = self::getRequireString($params,$attributeLabels, 'business_opportunities_no');
        $relational = self::getEnumInt($params,$attributeLabels, 'relational', array_keys(SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_MAP));
        $accountId = self::getInt($params,'account_id');
        if (!$accountId){
            throw new ErrException(Code::PARAMS_ERROR, '员工不能为空');
        }
        return [
            'suite_id' => $model->suite_id,
            'corp_id' => $model->corp_id,
            'customer_no' => $customerNo,
            'business_opportunities_no' => $businessOpportunitiesNo,
            'business_opportunities_link_no' => self::getSnowflakeId(),
            'relational' => $relational,
            'account_id' => $accountId,
            'created_at' => time(),
            'updated_at' => time(),
        ];
    }

    /**
     * 检查商机关系人数量约束
     * @param string $customerNo
     * @param string $businessOpportunitiesNo
     * @return void
     * @throws ErrException
     */
    public static function checkCreated(string $customerNo, string $businessOpportunitiesNo)
    {
        $relationals = SuiteCorpCrmBusinessOpportunitiesLink::corp()
            ->select(['relational'])
            ->andWhere([
                'customer_no' => $customerNo,
                'business_opportunities_no' => $businessOpportunitiesNo,
            ])
            ->column();
        $relational1 = 0;
        $relational2 = 0;
        foreach ($relationals as $relational){
            switch ($relational){
                case SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_1:
                    $relational1++;
                    break;
                case SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_2:
                    $relational2++;
                    break;
            }
        }
        if ($relational1 > 1){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '数据验证错误,商机下不能存在多个跟进人');
        }
        if ($relational2 > 5){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '数据验证错误,一个商机下不能存在超过5个协作人');
        }
    }

}
