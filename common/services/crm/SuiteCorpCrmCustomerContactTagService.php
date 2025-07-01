<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\crm\SuiteCorpCrmCustomerContact;
use common\models\crm\SuiteCorpCrmCustomerContactInformation;
use common\models\crm\SuiteCorpCrmCustomerContactTag;
use common\services\Service;
use yii\db\StaleObjectException;

class SuiteCorpCrmCustomerContactTagService extends Service
{
    /**
     * 新增标签
     * @param $params
     * @throws ErrException
     */
    public static function create($params)
    {
        $id = self::getString($params, 'id');
        if (!$id){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系人ID不能为空');
        }
        $group_name = self::getString($params, 'group_name');
        if (!$group_name){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '标签组名称不能为空');
        }
        $tag_name = self::getString($params, 'tag_name');
        if (!$tag_name){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '标签名称不能为空');
        }

        /** @var SuiteCorpCrmCustomerContact $contact */
        $contact = SuiteCorpCrmCustomerContact::corp()->andWhere(['id' => $id])->one();
        if (!$contact){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系人不存在');
        }

        $exists = SuiteCorpCrmCustomerContactTag::corp()->andWhere([
            'contact_no' => $contact->contact_no,
            'group_name' => $group_name,
            'tag_name' => $tag_name,
        ])->exists();
        if (!$exists){
            $tag = new SuiteCorpCrmCustomerContactTag();
            $tag->bindCorp();
            $tag->contact_no = $contact->contact_no;
            $tag->group_name = $group_name;
            $tag->tag_name = $tag_name;
            if (!$tag->save()){
                throw new ErrException(Code::BUSINESS_ABNORMAL, '标签创建失败');
            }
        }
    }

    /**
     * 移除标签
     * @param $params
     * @throws ErrException
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public static function remove($params)
    {
        $id = self::getString($params, 'id');
        if (!$id){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '标签ID不能为空');
        }
        $tag = SuiteCorpCrmCustomerContactTag::corp()->andWhere(['id' => $id])->one();
        if (!$tag){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '标签不存在');
        }
        if (!$tag->delete()){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '标签删除失败');
        }
    }

    /**
     * 根据外部联系人ID获取联系人编号
     * @param array $params
     * @return array
     */
    public static function getContactNoByExternalUserId(array $params):array
    {
        $suiteId = self::getString($params,'suite_id');
        $corpId = self::getString($params,'corp_id');
        $externalUserId = self::getString($params,'external_userid');//外部联系人ID
        if (!$suiteId || !$corpId || !$externalUserId){
            return [];
        }
        //查询联系方式
        $information = SuiteCorpCrmCustomerContactInformation::find()
            ->select(['contact_no'])
            ->andWhere([
                'contact_information_type' => SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4,
                'contact_number' => $externalUserId,
            ])
            ->column();
        if (!$information){
            return [];
        }
        $contacts = SuiteCorpCrmCustomerContact::find()
            ->select(['contact_no'])
            ->andWhere([
                'suite_id' => $suiteId,
                'corp_id' => $corpId,
                'contact_no' => $information,
            ])
            ->groupBy(['contact_no'])
            ->column();
        if (!$contacts){
            return [];
        }
        return $contacts;
    }

    /**
     * 同步客户联系人标签数据
     * @param array $params
     * @return void
     * @throws \yii\db\Exception
     */
    public static function syncContactTag(array $params)
    {
        $suiteId = self::getString($params,'suite_id');
        $corpId = self::getString($params,'corp_id');
        $group_name = self::getString($params,'group_name');//标签组名称
        $tag_name = self::getString($params,'tag_name');//标签名称
        $contacts = self::getArray($params,'contacts');
        if (!$suiteId || !$corpId || !$contacts || !$group_name || !$tag_name){
            return;
        }
        foreach ($contacts as $contactNo){
            SuiteCorpCrmCustomerContactTag::updateOrCreate([
                'suite_id' => $suiteId,
                'corp_id' => $corpId,
                'contact_no' => $contactNo,
                'group_name' => $group_name,
                'tag_name' => $tag_name,
            ],[
                'suite_id' => $suiteId,
                'corp_id' => $corpId,
                'contact_no' => $contactNo,
                'group_name' => $group_name,
                'tag_name' => $tag_name,
            ]);
        }
        return;
    }

}
