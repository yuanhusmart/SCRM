<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\analysis\SuiteCorpAnalysisTaskDate;
use common\models\crm\SuiteCorpCrmBusinessOpportunities;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesContact;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesSession;
use common\models\crm\SuiteCorpCrmCustomer;
use common\models\crm\SuiteCorpCrmCustomerContact;
use common\models\crm\SuiteCorpCrmCustomerContactInformation;
use common\models\crm\SuiteCorpCrmCustomerContactTag;
use common\models\crm\SuiteCorpCrmCustomerFollow;
use common\models\crm\SuiteCorpCrmCustomerLink;
use common\models\Account;
use common\models\crm\SuiteCorpCrmCustomerRequirementTag;
use common\models\OtsSuiteWorkWechatChatData;
use common\models\SuiteCorpAccountsDepartment;
use common\sdk\TableStoreChain;
use common\services\Service;
use yii\base\InvalidConfigException;
use yii\db\Exception;

class SuiteCorpCrmCustomerContactService extends Service
{
    /**
     * 新增联系人
     * @param $params
     * @return void
     * @throws ErrException
     * @throws Exception
     */
    public static function create($params)
    {
        $model = new SuiteCorpCrmCustomerContact();
        $model->bindCorp();
        $customerNo     = SuiteCorpCrmCustomerService::findOrCreateByCustomer($params, SuiteCorpCrmCustomer::SOURCE_6);
        $contactsIns    = [];
        $informationIns = [];
        $contacts       = self::getArray($params, 'contacts');
        foreach ($contacts as $item) {
            try {
                $item['customer_no'] = $customerNo;
                $item                = self::createCustomerVerify($item);
                $informationIns      = $item['information'] ?? [];
                unset($item['information']);
                $contactsIns[] = $item;
            } catch (\Throwable $e) {
                throw new ErrException(Code::PARAMS_ERROR, $e->getMessage());
            }
        }
        //增加联系人记录
        $contactsIns && SuiteCorpCrmCustomerContact::batchInsert($contactsIns);
        //增加联系人联系方式记录
        $informationIns && SuiteCorpCrmCustomerContactInformation::batchInsert($informationIns);
    }

    /**
     * 联系人列表
     * @param array $params
     * @return array|null
     */
    public static function index(array $params)
    {
        return SuiteCorpCrmCustomerContact::corp()
            ->select([
                SuiteCorpCrmCustomerContact::asField('id'),
                SuiteCorpCrmCustomerContact::asField('suite_id'),
                SuiteCorpCrmCustomerContact::asField('corp_id'),
                SuiteCorpCrmCustomerContact::asField('customer_no'),
                SuiteCorpCrmCustomerContact::asField('contact_no'),
                SuiteCorpCrmCustomerContact::asField('contact_name'),
                SuiteCorpCrmCustomerContact::asField('type'),
                SuiteCorpCrmCustomerContact::asField('created_at'),
                SuiteCorpCrmCustomerContact::asField('updated_at'),
            ])
            ->joinWith([
                'information' => function ($query) use ($params) {
                    $query->select([
                        SuiteCorpCrmCustomerContactInformation::asField('id'),
                        SuiteCorpCrmCustomerContactInformation::asField('contact_no'),
                        SuiteCorpCrmCustomerContactInformation::asField('contact_information_type'),
                        SuiteCorpCrmCustomerContactInformation::asField('contact_number'),
                    ])
                        ->keyword(self::getString($params, 'contact_number'), SuiteCorpCrmCustomerContactInformation::asField('contact_number'), SuiteCorpCrmCustomerContactInformation::asField('contact_number'));
                },
            ])
            ->leftJoin(
                SuiteCorpCrmCustomerLink::tableName(),
                sprintf(
                    '%s = %s',
                    SuiteCorpCrmCustomerContact::asField('customer_no'),
                    SuiteCorpCrmCustomerLink::asField('customer_no'),
                )
            )
            ->dataPermission(SuiteCorpCrmCustomerLink::asField('account_id'))
            ->when(self::getString($params, 'customer'), function ($query, $customer) {
                $query->leftJoin(
                    SuiteCorpCrmCustomer::tableName(),
                    sprintf(
                        '%s = %s',
                        SuiteCorpCrmCustomerContact::asField('customer_no'),
                        SuiteCorpCrmCustomer::asField('customer_no'),
                    )
                )
                    ->keyword($customer, SuiteCorpCrmCustomer::asField('customer_no'), SuiteCorpCrmCustomer::asField('customer_name'));
            })
            ->when(self::getString($params, 'type'), function ($query, $contact_type) {
                $query->andWhere([SuiteCorpCrmCustomerContact::asField('type') => $contact_type]);
            })
            ->keyword(self::getString($params, 'contact'), SuiteCorpCrmCustomerContact::asField('contact_no'), SuiteCorpCrmCustomerContact::asField('contact_name'))
            ->rangeGte(self::getInt($params, 'update_start'), SuiteCorpCrmCustomerContact::asField('updated_at'))
            ->rangeLte(self::getInt($params, 'update_end'), SuiteCorpCrmCustomerContact::asField('updated_at'))
            ->groupBy(SuiteCorpCrmCustomerContact::asField('contact_no'))
            ->orderBy([SuiteCorpCrmCustomerContact::asField('created_at') => SORT_DESC, SuiteCorpCrmCustomerContact::asField('id') => SORT_DESC,])
            ->myPage($params, function ($item) {
                $informations = $item['information'] ?? [];
                foreach ($informations as &$information) {
                    switch ($information['contact_information_type']) {
                        case SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_1:
                            $information['contact_number'] = strEncode($information['contact_number'], 3, 4, 4);
                            break;
                        case SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_2:
                            $information['contact_number'] = strEncode($information['contact_number'], 0, 2, 6);
                            break;
                    }
                }
                unset($information);
                $item['information'] = $informations;
                return $item;
            });
    }

