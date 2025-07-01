<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\crm\SuiteCorpCrmCustomer;
use common\models\crm\SuiteCorpCrmCustomerContact;
use common\models\crm\SuiteCorpCrmCustomerRequirementTag;
use common\services\Service;

class SuiteCorpCrmCustomerRequirementTagService extends Service
{
    /**
     * 保存标签
     * @param array $params
     * @return void
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function save(array $params)
    {
        $customerNo = self::getString($params, 'customer_no');
        if (!$customerNo){
            throw new ErrException(Code::PARAMS_ERROR,'客户编号不能为空');
        }
        $contactNo = self::getString($params, 'contact_no');
        if (!$contactNo){
            throw new ErrException(Code::PARAMS_ERROR,'联系人编号不能为空');
        }
        /** @var SuiteCorpCrmCustomerContact $contact */
        $contact = SuiteCorpCrmCustomerContact::corp()->andWhere(['customer_no' => $customerNo,'contact_no' => $contactNo])->one();
        if (!$contact){
            throw new ErrException(Code::PARAMS_ERROR,'联系人不存在');
        }

        $data = self::getArray($params,'data');
        foreach ($data as $k => $item){
            $groupName = self::getString($item, 'group_name');
            $tagName = self::getString($item, 'tag_name');
            if (!$groupName || !$tagName){
                throw new ErrException(Code::PARAMS_ERROR,sprintf('第%s个标签的标签组名称和标签名称不能为空',($k+1)));
            }
            if (isset($item['id'])){
                //修改
                SuiteCorpCrmCustomerRequirementTag::updateAll([
                    'group_name' => $groupName,
                    'tag_name' => $tagName,
                ],[
                    'suite_id' => $contact->suite_id,
                    'corp_id' => $contact->corp_id,
                    'customer_no' => $contact->customer_no,
                    'contact_no' => $contact->contact_no,
                    'id' => $item['id']
                ]);
            }else{
                //新增
                SuiteCorpCrmCustomerRequirementTag::updateOrCreate([
                    'suite_id' => $contact->suite_id,
                    'corp_id' => $contact->corp_id,
                    'customer_no' => $contact->customer_no,
                    'contact_no' => $contact->contact_no,
                    'group_name' => $groupName,
                    'tag_name' => $tagName,
                ],[
                    'suite_id' => $contact->suite_id,
                    'corp_id' => $contact->corp_id,
                    'customer_no' => $contact->customer_no,
                    'contact_no' => $contact->contact_no,
                    'group_name' => $groupName,
                    'tag_name' => $tagName,
                ]);
            }
        }
    }

    /**
     * 删除标签
     * @param array $params
     * @return void
     * @throws ErrException
     * @throws \Throwable
     */
    public static function remove(array $params)
    {
        $ids = self::getArray($params,'ids');
        if (!$ids){
            throw new ErrException(Code::PARAMS_ERROR,'标签ID不能为空');
        }
        SuiteCorpCrmCustomerRequirementTag::deleteAll([
            'suite_id' => auth()->suiteId(),
            'corp_id' => auth()->corpId(),
            'id' => $ids,
        ]);
    }

}
