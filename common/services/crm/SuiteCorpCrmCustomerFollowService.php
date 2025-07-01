<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\crm\SuiteCorpCrmBusinessOpportunities;
use common\models\crm\SuiteCorpCrmCustomer;
use common\models\crm\SuiteCorpCrmCustomerContact;
use common\models\crm\SuiteCorpCrmCustomerFollow;
use common\models\Account;
use common\services\Service;

class SuiteCorpCrmCustomerFollowService extends Service
{
    /**
     * 新增跟进记录
     * @param array $params
     * @return int
     * @throws ErrException
     */
    public static function create(array $params)
    {
        $created_id = auth()->accountId();
        if (!$created_id){
            throw new ErrException('创建人ID不能为空');
        }

        $followType = self::getInt($params,'follow_type');
        !$followType && $followType = SuiteCorpCrmCustomerFollow::FOLLOW_TYPE_1;

        $t = time();
        $follow = new SuiteCorpCrmCustomerFollow();
        $follow->bindCorp();
        $attributeLabels = $follow->attributeLabels();
        $follow->customer_no = self::getRequireString($params,$attributeLabels,'customer_no');
        $follow->business_opportunities_no = self::getRequireString($params,$attributeLabels,'business_opportunities_no');

        /** @var SuiteCorpCrmBusinessOpportunities $businessOpportunities 商机 */
        $businessOpportunities = SuiteCorpCrmBusinessOpportunities::corp()
            ->andWhere([
                'customer_no' => $follow->customer_no,
                'business_opportunities_no' => $follow->business_opportunities_no,
            ])
            ->one();
        if (!$businessOpportunities){
            throw new ErrException(Code::BUSINESS_ABNORMAL,'商机不存在');
        }
        $businessOpportunities->last_follow_at = $t;
        if (!$businessOpportunities->save()){
            throw new ErrException(Code::BUSINESS_ABNORMAL,'商机更新失败');
        }

        /** @var SuiteCorpCrmCustomer $customer 客户 */
        $customer = SuiteCorpCrmCustomer::corp()->andWhere(['customer_no' => $follow->customer_no,])->one();
        if (!$customer){
            throw new ErrException(Code::BUSINESS_ABNORMAL,'客户不存在');
        }
        $customer->last_follow_at = $t;
        if (!$customer->save()){
            throw new ErrException(Code::BUSINESS_ABNORMAL,'客户更新失败');
        }

        $follow->contact_no = self::getRequireString($params,$attributeLabels,'contact_no');
        $follow->follow_no = self::getSnowflakeId();
        $follow->created_id = $created_id;
        $follow->follow_type = $followType;
        $follow->content = self::getRequireString($params,$attributeLabels,'content');
        $follow->created_at = $t;
        if (!$follow->save()){
            throw new ErrException(Code::BUSINESS_ABNORMAL,'添加失败');
        }
        return $follow->id;
    }

    /**
     * 跟进记录列表
     * @param array $params
     * @return array|null
     */
    public static function index(array $params)
    {
        return SuiteCorpCrmCustomerFollow::corp()
            ->select([
                SuiteCorpCrmCustomerFollow::asField('id'),
                SuiteCorpCrmCustomerFollow::asField('suite_id'),
                SuiteCorpCrmCustomerFollow::asField('corp_id'),
                SuiteCorpCrmCustomerFollow::asField('customer_no'),
                SuiteCorpCrmCustomerFollow::asField('business_opportunities_no'),
                SuiteCorpCrmCustomerFollow::asField('contact_no'),
                SuiteCorpCrmCustomerFollow::asField('follow_no'),
                SuiteCorpCrmCustomerFollow::asField('created_id'),
                SuiteCorpCrmCustomerFollow::asField('follow_type'),
                SuiteCorpCrmCustomerFollow::asField('content'),
                SuiteCorpCrmCustomerFollow::asField('created_at'),
                SuiteCorpCrmCustomerFollow::asField('changed'),
            ])
            ->joinWith([
                'businessOpportunities' => function ($query) use ($params) {
                    //商机名称
                    $query->select([
                        SuiteCorpCrmBusinessOpportunities::asField('id'),
                        SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no'),
                        SuiteCorpCrmBusinessOpportunities::asField('name'),
                    ]);
                    if ($business_opportunities_name = self::getString($params,'business_opportunities_name')){
                        $query->andWhere(['like', SuiteCorpCrmBusinessOpportunities::asField('name'), $business_opportunities_name]);
                    }
                },
            ])
            ->with([
                'contact' => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomerContact::asField('id'),
                        SuiteCorpCrmCustomerContact::asField('suite_id'),
                        SuiteCorpCrmCustomerContact::asField('corp_id'),
                        SuiteCorpCrmCustomerContact::asField('customer_no'),
                        SuiteCorpCrmCustomerContact::asField('contact_no'),
                        SuiteCorpCrmCustomerContact::asField('contact_name'),
                    ]);
                },
                'creator' => function($query){
                    $query->select([
                        Account::asField('id'),
                        Account::asField('suite_id'),
                        Account::asField('corp_id'),
                        Account::asField('userid'),
                        Account::asField('nickname'),
                    ]);
                },
                'customer'
            ])
            ->when(self::getString($params,'customer_no'),function ($query, $customerNo){
                $query->andWhere([SuiteCorpCrmCustomerFollow::asField('customer_no') => $customerNo]);
            })
            ->when(self::getString($params,'business_opportunities_no'),function ($query, $businessOpportunitiesNo){
                $query->andWhere([SuiteCorpCrmCustomerFollow::asField('business_opportunities_no') => $businessOpportunitiesNo]);
            })
            ->when(self::getString($params,'contact_no'),function ($query, $contactNo){
                $query->andWhere([SuiteCorpCrmCustomerFollow::asField('contact_no') => $contactNo]);
            })
            ->when(self::getInt($params,'mine'), function ($query, $mine){
                $query->andWhere([SuiteCorpCrmCustomerFollow::asField('created_id') => auth()->accountId()]);
            })
            ->rangeGte(self::getInt($params,'created_start'), SuiteCorpCrmCustomerFollow::asField('created_at'))
            ->rangeLte(self::getInt($params,'created_end'), SuiteCorpCrmCustomerFollow::asField('created_at'))
            ->orderBy([
                SuiteCorpCrmCustomerFollow::asField('created_at') => SORT_DESC,
                SuiteCorpCrmCustomerFollow::asField('id') => SORT_DESC,
            ])
            ->myPage($params, function ($item){
                if (!is_null($item['changed'])){
                    $item['changed'] = json_decode( $item['changed'], true);
                }
                return $item;
            });
    }

}