    /**
     * 联系人详情
     * @param $params
     * @return array|mixed|\yii\db\ActiveRecord
     */
    public static function info($params)
    {
        $contact = SuiteCorpCrmCustomerContact::corp()
            ->with([
                'information' => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomerContactInformation::asField('id'),
                        SuiteCorpCrmCustomerContactInformation::asField('contact_no'),
                        SuiteCorpCrmCustomerContactInformation::asField('contact_information_type'),
                        SuiteCorpCrmCustomerContactInformation::asField('contact_number'),
                    ]);
                },
                'tags'        => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomerContactTag::asField('id'),
                        SuiteCorpCrmCustomerContactTag::asField('contact_no'),
                        SuiteCorpCrmCustomerContactTag::asField('group_name'),
                        SuiteCorpCrmCustomerContactTag::asField('tag_name'),
                    ]);
                },
                'creator'     => function ($query) {
                    $query->select([
                        Account::asField('id'),
                        Account::asField('suite_id'),
                        Account::asField('corp_id'),
                        Account::asField('userid'),
                        Account::asField('nickname'),
                    ]);
                }
            ])
            ->when(self::getId($params), function ($query, $id) {
                $query->andWhere([
                    SuiteCorpCrmCustomerContact::asField('id') => $id,
                ]);
            })
            ->when(self::getString($params, 'contact_number'), function ($query, $contactNumber) {
                //查询联系人信息
                /** @var SuiteCorpCrmCustomerContactInformation $contactInformation */
                $contactInformation = SuiteCorpCrmCustomerContactInformation::find()->where(['contact_number' => $contactNumber])->one();
                $query->andWhere([
                    SuiteCorpCrmCustomerContact::asField('contact_no') => $contactInformation ? $contactInformation->contact_no : null,
                ]);
            })
            ->one();
        if (
            !$contact ||
            (!self::getString($params, 'contact_number') && !self::getId($params))
        ) {
            return [];
        }

        $appends   = self::indexAppend(['contact_no' => [$contact->contact_no]]);
        $customers = collect($appends)->where('contact_no', $contact->contact_no)->first();
        if (!$customers) {
            return [];
        }

        $contact              = $contact->toArray();
        $contact['customers'] = $customers['customers'];

        // 要查沟通信息, 还需要商机编号

        $contact['talk_days']         = 0; //沟通天数
        $contact['msg_count']         = 0; //消息条数
        $contact['call_count']        = 0; //通话次数
        $contact['ai_analysis_count'] = 0; //ai分析次数

        // 需要先找到 session_id
        $sessionIds = SuiteCorpCrmBusinessOpportunitiesSession::find()
            ->select('session_id')
            ->andWhere(['suite_id' => $contact['suite_id']])
            ->andWhere(['corp_id' => $contact['corp_id']])
            ->andWhere(['contact_no' => $contact['contact_no']])
            ->column();

        if ($sessionIds) {
            $chain = new TableStoreChain(
                OtsSuiteWorkWechatChatData::TABLE_NAME,
                OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
            );

            $response = (clone $chain)->whereTerm('suite_id', $contact['suite_id'])
                ->whereTerm('corp_id', $contact['corp_id'])
                ->whereTerms('session_id', $sessionIds)
                ->offsetLimit(0, 0)
                ->count('message_count', 'msgid')
                ->distinctCount('date_count', 'send_date')
                ->select([])
                ->getAggResult();

            $contact['talk_days'] = collect($response)->where('name', 'date_count')->first()['agg_result']['value'] ?? 0;
            $contact['msg_count'] = collect($response)->where('name', 'message_count')->first()['agg_result']['value'] ?? 0;

            $response = (clone $chain)->whereTerm('suite_id', $contact['suite_id'])
                ->whereTerm('corp_id', $contact['corp_id'])
                ->whereTerms('session_id', $sessionIds)
                ->whereTerm('msgtype', OtsSuiteWorkWechatChatData::MSG_TYPE_22)
                ->offsetLimit(0, 0)
                ->count('call_count', 'msgid')
                ->select([])
                ->getAggResult();

            $contact['call_count'] = collect($response)->where('name', 'call_count')->first()['agg_result']['value'] ?? 0;

            $contact['ai_analysis_count'] = SuiteCorpAnalysisTaskDate::find()->where(['session_id' => $sessionIds])->count();
        }


        return SuiteCorpCrmCustomerContact::transform($contact);
    }


    public function tableStore()
    {
        return new TableStoreChain(
            OtsSuiteWorkWechatChatData::TABLE_NAME,
            OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
        );
    }


    /**
     * 删除联系人
     * @param $params
     * @throws ErrException
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function remove($params)
    {
        $autoUnbind = $params['auto_unbind'] ?? 0;

        /** @var SuiteCorpCrmCustomerContact $contact */
        $contact = SuiteCorpCrmCustomerContact::corp()->andWhere(['id' => self::getId($params),])->one();
        if (!$contact) {
            throw new ErrException(Code::PARAMS_ERROR, '联系人不存在');
        }

        // 判断是不是客户的唯一联系人
        $count = SuiteCorpCrmCustomerContact::corp()
            ->andWhere(['customer_no' => $contact->customer_no])
            ->count();

        if ($count <= 0) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '该联系人是当前客户下的唯一联系人, 无法删除');
        }

        // 判断是否有关联商机
        if ($autoUnbind != YES) {
            $count = SuiteCorpCrmBusinessOpportunitiesContact::find()
                ->andWhere(['contact_no' => $contact->contact_no])
                ->count();

            if ($count > 0) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '该联系人有关联商机, 无法删除');
            }
        }

        SuiteCorpCrmCustomerContact::updateAll([
            'deleted_at' => time(),
        ], [
            'suite_id'   => $contact->suite_id,
            'corp_id'    => $contact->corp_id,
            'contact_no' => $contact->contact_no,
        ]);
        if (!$contact->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '删除联系人失败');
        }
        SuiteCorpCrmCustomerContactInformation::deleteAll(['contact_no' => $contact->contact_no,]);
        $contact->type == SuiteCorpCrmCustomerContact::TYPE_2 && SuiteCorpCrmBusinessOpportunitiesSessionService::contact($contact->contact_no);

        // 自动解绑商机联系人
        /*
         - 删除联系人，系统进行判定
           1、该联系人是否为该客户下唯一联系人

                 是：删除失败，弹出提示弹窗

                 否：进入第二步判断

           2、该联系人是否关联了该客户对应商机

                 是：弹出提示弹窗，询问是系统自动解绑并删除还是操作人手动解绑后，再来删除联系人；

                 否：删除成功

          - 系统自动解绑联系人商机并删除：
           若联系人为商机非主要联系人，则直接解绑，将该联系人从商机中删除；

            若联系人为商机主要联系人，则需要先将主要联系人设定为该商机下其他联系人，再将该联系人从商机中删除

            若联系人为商机主要联系人且商机中没有其他联系人，则解绑失败，联系人不允许删除（商机层面和客户层面都不允许删除）；
        */
        if ($autoUnbind == YES) {
            // 查出相关所有商机联系人
            $opportunities = SuiteCorpCrmBusinessOpportunitiesContact::corp()
                ->select('business_opportunities_no')
                ->andWhere(['contact_no' => $contact->contact_no])
                ->column();

            $items = SuiteCorpCrmBusinessOpportunitiesContact::corp()
                ->andWhere(['business_opportunities_no' => $opportunities])
                ->asArray()
                ->all();

            collect($items)
                ->groupBy('business_opportunities_no')
                ->each(function ($items) use ($contact) {
                    // 解绑商机
                    if ($items->count() <= 1) {
                        throw new ErrException(Code::BUSINESS_ABNORMAL, '该联系人是当前客户下的唯一联系人, 无法解绑');
                    }

                    // 指定其他联系人为主要联系人
                    if ($items->where('contant_no', $contact['contact_no'])->where('is_main', YES)->count()) {
                        $item = $items->where('contant_no', $contact['contact_no'])->where('is_main', '!=', YES)->first();
                        SuiteCorpCrmBusinessOpportunitiesContact::updateAll(['is_main' => YES], ['id' => $item['id']]);
                    }

                    // 解绑
                    SuiteCorpCrmBusinessOpportunitiesContact::deleteAll(['id' => $contact[['id']]]);
                });
        }
    }

    /**
     * 修改客户联系人姓名
     * @param array $params
     * @return void
     * @throws ErrException
     */
    public static function updateName(array $params)
    {
        /** @var SuiteCorpCrmCustomerContact $contact */
        $contact = SuiteCorpCrmCustomerContact::corp()->andWhere(['id' => self::getId($params),])->one();
        if (!$contact) {
            throw new ErrException(Code::PARAMS_ERROR, '联系人不存在');
        }
        $contact_name = self::getString($params, 'contact_name');
        if (!$contact_name) {
            throw new ErrException(Code::PARAMS_ERROR, '联系人姓名不能为空');
        }
        $contact->contact_name = $contact_name;
        if (!$contact->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '修改联系人姓名失败');
        }
    }

    /**
     * 验证是否存在
     * @param array $contactNumbers
     * @return array
     * @uses
     */
    public static function verifyExistsByParam(array $contactNumbers): array
    {
        if (!$contactNumbers) {
            return [];
        }
        $list = SuiteCorpCrmCustomerContact::corp()
            ->select([
                SuiteCorpCrmCustomerContact::asField('contact_no'),
                SuiteCorpCrmCustomerContact::asField('contact_name'),
            ])
            ->joinWith([
                'information' => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomerContactInformation::asField('contact_no'),
                        SuiteCorpCrmCustomerContactInformation::asField('contact_information_type'),
                        SuiteCorpCrmCustomerContactInformation::asField('contact_number'),
                    ]);
                },
            ])
            ->andWhere([
                'AND',
                ['IN', SuiteCorpCrmCustomerContactInformation::asField('contact_number'), $contactNumbers],
            ])
            ->groupBy(SuiteCorpCrmCustomerContact::asField('contact_no'))
            ->asArray()
            ->all();
        return collect($list)
            ->map(function ($item) {
                $item['information'] = collect($item['information'] ?? [])
                    ->map(function ($info) {
                        $info['contact_number'] = strEncode($info['contact_number'], 2, 2, 4);
                        return $info;
                    })
                    ->toArray();
                return $item;
            })->toArray();
    }

    /**
     * 新建联系人验证处理得到联系方式集合
     * @param $contactName
     * @param $information
     * @param $contactNo
     * @return mixed
     * @throws ErrException
     * @uses
     */
    public static function createVerify($contactNo, $contactName, $information)
    {
        $contactNumbers = [];
        foreach ($information as $k => $item) {
            try {
                $item['contact_no'] = $contactNo;
                $information[$k]    = SuiteCorpCrmCustomerContactInformationService::createCustomerVerify($item);
                !empty($information[$k]['contact_number']) && $contactNumbers[] = $information[$k]['contact_number'];
            } catch (\Throwable $e) {
                throw new ErrException(
                    Code::BUSINESS_ABNORMAL,
                    sprintf(
                        '联系方式[%s]%s',
                        isset($item['contact_number']) ? $item['contact_number'] : sprintf('第%s个', $k + 1),
                        $e->getMessage()
                    )
                );
            }
        }
        //验证联系人编号的合法性
        if (!$contactNumbers) {
            throw new ErrException(Code::PARAMS_ERROR, sprintf('联系人[%s]联系方式中至少需要有一个联系方式', $contactName));
        }
        $mobileContacts = self::verifyExistsByParam($contactNumbers);
        if ($mobileContacts) {
            throw new ErrException(Code::PARAMS_ERROR, sprintf('联系人[%s]已存在', $contactName));
        }
        return $information;
    }

    /**
     * 新增一个客户时的验证
     * @param array $params
     * @return array
     * @throws ErrException
     * @uses
     */
    public static function createCustomerVerify(array $params): array
    {
        $model = new SuiteCorpCrmCustomerContact();
        $model->bindCorp();
        $attributeLabels = $model->attributeLabels();
        $customerNo      = self::getRequireString($params, $attributeLabels, 'customer_no');
        $contactName     = self::getRequireString($params, $attributeLabels, 'contact_name');

        //联系人
        $contactNo = self::getString($params, 'contact_no');
        $type      = SuiteCorpCrmCustomerContact::TYPE_1;
        if ($contactNo) {
            //复用联系人时进行数据重复判断
            $exists = SuiteCorpCrmCustomerContact::corp()->andWhere(['AND', ['=', 'customer_no', $customerNo], ['=', 'contact_no', $contactNo],])->exists();
            if ($exists) {
                throw new ErrException(Code::PARAMS_ERROR, sprintf('该客户名下已有联系人[%s],请移除联系人之后重新操作', $contactName));
            }
            if (
                SuiteCorpCrmCustomerContactInformation::find()
                ->where([
                    'contact_no'               => $contactNo,
                    'contact_information_type' => SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4,
                ])
                ->exists()
            ) {
                $type = SuiteCorpCrmCustomerContact::TYPE_2;
            }
            $information = []; //复用的时候不需要保存联系方式
        } else {
            //新增联系人
            $contactNo   = self::getSnowflakeId();
            $information = self::getArray($params, 'information');
            $information = self::createVerify($contactNo, $contactName, $information);
            if (
                collect($information)
                ->where('contact_information_type', SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4)
                ->isNotEmpty()
            ) {
                $type = SuiteCorpCrmCustomerContact::TYPE_2;
            }
        }

        return [
            'suite_id'     => $model->suite_id,
            'corp_id'      => $model->corp_id,
            'customer_no'  => $customerNo,
            'contact_no'   => $contactNo,
            'contact_name' => $contactName,
            'information'  => $information,
            'type'         => $type,
            'created_id'   => auth()->accountId(),
            'created_at'   => time(),
            'updated_at'   => time(),
        ];
    }

    /**
     * 联系人列表追加数据
     * @return array
     * @uses
     */
    public static function indexAppend(array $params)
    {
        $contactNos = array_values(array_unique(self::getArray($params, 'contact_no')));
        $contacts   = SuiteCorpCrmCustomerContact::corp()
            ->select([
                SuiteCorpCrmCustomerContact::asField('customer_no'),
                SuiteCorpCrmCustomerContact::asField('contact_no'),
            ])
            ->andWhere(['contact_no' => $contactNos,])
            ->asArray()
            ->all();

        $customerNos = [];
        $contacts    = collect($contacts)
            ->each(function ($item) use (&$customerNos) {
                $customerNos[] = $item['customer_no'];
            })
            ->groupBy('contact_no')
            ->toArray();
        $customerNos = array_values(array_unique($customerNos));
        $customers   = SuiteCorpCrmCustomer::corp()
            ->select([
                SuiteCorpCrmCustomer::asField('id'),
                SuiteCorpCrmCustomer::asField('customer_no'),
                SuiteCorpCrmCustomer::asField('customer_name'),
            ])
            ->joinWith([
                'links' => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomerLink::asField('id'),
                        SuiteCorpCrmCustomerLink::asField('customer_no'),
                        SuiteCorpCrmCustomerLink::asField('account_id'),
                        SuiteCorpCrmCustomerLink::asField('relational'),
                    ])
                        ->joinWith([
                            'account' => function ($query) {
                                $query->select([
                                    Account::asField('id'),
                                    Account::asField('suite_id'),
                                    Account::asField('corp_id'),
                                    Account::asField('userid'),
                                    Account::asField('nickname'),
                                ])
                                    ->with([
                                        'accountsDepartmentByAccount' => function ($query) {
                                            $query->select([
                                                SuiteCorpAccountsDepartment::asField('id'),
                                                SuiteCorpAccountsDepartment::asField('suite_id'),
                                                SuiteCorpAccountsDepartment::asField('corp_id'),
                                                SuiteCorpAccountsDepartment::asField('userid'),
                                                SuiteCorpAccountsDepartment::asField('department_id'),
                                            ]);
                                        },
                                    ]);
                            }
                        ])
                        ->dataPermission(SuiteCorpCrmCustomerLink::asField('account_id'));
                },
            ])
            ->andWhere([SuiteCorpCrmCustomer::asField('customer_no') => $customerNos,])
            ->asArray()
            ->all();
        $customers   = array_column($customers, null, 'customer_no');
        $result      = [];
        collect($contacts)->each(function ($item) use ($customers, &$result) {
            $itemCustomers = [];
            collect($item)->each(function ($child) use ($customers, &$itemContactNo, &$itemCustomers) {
                $itemContactNo = $child['contact_no'];
                if (isset($customers[$child['customer_no']])) {
                    $itemCustomers[] = $customers[$child['customer_no']];
                }
            });
            $result[] = [
                'contact_no' => $itemContactNo,
                'customers'  => $itemCustomers,
            ];
        });
        return $result;
    }

    /**
     * 合并联系人
     * @param $params
     * @return bool
     * @throws ErrException
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function merge($params)
    {
        //得到历史联系人编号
        $currentId = self::getInt($params, 'current_id');
        $ids       = self::getArray($params, 'ids');
        if (!$ids) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '请选择需要合并的记录');
        }
        $ids[] = $currentId;
        $ids   = array_values(array_unique($ids));
        /** @var SuiteCorpCrmCustomerContact $currentContact */
        $currentContact = SuiteCorpCrmCustomerContact::corp()->andWhere(['id' => $currentId])->one();
        if (!$currentContact) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '当前联系人记录不存在');
        }
        $oldContactNos = SuiteCorpCrmCustomerContact::corp()->select(['contact_no'])->andWhere(['id' => $ids])->column();
        if (count($oldContactNos) != count($ids)) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '联系人记录异常');
        }
        $oldContactNos    = array_values(array_unique($oldContactNos));
        $contactNo        = snowflakeId(); //生成新的联系人编号
        $currentContactNo = $currentContact->contact_no; //当前联系人编号

        //开始合并
        self::merge_suite_corp_crm_customer_contact($contactNo, $currentContactNo, $oldContactNos);
        self::merge_suite_corp_crm_customer_contact_information($contactNo, $currentContactNo, $oldContactNos);
        self::merge_suite_corp_crm_customer_follow($contactNo, $oldContactNos);
        self::merge_suite_corp_crm_business_opportunities_contact($contactNo, $currentContactNo, $oldContactNos);
        self::merge_suite_corp_crm_customer_contact_tags($contactNo, $currentContactNo, $oldContactNos);
        self::merge_suite_corp_crm_customer_requirement_tags($contactNo, $currentContactNo, $oldContactNos);
        SuiteCorpCrmBusinessOpportunitiesSessionService::contact($contactNo);
        return true;
    }

    /**
     * 合并客户需求标签
     * @param string $contactNo 新联系人编号
     * @param string $currentContactNo 当前联系人编号(优先保留)
     * @param array $oldContactNos 旧联系人编号集合
     * @return void
     * @throws InvalidConfigException
     */
    private static function merge_suite_corp_crm_customer_requirement_tags(string $contactNo, string $currentContactNo, array $oldContactNos)
    {
        $arr    = SuiteCorpCrmCustomerRequirementTag::corp()
            ->select(['id', 'customer_no', 'group_name', 'tag_name', 'contact_no'])
            ->andWhere(['contact_no' => $oldContactNos])
            ->asArray()
            ->all();
        $unique = [];
        $del    = [];
        foreach ($arr as $k => $item) {
            if ($item['contact_no'] == $currentContactNo) {
                $index = md5(
                    sprintf(
                        '%s%s%s',
                        $item['customer_no'],
                        $item['group_name'],
                        $item['tag_name'],
                    )
                );
                if (!isset($unique[$index])) {
                    $unique[$index] = true;
                    unset($arr[$k]);
                }
            }
        }
        $arr = array_values($arr);
        foreach ($arr as $item) {
            $index = md5(
                sprintf(
                    '%s%s%s',
                    $item['customer_no'],
                    $item['group_name'],
                    $item['tag_name'],
                )
            );
            if (!isset($unique[$index])) {
                $unique[$index] = true;
            } else {
                $del[] = $item['id'];
            }
        }
        $del && SuiteCorpCrmCustomerRequirementTag::deleteAll(['id' => $del]);
        SuiteCorpCrmCustomerRequirementTag::updateAll(['contact_no' => $contactNo,], ['contact_no' => $oldContactNos,]);
    }


    /**
     * 合并客户联系人标签
     * @param string $contactNo 新联系人编号
     * @param string $currentContactNo 当前联系人编号(优先保留)
     * @param array $oldContactNos 旧联系人编号集合
     * @return void
     * @throws InvalidConfigException
     */
    private static function merge_suite_corp_crm_customer_contact_tags(string $contactNo, string $currentContactNo, array $oldContactNos)
    {
        $arr    = SuiteCorpCrmCustomerContactTag::corp()
            ->select(['id', 'group_name', 'tag_name', 'contact_no'])
            ->andWhere(['contact_no' => $oldContactNos])
            ->asArray()
            ->all();
        $unique = [];
        $del    = [];
        foreach ($arr as $k => $item) {
            if ($item['contact_no'] == $currentContactNo) {
                $index = md5(
                    sprintf(
                        '%s%s',
                        $item['group_name'],
                        $item['tag_name'],
                    )
                );
                if (!isset($unique[$index])) {
                    $unique[$index] = true;
                    unset($arr[$k]);
                }
            }
        }
        $arr = array_values($arr);
        foreach ($arr as $item) {
            $index = md5(
                sprintf(
                    '%s%s',
                    $item['group_name'],
                    $item['tag_name'],
                )
            );
            if (!isset($unique[$index])) {
                $unique[$index] = true;
            } else {
                $del[] = $item['id'];
            }
        }
        $del && SuiteCorpCrmCustomerContactTag::deleteAll(['id' => $del]);
        SuiteCorpCrmCustomerContactTag::updateAll(['contact_no' => $contactNo,], ['contact_no' => $oldContactNos,]);
    }

    /**
     * 合并商机联系人
     * @param string $contactNo 新联系人编号
     * @param string $currentContactNo 当前联系人编号(优先保留)
     * @param array $oldContactNos 旧联系人编号集合
     * @return void
     * @throws InvalidConfigException
     */
    private static function merge_suite_corp_crm_business_opportunities_contact(string $contactNo, string $currentContactNo, array $oldContactNos)
    {
        $arr    = SuiteCorpCrmBusinessOpportunitiesContact::corp()
            ->select(['id', 'customer_no', 'business_opportunities_no', 'contact_no', 'role'])
            ->andWhere(['contact_no' => $oldContactNos])
            ->asArray()
            ->all();
        $unique = [];
        $del    = [];
        foreach ($arr as $k => $item) {
            if ($item['contact_no'] == $currentContactNo) {
                $index = md5(
                    sprintf(
                        '%s%s%s',
                        $item['customer_no'],
                        $item['business_opportunities_no'],
                        $item['role'],
                    )
                );
                if (!isset($unique[$index])) {
                    $unique[$index] = true;
                    unset($arr[$k]);
                }
            }
        }
        $arr = array_values($arr);
        foreach ($arr as $item) {
            $index = md5(
                sprintf(
                    '%s%s%s',
                    $item['customer_no'],
                    $item['business_opportunities_no'],
                    $item['role'],
                )
            );
            if (!isset($unique[$index])) {
                $unique[$index] = true;
            } else {
                $del[] = $item['id'];
            }
        }
        $del && SuiteCorpCrmBusinessOpportunitiesContact::deleteAll(['id' => $del]);
        SuiteCorpCrmBusinessOpportunitiesContact::updateAll(['contact_no' => $contactNo,], ['contact_no' => $oldContactNos,]);
    }

    /**
     * 合并企业的CRM客户联系人
     * @param string $contactNo 新联系人编号
     * @param string $currentContactNo 当前联系人编号(优先保留)
     * @param array $oldContactNos 旧联系人编号集合
     * @return void
     * @throws InvalidConfigException
     */
    public static function merge_suite_corp_crm_customer_contact(string $contactNo, string $currentContactNo, array $oldContactNos)
    {
        $arr    = SuiteCorpCrmCustomerContact::corp()
            ->select(['id', 'suite_id', 'corp_id', 'customer_no', 'contact_no'])
            ->andWhere(['contact_no' => $oldContactNos])
            ->asArray()
            ->all();
        $unique = [];
        $del    = [];
        foreach ($arr as $k => $item) {
            if ($item['contact_no'] == $currentContactNo) {
                $index = $item['customer_no'];
                if (!isset($unique[$index])) {
                    $unique[$index] = true;
                    unset($arr[$k]);
                }
            }
        }
        $arr = array_values($arr);
        foreach ($arr as $item) {
            $index = $item['customer_no'];
            if (!isset($unique[$index])) {
                $unique[$index] = true;
            } else {
                $del[] = $item['id'];
            }
        }
        $del && SuiteCorpCrmCustomerContact::deleteAll(['id' => $del]);
        SuiteCorpCrmCustomerContact::updateAll(['contact_no' => $contactNo,], ['contact_no' => $oldContactNos,]);
    }

    /**
     * 合并联系方式
     * @param string $contactNo 新联系人编号
     * @param string $currentContactNo 当前联系人编号(优先保留)
     * @param array $oldContactNos 旧联系人编号集合
     * @return void
     * @throws InvalidConfigException
     */
    private static function merge_suite_corp_crm_customer_contact_information(string $contactNo, string $currentContactNo, array $oldContactNos)
    {
        $informations = SuiteCorpCrmCustomerContactInformation::find()
            ->select(['id', 'contact_no', 'contact_information_type', 'contact_number'])
            ->where(['contact_no' => $oldContactNos])
            ->asArray()
            ->all();
        $unique       = [];
        $del          = [];
        foreach ($informations as $k => $information) {
            if ($information['contact_no'] == $currentContactNo) {
                $index = md5(sprintf('%s%s', $information['contact_information_type'], $information['contact_number']));
                if (!isset($unique[$index])) {
                    $unique[$index] = true;
                    unset($informations[$k]);
                }
            }
        }
        $informations = array_values($informations);
        foreach ($informations as $information) {
            $index = md5(sprintf('%s%s', $information['contact_information_type'], $information['contact_number']));
            if (!isset($unique[$index])) {
                $unique[$index] = true;
            } else {
                $del[] = $information['id'];
            }
        }
        $del && SuiteCorpCrmCustomerContactInformation::deleteAll(['id' => $del]);
        SuiteCorpCrmCustomerContactInformation::updateAll(['contact_no' => $contactNo,], ['contact_no' => $oldContactNos,]);
    }

    /**
     * 合并跟进记录
     * @param string $contactNo 新联系人编号
     * @param array $oldContactNos 旧联系人编号集合
     * @return void
     * @throws InvalidConfigException
     */
    public static function merge_suite_corp_crm_customer_follow(string $contactNo, array $oldContactNos)
    {
        SuiteCorpCrmCustomerFollow::updateAll(['contact_no' => $contactNo,], ['contact_no' => $oldContactNos,]);
        //更新关联客户记录的最近跟进时间
        $customers = SuiteCorpCrmCustomerFollow::corp()
            ->select(['customer_no', 'MAX(created_at) as last_follow_at'])
            ->andWhere(['contact_no' => $contactNo])
            ->groupBy(['customer_no'])
            ->asArray()
            ->all();
        foreach ($customers as $customer) {
            SuiteCorpCrmCustomer::updateAll(
                ['last_follow_at' => $customer['last_follow_at']],
                ['customer_no' => $customer['customer_no']]
            );
        }

        $opportunities = SuiteCorpCrmCustomerFollow::corp()
            ->select(['business_opportunities_no', 'MAX(created_at) as last_follow_at'])
            ->andWhere(['contact_no' => $contactNo])
            ->groupBy(['business_opportunities_no'])
            ->asArray()
            ->all();
        foreach ($opportunities as $opportunity) {
            SuiteCorpCrmBusinessOpportunities::updateAll(
                ['last_follow_at' => $opportunity['last_follow_at']],
                ['business_opportunities_no' => $opportunity['business_opportunities_no']]
            );
        }
    }

    /**
     * 更新联系人记录更新时间
     * @param $contactNo
     * @return void
     * @throws InvalidConfigException
     */
    public static function syncUpdated($contactNo)
    {
        SuiteCorpCrmCustomerContact::updateAll(['updated_at' => time()], ['contact_no' => $contactNo,]);
    }
}
