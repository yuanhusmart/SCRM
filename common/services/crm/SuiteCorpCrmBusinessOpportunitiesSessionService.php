<?php

namespace common\services\crm;

use common\models\Account;
use common\models\crm\SuiteCorpCrmBusinessOpportunities;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesContact;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesLink;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesSession;
use common\models\crm\SuiteCorpCrmCustomerContactInformation;
use common\services\Service;

class SuiteCorpCrmBusinessOpportunitiesSessionService extends Service
{
    /**
     * 通过客户联系人编号同步商机会话记录
     * @param string|array $contactNo
     * @return void
     * @throws \yii\db\Exception
     */
    public static function contact($contactNo)
    {
        //查询联系人的所有商机
        $businessOpportunitiesNos = SuiteCorpCrmBusinessOpportunitiesContact::find()
            ->select(['business_opportunities_no'])
            ->where(['contact_no' => $contactNo])
            ->groupBy('business_opportunities_no')
            ->column();
        foreach ($businessOpportunitiesNos as $businessOpportunitiesNo){
            self::sync($businessOpportunitiesNo);
        }
    }

    /**
     * 同步商会话
     * @param string $businessOpportunitiesNo
     * @return void
     * @throws \yii\db\Exception
     */
    public static function sync(string $businessOpportunitiesNo)
    {
        //获取商机所有联系人、关系人
        $bo = SuiteCorpCrmBusinessOpportunities::find()
            ->select([
                SuiteCorpCrmBusinessOpportunities::asField('suite_id'),
                SuiteCorpCrmBusinessOpportunities::asField('corp_id'),
                SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no'),
            ])
            ->with([
                'businessOpportunitiesContacts' => function ($query) {
                    $query->select([
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('business_opportunities_no'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('contact_no'),
                    ])
                        ->with([
                            'information' => function ($query) {
                                $query->select([
                                    SuiteCorpCrmCustomerContactInformation::asField('contact_no'),
                                    SuiteCorpCrmCustomerContactInformation::asField('contact_number'),
                                ])
                                    ->where([
                                        SuiteCorpCrmCustomerContactInformation::asField('contact_information_type') => SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4,
                                    ]);
                            }
                        ]);
                },
                'businessOpportunitiesLinks' => function ($query) {
                    $query->select([
                        SuiteCorpCrmBusinessOpportunitiesLink::asField('account_id'),
                        SuiteCorpCrmBusinessOpportunitiesLink::asField('business_opportunities_no'),
                        SuiteCorpCrmBusinessOpportunitiesLink::asField('business_opportunities_link_no'),
                    ])
                        ->with([
                           'account' => function ($query) {
                               $query->select([
                                   Account::asField('id'),
                                   Account::asField('userid'),
                               ]);
                           }
                        ]);
                }
            ])
            ->where([
                SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no') => $businessOpportunitiesNo,
            ])
            ->one();
        if (!$bo){
            return;
        }
        $bo = $bo->toArray();

        //取出所有内部员工userid
        $users = collect($bo['business_opportunities_links'] ?? [])
            ->map(function ($item) {
                $item['userid'] = $item['account']['userid'] ?? null;
                unset($item['account']);
                return $item;
            })
            ->filter(function ($item) {
                return $item['userid'] != null && $item['userid'] != '';
            })
            ->pluck(null, 'userid')
            ->toArray();

        //取出所有外部联系人的userid
        $contacts = [];
        foreach ($bo['business_opportunities_contacts'] ?? [] as $item){
            foreach ($item['information'] ?? [] as $information){
                $contacts[$information['contact_number']] = $information['contact_no'];
            }
        }

        //获取所有商机会话
        $sessions = SuiteCorpCrmBusinessOpportunitiesSession::find()
            ->select([
                SuiteCorpCrmBusinessOpportunitiesSession::asField('id'),
                SuiteCorpCrmBusinessOpportunitiesSession::asField('suite_id'),
                SuiteCorpCrmBusinessOpportunitiesSession::asField('corp_id'),
                SuiteCorpCrmBusinessOpportunitiesSession::asField('session_id'),
                SuiteCorpCrmBusinessOpportunitiesSession::asField('business_opportunities_no'),
            ])
            ->where([
                SuiteCorpCrmBusinessOpportunitiesSession::asField('business_opportunities_no') => $bo['business_opportunities_no'],
            ])
            ->asArray()
            ->all();
        foreach ($sessions as $k => $session){
            $sessions[$k] = [
                'uk' => md5(
                    sprintf(
                        '%s%s%s%s',
                        $session['suite_id'],
                        $session['corp_id'],
                        $session['session_id'],
                        $session['business_opportunities_no']
                    )
                ),
                'id' => $session['id']
            ];
        }
        $sessions = array_column($sessions,'id','uk');

        $currentSessions = [];
        foreach ($users as $userId => $user){
            foreach ($contacts as $contactUserId => $contactNo){
                $sessionId = dictSortMd5([$userId, $contactUserId]);
                $uk = md5(
                    sprintf(
                        '%s%s%s%s',
                        $bo['suite_id'],
                        $bo['corp_id'],
                        $sessionId,
                        $bo['business_opportunities_no']
                    )
                );
                $currentSessions[$uk] = [
                    'suite_id' => $bo['suite_id'],
                    'corp_id' => $bo['corp_id'],
                    'session_id' => $sessionId,
                    'business_opportunities_no' => $bo['business_opportunities_no'],
                    'contact_no' => $contactNo,
                    'business_opportunities_link_no' => $user['business_opportunities_link_no']
                ];
            }
        }

        $sessionsKeys = array_keys($sessions);
        $currentSessionsKeys = array_keys($currentSessions);
        $adds = array_diff($currentSessionsKeys,$sessionsKeys);
        $ins = [];
        foreach ($adds as $uk){
            if(isset($currentSessions[$uk])){
                $ins[] = $currentSessions[$uk];
            }
        }

        $dels = array_diff($sessionsKeys,$currentSessionsKeys);
        $delIds = [];
        foreach ($dels as $uk){
            if (isset($sessions[$uk])){
                $delIds[] = $sessions[$uk];
            }
        }
        $delIds && SuiteCorpCrmBusinessOpportunitiesSession::deleteAll(['id' => $delIds]);
        $ins && SuiteCorpCrmBusinessOpportunitiesSession::batchInsert($ins);
    }
}
