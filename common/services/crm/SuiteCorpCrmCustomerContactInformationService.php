<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\crm\SuiteCorpCrmCustomerContactInformation;
use common\services\Service;
use yii\db\Exception;
use yii\db\StaleObjectException;

class SuiteCorpCrmCustomerContactInformationService extends Service
{
    /**
     * 验证
     * @param $params
     * @param bool $forceContactNumber 是否强制验证联系方式号码非空
     * @return array
     * @throws ErrException
     * @uses
     */
    public static function createCustomerVerify($params, bool $forceContactNumber = false): array
    {
        $model = new SuiteCorpCrmCustomerContactInformation();
        $attributeLabels = $model->attributeLabels();
        $contactNo = self::getRequireString($params, $attributeLabels, 'contact_no');
        $contactInformationType = self::getEnumInt($params, $attributeLabels, 'contact_information_type', array_keys(SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_MAP));
        if (in_array($contactInformationType, [SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_1, SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4]) ||
            $forceContactNumber
        ) {
            $contactNumber = self::getRequireString($params, $attributeLabels, 'contact_number');
        } else {
            $contactNumber = self::getString($params, 'contact_number');
        }
        return [
            'contact_no' => $contactNo,
            'contact_information_type' => $contactInformationType,
            'contact_number' => $contactNumber,
            'created_at' => time(),
            'updated_at' => time(),
        ];
    }

    /**
     * 新增联系方式
     * @param $params
     * @return true
     * @throws ErrException
     * @throws Exception|\yii\base\InvalidConfigException
     */
    public static function create($params)
    {
        $information = self::getArray($params, 'information');
        if (!$information) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系方式不能为空');
        }
        $contactNo = $information[0]['contact_no'];
        $contactList = SuiteCorpCrmCustomerContactInformation::find()->where(['contact_no' => $contactNo])->asArray()->all();
        $contactUnique = [];
        foreach ($contactList as $item) {
            $index = sprintf('%s_%s', $item['contact_information_type'], $item['contact_number']);
            $contactUnique[$index] = true;
        }

