<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\crm\SuiteCorpCrmBusinessOpportunities;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesContact;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesLink;
use common\models\crm\SuiteCorpCrmCustomerContact;
use common\models\crm\SuiteCorpCrmCustomerContactInformation;
use common\services\Service;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\StaleObjectException;

class SuiteCorpCrmBusinessOpportunitiesContactService extends Service
{
    /**
     * 保存商机联系人
     * @param array $params
     * @throws ErrException
     * @throws InvalidConfigException
     * @throws Exception
     */
    public static function save(array $params)
    {
        $businessOpportunitiesNo = self::getString($params, 'business_opportunities_no');
        if (!$businessOpportunitiesNo){
            throw new ErrException(Code::PARAMS_ERROR, '商机编号不能为空');
        }

        $data = self::getArray($params, 'data');
        if (!$data){
            throw new ErrException(Code::PARAMS_ERROR, '商机联系人不能为空');
        }

        /** @var SuiteCorpCrmBusinessOpportunities $businessOpportunities 商机 */
        $businessOpportunities = SuiteCorpCrmBusinessOpportunities::corp()
            ->andWhere(['business_opportunities_no' => $businessOpportunitiesNo])
            ->one();
        if (!$businessOpportunities){
            throw new ErrException(Code::PARAMS_ERROR, '商机不存在');
        }

        //获取客户所有联系人列表
        $customerContacts = SuiteCorpCrmCustomerContact::corp()
            ->select(['contact_no'])
            ->andWhere(['customer_no' => $businessOpportunities->customer_no,])
            ->column();

        $newData = [];
        $contactNos = [];
        foreach ($data as $item){
            if (!isset($item['contact_no'])){
                throw new ErrException(Code::PARAMS_ERROR, '缺少联系人编号');
            }
            if (!in_array($item['contact_no'],$customerContacts)){
                throw new ErrException(Code::PARAMS_ERROR, '存在错误的联系人数据');
            }
            if (!isset($item['role'])){
                throw new ErrException(Code::PARAMS_ERROR, '缺少角色');
            }
            //一个联系人只能担任一个角色
            $contactNos[] = $item['contact_no'];
            $newData[$item['contact_no']] = [
                'contact_no' => $item['contact_no'],
                'role' => $item['role'],
            ];
        }

        $suiteCorpCrmBusinessOpportunitiesContacts = SuiteCorpCrmBusinessOpportunitiesContact::corp()
            ->select(['contact_no'])
            ->andWhere(['business_opportunities_no' => $businessOpportunitiesNo])
            ->column();
        $adds = array_diff($contactNos,$suiteCorpCrmBusinessOpportunitiesContacts);
        $deletes = array_diff($suiteCorpCrmBusinessOpportunitiesContacts,$contactNos);
        //通过交集获取要更新的数据
        $updates = array_intersect($contactNos,$suiteCorpCrmBusinessOpportunitiesContacts);

        if (!empty($adds)){
            $ins = [];
            $base = [
                'suite_id' => $businessOpportunities->suite_id,
                'corp_id' => $businessOpportunities->corp_id,
                'customer_no' => $businessOpportunities->customer_no,
                'business_opportunities_no' => $businessOpportunities->business_opportunities_no,
                'is_main' => SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_2,
                'created_at' => time(),
                'updated_at' => time(),
            ];
            foreach ($adds as $contactNo){
                if (isset($newData[$contactNo])){
                    $ins[] = array_merge($base,$newData[$contactNo]);
                }
            }
            if (!empty($ins)){
                SuiteCorpCrmBusinessOpportunitiesContact::batchInsert($ins);
            }
        }

        if (!empty($deletes)){
            SuiteCorpCrmBusinessOpportunitiesContact::deleteAll([
                'and',
                ['business_opportunities_no' => $businessOpportunitiesNo],
                ['contact_no' => $deletes],
            ]);
        }

        if (!empty($updates)){
            foreach ($updates as $contactNo){
                if (isset($newData[$contactNo])){
                    SuiteCorpCrmBusinessOpportunitiesContact::updateAll(array_merge($newData[$contactNo],['updated_at' => time()]),[
                        'and',
                        ['business_opportunities_no' => $businessOpportunitiesNo],
                        ['contact_no' => $contactNo],
                    ]);
                }
            }
        }

        //判断是否存在主联系人
        $hasMain = SuiteCorpCrmBusinessOpportunitiesContact::corp()
            ->where([
                'business_opportunities_no' => $businessOpportunitiesNo,
                'is_main' => SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_1,
            ])
            ->one();
        if (!$hasMain){
            //给第一个人设置为主联系人
            $first = SuiteCorpCrmBusinessOpportunitiesContact::corp()
                ->where([
                    'business_opportunities_no' => $businessOpportunitiesNo,
                ])
                ->one();
            if (!$first){
                throw new ErrException(Code::PARAMS_ERROR, '商机联系人最少需要一个联系人');
            }
            $first->is_main = SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_1;
            if (!$first->save()){
                throw new ErrException(Code::PARAMS_ERROR, '设置主联系人失败');
            }
        }
        SuiteCorpCrmBusinessOpportunitiesSessionService::sync($businessOpportunitiesNo);
    }

