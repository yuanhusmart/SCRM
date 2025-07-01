<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\crm\SuiteCorpCrmBusinessOpportunities;
use common\models\crm\SuiteCorpCrmCustomer;
use common\models\crm\SuiteCorpCrmCustomerContact;
use common\models\crm\SuiteCorpCrmCustomerContactInformation;
use common\models\crm\SuiteCorpCrmCustomerLink;
use common\models\Account;
use common\models\SuiteCorpAccountsDepartment;
use common\services\Service;
use yii\db\ActiveRecord;
use yii\db\Exception;

class SuiteCorpCrmCustomerService extends Service
{
    /**
     * 新增客户
     * @param array $params
     * @return int
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function create(array $params)
    {
        $customer = new SuiteCorpCrmCustomer();
        $attributeLabels = $customer->attributeLabels();
        $customer->bindCorp();
        $customer->customer_name = self::getRequireString($params, $attributeLabels, 'customer_name');
        $customer->customer_address = self::getString($params, 'customer_address');
        $customer->network_address = self::getString($params, 'network_address');
        $customer->remark = self::getString($params, 'remark');
        $customer->source = self::getEnumInt($params, $attributeLabels, 'source', array_keys(SuiteCorpCrmCustomer::SOURCE_MAP));
        $customer->customer_no = self::getSnowflakeId();

        $contacts = self::getArray($params, 'contacts');
        $contactsIns = [];
        $informationIns = [];
        collect($contacts)->each(function ($item) use ($customer, &$contactsIns, &$informationIns) {
            try {
                $item['customer_no'] = $customer->customer_no;
                $item = SuiteCorpCrmCustomerContactService::createCustomerVerify($item);
                $information = $item['information'] ?? [];
                unset($item['information']);
                foreach ($information as $informationItem) {
                    $informationIns[] = $informationItem;
                }
                $contactsIns[] = $item;
            } catch (\Throwable $e) {
                throw new ErrException(Code::PARAMS_ERROR, $e->getMessage());
            }
        });

        $links = self::getArray($params, 'links');
        $linkIns = [];
        collect($links)->each(function ($item) use ($customer, &$linkIns) {
            try {
                $item['customer_no'] = $customer->customer_no;
                $linkIns[] = SuiteCorpCrmCustomerLinkService::createCustomerVerify($item);
            } catch (\Throwable $e) {
                throw new ErrException(Code::PARAMS_ERROR, $e->getMessage());
            }
        });
        //增加客户记录
        $customer->save();
        //增加联系人记录
        $contactsIns && SuiteCorpCrmCustomerContact::batchInsert($contactsIns);
        //增加联系人联系方式记录
        $informationIns && SuiteCorpCrmCustomerContactInformation::batchInsert($informationIns);
        //增加关系人记录
        $linkIns && SuiteCorpCrmCustomerLink::batchInsert($linkIns);
        //客户关系人数量校验
        self::verifyLinkCount($customer);
        return $customer->id;
    }

    /**
     * 批量新增客户
     * @param array $params
     * @return int
     * @throws ErrException
     * @throws Exception
     */
    public static function batchCreate(array $params)
    {
        foreach ($params as $param) {
            self::create($param);
        }
    }