        $ins = [];
        $contactNumbers = [];
        foreach ($information as $item) {
            $item = self::createCustomerVerify($item, true);
            $index = sprintf('%s_%s', $item['contact_information_type'], $item['contact_number']);
            if (isset($contactUnique[$index])) {
                continue;
            }
            if ($item['contact_no'] != $contactNo) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '单次仅支持同一联系人进行新增');
            }
            $ins[] = $item;
            $contactNumbers[] = $item['contact_number'];
        }
        if (!$ins) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系方式已存在');
        }
        self::verifyExistsByParamThrow($contactNumbers, $contactNo);

        SuiteCorpCrmCustomerContactInformation::batchInsert($ins);
        if (
            collect($ins)->where('contact_information_type',  SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4)->count() > 0
        ){
            SuiteCorpCrmBusinessOpportunitiesSessionService::contact($contactNo);
        }
        SuiteCorpCrmCustomerContactService::syncUpdated($contactNo);
        return true;
    }

    /**
     * 保存联系方式
     * @param $params
     * @return true
     * @throws ErrException
     * @throws Exception
     */
    public static function save($params)
    {
        $contactNo = self::getString($params, 'contact_no');
        if (!$contactNo) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系人编号不能为空');
        }
        $contactNumber = self::getString($params, 'contact_number');
        if (!$contactNumber) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系方式号码不能为空');
        }

        $contactInformation = self::verifyOneData($params);

        //同一种联系方式的号码不能重复
        $contactList = SuiteCorpCrmCustomerContactInformation::find()
            ->select(['contact_number'])
            ->where(['contact_no' => $contactNo, 'contact_information_type' => $contactInformation->contact_information_type])
            ->column();
        //无修改
        if (isset($contactList[$contactNumber])) {
            return true;
        }

        //如果需要修改，需要验证该联系方式是否被其他联系人使用
        self::verifyExistsByParamThrow([$contactNumber], $contactNo);

        $contactInformation->contact_number = $contactNumber;
        if (!$contactInformation->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系方式保存失败');
        }
        if ($contactInformation->contact_information_type == SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4){
            SuiteCorpCrmBusinessOpportunitiesSessionService::contact($contactNo);
        }
        SuiteCorpCrmCustomerContactService::syncUpdated($contactNo);
        return true;
    }

    /**
     * 移除联系方式
     * @param $params
     * @return void
     * @throws ErrException
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public static function remove($params)
    {
        $contactInformation = self::verifyOneData($params);
        $contactInformationType = $contactInformation->contact_information_type;
        $contactNo = $contactInformation->contact_no;
        $contactInformation->delete();
        $count = SuiteCorpCrmCustomerContactInformation::find()
            ->where(['contact_no' => $contactNo])
            ->count();
        if ($count <= 0){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '操作后联系人联系方式不能为空');
        }
        if ($contactInformationType == SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4){
            SuiteCorpCrmBusinessOpportunitiesSessionService::contact($contactNo);
        }
        SuiteCorpCrmCustomerContactService::syncUpdated($contactNo);
    }

    /**
     * @param $params
     * @return SuiteCorpCrmCustomerContactInformation
     * @throws ErrException
     */
    public static function verifyOneData($params)
    {
        $id = self::getId($params);
        if (!$id) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系方式ID不能为空');
        }
        $contactNo = self::getString($params, 'contact_no');
        if (!$contactNo) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系人编号不能为空');
        }
        /** @var SuiteCorpCrmCustomerContactInformation $contactInformation */
        $contactInformation = SuiteCorpCrmCustomerContactInformation::find()->where(['id' => $id, 'contact_no' => $contactNo,])->one();
        if (!$contactInformation) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系方式不存在');
        }
        return $contactInformation;
    }

    /**
     * 验证联系方式是否存在
     * @param array $contactNumbers
     * @param string $contactNo
     * @return void
     * @throws ErrException
     */
    public static function verifyExistsByParamThrow(array $contactNumbers, string $contactNo)
    {
        $mobileContacts = SuiteCorpCrmCustomerContactService::verifyExistsByParam($contactNumbers);
        $mobileContacts = array_column($mobileContacts, 'contact_name', 'contact_no');
        unset($mobileContacts[$contactNo]);
        if (!empty($mobileContacts)) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系方式已被使用，请查证后修改');
        }
    }

    /**
     * 批量保存
     * @param $params
     * @throws ErrException
     * @throws Exception
     */
    public static function batchSave($params)
    {
        $suiteCorpCrmCustomerContactInformation = new SuiteCorpCrmCustomerContactInformation();
        $attributeLabels = $suiteCorpCrmCustomerContactInformation->attributeLabels();
        $contactNo = self::getRequireString($params, $attributeLabels, 'contact_no');
        $contactInformationType = self::getEnumInt($params, $attributeLabels, 'contact_information_type', array_keys(SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_MAP));
        $contactNumbers = self::getArray($params, 'contact_numbers');
        $contactNumbers = array_unique($contactNumbers);
        if (
            in_array($contactInformationType, [
                SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_1,
                SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4,
            ]) &&
            empty($contactNumbers)
        ) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系方式号码不能为空');
        }

        $contactInformationType = self::getInt($params, 'contact_information_type');
        if (!$contactInformationType) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系方式类型不能为空');
        }

        $informations = SuiteCorpCrmCustomerContactInformation::find()
            ->select(['contact_number'])
            ->where(['contact_no' => $contactNo, 'contact_information_type' => $contactInformationType])
            ->column();

        //通过差集的方法，判断出本次需要新增的哪些，需要删除哪些
        $addContactNumbers = array_diff($contactNumbers, $informations);
        $deleteContactNumbers = array_diff($informations, $contactNumbers);

        //对需要增加的数据验证是否被其他联系人使用了
        if (!empty($addContactNumbers)) {
            self::verifyExistsByParamThrow($addContactNumbers, $contactNo);
            $t = time();
            foreach ($addContactNumbers as $k => $addContactNumber) {
                $addContactNumbers[$k] = [
                    'contact_no' => $contactNo,
                    'contact_information_type' => $contactInformationType,
                    'contact_number' => $addContactNumber,
                    'created_at' => $t,
                    'updated_at' => $t,
                ];
            }
            SuiteCorpCrmCustomerContactInformation::batchInsert($addContactNumbers);
        }

        if (!empty($deleteContactNumbers)) {
            SuiteCorpCrmCustomerContactInformation::deleteAll([
                'contact_no' => $contactNo,
                'contact_information_type' => $contactInformationType,
                'contact_number' => $deleteContactNumbers,
            ]);
        }

        $count = SuiteCorpCrmCustomerContactInformation::find()->where(['contact_no' => $contactNo])->count();
        if ($count <= 0){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '操作后联系人联系方式不能为空');
        }

        if (
            $contactInformationType == SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4 &&
            (!empty($addContactNumbers) || !empty($deleteContactNumbers))
        ){
            SuiteCorpCrmBusinessOpportunitiesSessionService::contact($contactNo);
        }
        SuiteCorpCrmCustomerContactService::syncUpdated($contactNo);
    }
}