    /**
     * 删除商机联系人
     * @param array $params
     * @return void
     * @throws ErrException
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public static function remove(array $params)
    {
        $businessOpportunitiesContact = self::getOneData($params);
        $businessOpportunitiesNo = $businessOpportunitiesContact->business_opportunities_no;
        $businessOpportunitiesContact->delete();
        SuiteCorpCrmBusinessOpportunitiesSessionService::sync($businessOpportunitiesNo);
    }

    /**
     * 设为主要联系人
     * @param array $params
     * @return void
     * @throws ErrException
     * @throws InvalidConfigException
     */
    public static function setMain(array $params)
    {
        $businessOpportunitiesContact = self::getOneData($params);

        //只有商机跟进人及上级才可以设置
        $linkAccountIds = SuiteCorpCrmBusinessOpportunitiesLink::corp()
            ->select(['account_id'])
            ->andWhere([
                'customer_no' => $businessOpportunitiesContact->customer_no,
                'business_opportunities_no' => $businessOpportunitiesContact->business_opportunities_no,
            ])
            ->column();
        if (!auth()->isStaffSuperior($linkAccountIds)){
            throw new ErrException(Code::PARAMS_ERROR, '您没有权限设置此商机的主要联系人,仅是商机跟进人/协助人及上级可设置');
        }

        SuiteCorpCrmBusinessOpportunitiesContact::updateAll(
            [
                'is_main' => SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_2,
            ],
            [
                'suite_id' => $businessOpportunitiesContact->suite_id,
                'corp_id' => $businessOpportunitiesContact->corp_id,
                'customer_no' => $businessOpportunitiesContact->customer_no,
                'business_opportunities_no' => $businessOpportunitiesContact->business_opportunities_no,
                'is_main' => SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_1
            ]
        );

        $businessOpportunitiesContact->is_main = SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_1;
        if (!$businessOpportunitiesContact->save()){
            throw new ErrException(Code::BUSINESS_ABNORMAL, '设置失败');
        }
    }