    /**
     * 客户列表
     * @param $params
     * @return array|null
     */
    public static function index($params)
    {
        $list = SuiteCorpCrmCustomer::corp()
            ->select([
                SuiteCorpCrmCustomer::asField('id'),
                SuiteCorpCrmCustomer::asField('suite_id'),
                SuiteCorpCrmCustomer::asField('corp_id'),
                SuiteCorpCrmCustomer::asField('customer_no'),
                SuiteCorpCrmCustomer::asField('customer_name'),
                SuiteCorpCrmCustomer::asField('created_at'),
                SuiteCorpCrmCustomer::asField('updated_at'),
                SuiteCorpCrmCustomer::asField('last_follow_at'),
            ])
            ->joinWith([
                'links' => function ($query) use ($params) {
                    $query->select([
                        SuiteCorpCrmCustomerLink::asField('id'),
                        SuiteCorpCrmCustomerLink::asField('customer_no'),
                        SuiteCorpCrmCustomerLink::asField('account_id'),
                        SuiteCorpCrmCustomerLink::asField('relational'),
                    ])
                        ->dataPermission(SuiteCorpCrmCustomerLink::asField('account_id'))
                        ->when(self::getString($params, 'maintenance'), function ($query, $maintenance) {
                            //维护人姓名或电话
                            $query->joinWith([
                                'account' => function ($query) use ($maintenance) {
                                    $query->select([
                                        Account::asField('id'),
                                        Account::asField('suite_id'),
                                        Account::asField('corp_id'),
                                        Account::asField('userid'),
                                        Account::asField('nickname'),
                                    ])
                                        ->accountKeyword($maintenance);
                                }
                            ])->andWhere(['relational' => SuiteCorpCrmCustomerLink::RELATIONAL_1,]);
                        });
                },
            ])
            ->keyword(self::getString($params, 'customer'), SuiteCorpCrmCustomer::asField('customer_no'), SuiteCorpCrmCustomer::asField('customer_name'))
            ->rangeGte(self::getInt($params, 'last_follow_start'), SuiteCorpCrmCustomer::asField('last_follow_at'))
            ->rangeLte(self::getInt($params, 'last_follow_end'), SuiteCorpCrmCustomer::asField('last_follow_at'))
            ->rangeGte(self::getInt($params, 'update_start'), SuiteCorpCrmCustomer::asField('updated_at'))
            ->rangeLte(self::getInt($params, 'update_end'), SuiteCorpCrmCustomer::asField('updated_at'))
            // 联系人
            ->when(self::getString($params, 'contact_no'), function ($query, $contactNo) {
                $query->andWhere([
                    'exists',
                    SuiteCorpCrmCustomerContact::find()
                        ->andWhere('suite_corp_crm_customer.customer_no=suite_corp_crm_customer_contact.customer_no')
                        ->andWhere(['suite_corp_crm_customer_contact.contact_no' => $contactNo])
                ]);
            })
            ->groupBy([SuiteCorpCrmCustomer::asField('id'),])
            ->orderBy([SuiteCorpCrmCustomer::asField('last_follow_at') => SORT_DESC, SuiteCorpCrmCustomer::asField('id') => SORT_DESC,])
            ->myPage($params);
        $items = $list['items'] ?? [];
        if ($items) {
            $ids = collect(array_column($items, 'id'))->map(function ($id) {
                return intval($id);
            });
            $links = SuiteCorpCrmCustomer::corp()
                ->select([
                    SuiteCorpCrmCustomer::asField('id'),
                    SuiteCorpCrmCustomer::asField('customer_no'),
                ])
                ->with([
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
                            ]);
                    }
                ])
                ->andWhere(['id' => $ids])
                ->asArray()
                ->all();
            $links = array_column($links, null, 'id');
            foreach ($items as $k => $item) {
                if (isset($links[$item['id']])) {
                    $items[$k]['links'] = $links[$item['id']]['links'];
                } else {
                    $items[$k]['links'] = [];
                }
            }
            $list['items'] = $items;
        }
        return $list;
    }

    /**
     * 客户列表追加数据
     * @return array
     */
    public static function indexAppend($params)
    {
        $ids = self::getArray($params, 'ids');
        if (!$ids) {
            return [];
        }
        $customers = SuiteCorpCrmCustomer::corp()
            ->select(['id', 'customer_no',])
            ->andWhere(['id' => $ids])
            ->asArray()
            ->all();
        $customers = array_column($customers, 'customer_no', 'id');
        $customerNos = array_unique(array_values($customers));
        //商机总数 = 数据来源于当前该客户已关联的未完结商机数量
        $businessOpportunities = SuiteCorpCrmBusinessOpportunities::corp()
            ->select(['customer_no', 'COUNT(*) AS total',])
            ->andWhere(['customer_no' => $customerNos, 'status' => SuiteCorpCrmBusinessOpportunities::STATUS_1,])
            ->groupBy('customer_no')
            ->asArray()
            ->all();
        $businessOpportunities = array_column($businessOpportunities, 'total', 'customer_no');

        $result = [];
        foreach ($ids as $id) {
            $customerNo = $customers[$id] ?? '';
            $result[] = [
                'id' => $id,
                //商机总数 = 数据来源于当前该客户已关联的未完结商机数量
                'business_opportunities' => $customerNo ? intval(($businessOpportunities[$customerNo] ?? 0)) : 0,
                //订单总数 todo lyq 统计
                'order' => 0
            ];
        }
        return $result;
    }

    /**
     * 客户详情
     * @param $params
     * @return array|null
     */
    public static function info($params)
    {
        $id = self::getId($params);
        if (!$id) {
            return [];
        }

        $info = SuiteCorpCrmCustomer::corp()
            ->select([
                SuiteCorpCrmCustomer::asField('id'),
                SuiteCorpCrmCustomer::asField('suite_id'),
                SuiteCorpCrmCustomer::asField('corp_id'),
                SuiteCorpCrmCustomer::asField('customer_no'),
                SuiteCorpCrmCustomer::asField('customer_name'),
                SuiteCorpCrmCustomer::asField('customer_address'),
                SuiteCorpCrmCustomer::asField('network_address'),
                SuiteCorpCrmCustomer::asField('remark'),
                SuiteCorpCrmCustomer::asField('source'),
                SuiteCorpCrmCustomer::asField('created_at'),
                SuiteCorpCrmCustomer::asField('last_follow_at'),
            ])
            ->joinWith([
                'links' => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomerLink::asField('id'),
                        SuiteCorpCrmCustomerLink::asField('customer_no'),
                        SuiteCorpCrmCustomerLink::asField('link_no'),
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
                        ]);
                },
            ])
            ->andWhere([SuiteCorpCrmCustomer::asField('id') => $id,])
            ->one();
        if (!$info) {
            return [];
        }
        $info = $info->toArray();
        $info = SuiteCorpCrmCustomer::transform($info);
        //历史成交金额 = 客户下所有商机已回款金额总和
        $bossOpportunities = SuiteCorpCrmBusinessOpportunities::corp()
            ->select(['SUM(order_money) AS total',])
            ->andWhere([
                SuiteCorpCrmBusinessOpportunities::asField('customer_no') => $info['customer_no'],
                SuiteCorpCrmBusinessOpportunities::asField('status') => [SuiteCorpCrmBusinessOpportunities::STATUS_1, SuiteCorpCrmBusinessOpportunities::STATUS_2],
            ])
            ->asArray()
            ->one();
        $bossOpportunities = $bossOpportunities['total'] ?? null;
        $info['history_order_money'] = $bossOpportunities ?: '0.00';
        return $info;
    }

    /**
     * 修改客户信息
     * @param $params
     * @return bool
     * @throws ErrException
     */
    public static function save($params)
    {
        $customer = self::getOne($params);
        $allowFields = [
            'customer_name',
            'customer_address',
            'network_address',
            'remark',
            'source'
        ];
        $keys = self::includeKeys($params['data'] ?? [], $allowFields);
        $attributeLabels = $customer->attributeLabels();
        foreach ($keys as $key => $val) {
            if (!in_array($key, $allowFields)) {
                throw new ErrException(Code::PARAMS_ERROR, '缺少参数' . ($attributeLabels[$key] ?? $key));
            }
            switch ($key) {
                case 'source':
                    if (!isset(SuiteCorpCrmCustomer::SOURCE_MAP[$val])) {
                        throw new ErrException(Code::PARAMS_ERROR, '来源类型错误');
                    }
                    break;
            }
            $customer->$key = $val;
        }
        if (!$customer->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '保存失败');
        }
        return true;
    }

    /**
     * 修改客户关系人信息
     * @param $params
     * @return true
     * @throws ErrException|\yii\db\Exception
     */
    public static function saveLink($params)
    {
        $customer = self::getOne($params);
        $links = self::getArray($params, 'links');
        if (!$links) {
            throw new ErrException(Code::PARAMS_ERROR, '关系人不能为空');
        }

        $ins = [];
        foreach ($links as $link) {
            if (!isset($link['account_id'])) {
                throw new ErrException(Code::PARAMS_ERROR, '员工ID不能为空');
            }
            if (!isset($link['relational'])) {
                throw new ErrException(Code::PARAMS_ERROR, '关系不能为空');
            }
            if (!in_array($link['relational'], array_keys(SuiteCorpCrmCustomerLink::RELATIONAL_MAP))) {
                throw new ErrException(Code::PARAMS_ERROR, '关系类型不支持');
            }
            /** @var SuiteCorpCrmCustomerLink $linkItem */
            if (isset($link['id'])) {
                //修改
                $linkItem = SuiteCorpCrmCustomerLink::corp()
                    ->andWhere([
                        'AND',
                        ['=', 'id', $link['id']],
                        ['=', 'customer_no', $customer->customer_no],
                        ['=', 'relational', $link['relational']],
                    ])
                    ->one();
                if (!$linkItem) {
                    throw new ErrException(Code::BUSINESS_ABNORMAL, '关系人不存在');
                }
                $linkItem->account_id = $link['account_id'];
                if (!$linkItem->save()) {
                    throw new ErrException(Code::BUSINESS_ABNORMAL, '保存失败');
                }
            } else {
                $linkItem = SuiteCorpCrmCustomerLink::corp()
                    ->andWhere([
                        'AND',
                        ['=', 'customer_no', $customer->customer_no],
                        ['=', 'relational', $link['relational']],
                        ['=', 'account_id', $link['account_id']],
                    ])
                    ->exists();
                if (!$linkItem) {
                    //新增
                    $ins[] = [
                        'suite_id' => $customer->suite_id,
                        'corp_id' => $customer->corp_id,
                        'customer_no' => $customer->customer_no,
                        'link_no' => self::getSnowflakeId(),
                        'relational' => $link['relational'],
                        'account_id' => $link['account_id'],
                        'created_at' => time(),
                        'updated_at' => time(),
                    ];
                }
            }
        }

        if (!empty($ins)) {
            SuiteCorpCrmCustomerLink::batchInsert($ins);
        }

        self::verifyLinkCount($customer);
        return true;
    }

    /**
     * 客户名称唯一性校验
     * @param $params
     * @return array|ActiveRecord[]
     * @throws ErrException
     */
    public static function uniqueCustomerName($params)
    {
        $customerName = self::getString($params, 'customer_name');
        if (!$customerName) {
            throw new ErrException(Code::PARAMS_ERROR, '客户名称不能为空');
        }
        return SuiteCorpCrmCustomer::corp()
            ->select([
                SuiteCorpCrmCustomer::asField('id'),
                SuiteCorpCrmCustomer::asField('customer_no'),
                SuiteCorpCrmCustomer::asField('customer_name'),
            ])
            ->with([
                'contacts' => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomerContact::asField('customer_no'),
                        SuiteCorpCrmCustomerContact::asField('contact_name'),
                    ]);
                },
                'links' => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomerLink::asField('customer_no'),
                        SuiteCorpCrmCustomerLink::asField('relational'),
                        SuiteCorpCrmCustomerLink::asField('account_id'),
                    ])
                        ->with([
                            'account' => function ($query) {
                                $query->select([
                                    Account::asField('id'),
                                    Account::asField('suite_id'),
                                    Account::asField('corp_id'),
                                    Account::asField('userid'),
                                    Account::asField('nickname'),
                                ]);
                            }
                        ]);
                },
            ])
            ->andWhere([
                SuiteCorpCrmCustomer::asField('customer_name') => $customerName,
            ])
            ->asArray()
            ->all();
    }

    /**
     * 删除客户协作人信息
     * @param $params
     * @return true
     * @throws ErrException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function removeLink($params)
    {
        $linkNo = self::getString($params, 'link_no');
        if (!$linkNo) {
            throw new ErrException(Code::PARAMS_ERROR, '协作人编号不能为空');
        }

        $linkItem = SuiteCorpCrmCustomerLink::corp()
            ->andWhere([
                'AND',
                ['=', 'link_no', $linkNo],
                ['=', 'relational', SuiteCorpCrmCustomerLink::RELATIONAL_2],
            ])
            ->one();
        if (!$linkItem) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '协作人不存在');
        }
        if (!$linkItem->delete()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '删除失败');
        }
        return true;
    }

    /**
     * 转交客户
     * @param $params
     * @return void
     * @throws ErrException
     */
    public static function move($params)
    {
        $customerNo = self::getString($params, 'customer_no');
        if (!$customerNo) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少客户编号参数');
        }
        // 是否添加协作人:1是,2否
        $isAddCollaborator = self::getInt($params, 'is_add_collaborator');
        if (!in_array($isAddCollaborator, [1, 2])) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少是否添加协作人参数');
        }

        $accountId = self::getInt($params, 'account_id');
        if (!$accountId) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少员工ID参数');
        }

        /** @var SuiteCorpCrmCustomer $customer */
        $customer = SuiteCorpCrmCustomer::corp()->andWhere(['AND', ['=', 'customer_no', $customerNo],])->one();
        if (!$customer) {
            throw new ErrException(Code::PARAMS_ERROR, '客户不存在');
        }

        //该客户维护人将变更为接收人
        /** @var SuiteCorpCrmCustomerLink $whr */
        $whr = SuiteCorpCrmCustomerLink::corp()
            ->andWhere([
                'AND',
                ['=', 'customer_no', $customer->customer_no],
                ['=', 'relational', SuiteCorpCrmCustomerLink::RELATIONAL_1],
            ])
            ->one();
        if (!$whr) {
            throw new ErrException(Code::PARAMS_ERROR, '客户维护人不存在');
        }
        if ($whr->account_id != $accountId) {
            $whr->account_id = $accountId;
            if (!$whr->save()) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '客户维护人变更失败');
            }
        }

        //将我变更为该客户协作人
        if ($isAddCollaborator == 1) {
            $createdId = auth()->accountId();
            if (!$createdId) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '用户信息异常');
            }
            $links = SuiteCorpCrmCustomerLink::corp()
                ->select(['account_id'])
                ->andWhere([
                    'AND',
                    ['=', 'customer_no', $customer->customer_no],
                    ['=', 'relational', SuiteCorpCrmCustomerLink::RELATIONAL_2],
                ])
                ->column();
            if (!in_array($createdId, $links)) {
                if ((count($links) + 1) > 5) {
                    throw new ErrException(Code::BUSINESS_ABNORMAL, '一个客户最多支持5个协作人');
                }
                $link = new SuiteCorpCrmCustomerLink();
                $link->bindCorp();
                $link->customer_no = $customer->customer_no;
                $link->link_no = self::getSnowflakeId();
                $link->relational = SuiteCorpCrmCustomerLink::RELATIONAL_2;
                $link->account_id = $createdId;
                if (!$link->save()) {
                    throw new ErrException(Code::BUSINESS_ABNORMAL, '添加协作人失败');
                }
            }
        }
    }

    /**
     * 删除客户
     * @param $params
     * @return void
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function remove($params)
    {
        $customerNo = self::getString($params, 'customer_no');
        if (!$customerNo) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少客户编号参数');
        }

        /** @var SuiteCorpCrmCustomer $customer */
        $customer = SuiteCorpCrmCustomer::corp()->andWhere(['customer_no' => $customerNo])->one();
        if (!$customer) {
            throw new ErrException(Code::PARAMS_ERROR, '客户不存在');
        }
        $customer->deleted_at = time();
        //删除关联的联系人数据
        SuiteCorpCrmCustomerContact::updateAll([
            'deleted_at' => time(),
        ], [
            'AND',
            ['=', 'suite_id', $customer->suite_id],
            ['=', 'corp_id', $customer->corp_id],
            ['=', 'customer_no', $customer->customer_no],
            ['=', 'deleted_at', 0],
        ]);
        if (!$customer->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '删除失败');
        }
    }

    /**
     * 仅获取一个客户对象
     * @param $params
     * @return SuiteCorpCrmCustomer
     * @throws ErrException
     */
    public static function getOne($params): ?SuiteCorpCrmCustomer
    {
        $id = self::getId($params);
        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR, '客户ID不能为空');
        }
        /** @var SuiteCorpCrmCustomer $customer */
        $customer = SuiteCorpCrmCustomer::corp()->andWhere(['id' => $id])->one();
        if (!$customer) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '客户不存在');
        }
        return $customer;
    }

    /**
     * 客户关系人数量校验
     * @param SuiteCorpCrmCustomer $customer
     * @return void
     * @throws ErrException
     */
    public static function verifyLinkCount(SuiteCorpCrmCustomer $customer)
    {
        $relationals = SuiteCorpCrmCustomerLink::corp()->select(['relational'])->andWhere(['customer_no' => $customer->customer_no])->column();
        $relational1 = 0;
        $relational2 = 0;
        foreach ($relationals as $relational) {
            switch ($relational) {
                case SuiteCorpCrmCustomerLink::RELATIONAL_1:
                    $relational1++;
                    break;
                case SuiteCorpCrmCustomerLink::RELATIONAL_2:
                    $relational2++;
                    break;
            }
        }
        if ($relational1 > 1) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '数据验证错误,客户下不能存在多个维护人');
        }
        if ($relational2 > 5) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '数据验证错误,一个客户下不能存在超过5个协作人');
        }
    }

    /**
     * 根据入参自动创建客户
     * @param $params
     * @param int $source
     * @return mixed|string
     * @throws ErrException
     * @throws Exception
     */
    public static function findOrCreateByCustomer($params, int $source)
    {
        $customer = self::getArray($params, 'customer');
        if (!$customer) {
            throw new ErrException(Code::PARAMS_ERROR, '客户信息不能为空');
        }
        if (!isset($customer['customer_no'])) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少客户编号参数');
        }

        $customerNo = $customer['customer_no'];
        if (empty($customer['customer_no'])) {
            //新建客户
            if (!isset($customer['customer_name'])) {
                throw new ErrException(Code::PARAMS_ERROR, '缺少客户名称参数');
            }
            if (empty($customer['customer_name'])) {
                throw new ErrException(Code::PARAMS_ERROR, '客户名称不能为空');
            }
            $customerModel = new SuiteCorpCrmCustomer();
            $customerModel->bindCorp();
            $customerNo = $customerModel->customer_no = self::getSnowflakeId();
            $customerModel->customer_name = $customer['customer_name'];
            $customerModel->source = $source;
            if (!$customerModel->save()) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '客户信息保存失败');
            }

            //默认我是客户维护人，因为这是新建的客户
            $linkIns[] = SuiteCorpCrmCustomerLinkService::createCustomerVerify([
                'customer_no' => $customerNo,
                'account_id' => auth()->accountId(),
                'relational' => SuiteCorpCrmCustomerLink::RELATIONAL_1,
            ]);
            $linkIns && SuiteCorpCrmCustomerLink::batchInsert($linkIns);
        } else {
            //指定客户
            $exists = SuiteCorpCrmCustomer::corp()->andWhere(['customer_no' => $customerNo])->exists();
            if (!$exists) {
                throw new ErrException(Code::PARAMS_ERROR, sprintf('客户[%s]不存在', $customerNo));
            }
        }
        return $customerNo;
    }

    /**
     * 查询客户关系人列表
     * @param array $params
     * @return array|null
     */
    public static function linkIndex(array $params)
    {
        return SuiteCorpCrmCustomerLink::corp()
            ->when(self::getString($params, 'customer_no'), function ($query, $customerNo) {
                $query->andWhere(['customer_no' => $customerNo]);
            })
            ->when(self::getString($params, 'link_no'), function ($query, $linkNo) {
                $query->andWhere(['link_no' => $linkNo]);
            })
            ->when(self::getId($params), function ($query, $id) {
                $query->andWhere(['id' => $id]);
            })
            ->when(self::getString($params, 'relational'), function ($query, $relational) {
                $query->andWhere(['relational' => $relational]);
            })
            ->joinWith([
                'account' => function ($query) use ($params) {
                    $query->select([
                        Account::asField('id'),
                        Account::asField('suite_id'),
                        Account::asField('corp_id'),
                        Account::asField('userid'),
                        Account::asField('nickname'),
                    ])
                        ->accountKeyword(self::getString($params, 'keyword'));
                }
            ])
            ->dataPermission(SuiteCorpCrmCustomerLink::asField('account_id'))
            ->myPage($params);
    }
}