    /**
     * 获取商机联系人详情
     * @param array $params
     * @return array|ActiveRecord|SuiteCorpCrmBusinessOpportunitiesContact
     * @throws ErrException
     */
    public static function getOneData(array $params)
    {
        $businessOpportunitiesNo = self::getString($params, 'business_opportunities_no');
        if (!$businessOpportunitiesNo){
            throw new ErrException(Code::PARAMS_ERROR, '商机编号不能为空');
        }

        $customerNo = self::getString($params, 'customer_no');
        if (!$customerNo){
            throw new ErrException(Code::PARAMS_ERROR, '客户编号不能为空');
        }

        $contactNo = self::getString($params, 'contact_no');
        if (!$contactNo){
            throw new ErrException(Code::PARAMS_ERROR, '联系人编号不能为空');
        }

        /** @var SuiteCorpCrmBusinessOpportunitiesContact $businessOpportunities 商机 */
        $businessOpportunitiesContact = SuiteCorpCrmBusinessOpportunitiesContact::corp()
            ->where([
                'customer_no' => $customerNo,
                'business_opportunities_no' => $businessOpportunitiesNo,
                'contact_no' => $contactNo,
            ])
            ->one();
        if (!$businessOpportunitiesContact){
            throw new ErrException(Code::PARAMS_ERROR, '商机联系人不存在');
        }
        return $businessOpportunitiesContact;
    }

    /**
     * 商机联系人列表
     * @param array $params
     * @return array|null
     */
    public static function index(array $params)
    {
        return SuiteCorpCrmBusinessOpportunitiesContact::corp()
            ->select([
                SuiteCorpCrmBusinessOpportunitiesContact::asField('id'),
                SuiteCorpCrmBusinessOpportunitiesContact::asField('suite_id'),
                SuiteCorpCrmBusinessOpportunitiesContact::asField('corp_id'),
                SuiteCorpCrmBusinessOpportunitiesContact::asField('customer_no'),
                SuiteCorpCrmBusinessOpportunitiesContact::asField('business_opportunities_no'),
                SuiteCorpCrmBusinessOpportunitiesContact::asField('contact_no'),
                SuiteCorpCrmBusinessOpportunitiesContact::asField('role'),
                SuiteCorpCrmBusinessOpportunitiesContact::asField('is_main'),
            ])
            ->joinWith([
                'contact' => function($query){
                    $query->select([
                        SuiteCorpCrmCustomerContact::asField('id'),
                        SuiteCorpCrmCustomerContact::asField('suite_id'),
                        SuiteCorpCrmCustomerContact::asField('corp_id'),
                        SuiteCorpCrmCustomerContact::asField('customer_no'),
                        SuiteCorpCrmCustomerContact::asField('contact_no'),
                        SuiteCorpCrmCustomerContact::asField('contact_name'),
                    ])
                        ->with([
                            'information' => function ($query) {
                                $query->select([
                                    SuiteCorpCrmCustomerContactInformation::asField('id'),
                                    SuiteCorpCrmCustomerContactInformation::asField('contact_no'),
                                    SuiteCorpCrmCustomerContactInformation::asField('contact_information_type'),
                                    SuiteCorpCrmCustomerContactInformation::asField('contact_number')
                                ]);
                            }
                        ]);
                }
            ])
            ->andWhere([
                SuiteCorpCrmBusinessOpportunitiesContact::asField('customer_no') => self::getString($params, 'customer_no'),
                SuiteCorpCrmBusinessOpportunitiesContact::asField('business_opportunities_no') => self::getString($params, 'business_opportunities_no'),
            ])
            ->orderBy([
                SuiteCorpCrmBusinessOpportunitiesContact::asField('is_main') => SORT_ASC,
                SuiteCorpCrmBusinessOpportunitiesContact::asField('id') => SORT_DESC,
            ])
            ->myPage($params,function ($item){
                if (isset($item['contact']['information'])){
                    foreach ($item['contact']['information'] as $key => $value){
                        switch ($value['contact_information_type']){
                            case SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_1:
                                $item['contact']['information'][$key]['contact_number'] = strEncode($value['contact_number'],3,4,4);
                                break;
                            case SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_2:
                                $item['contact']['information'][$key]['contact_number'] = strEncode($value['contact_number'], 0, 2, 6);
                                break;
                            case SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_3:
                                $item['contact']['information'][$key]['contact_number'] = strEncode($value['contact_number'], 2, 2, 4);
                                break;
                        }
                    }
                }
                return $item;
            });
    }

}
