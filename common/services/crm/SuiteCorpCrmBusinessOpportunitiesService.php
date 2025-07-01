<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\analysis\SuiteCorpAnalysisTaskDate;
use common\models\analysis\SuiteCorpAnalysisTaskResultDetails;
use common\models\crm\SuiteCorpCrmBusinessOpportunities;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesContact;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesLink;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesSop;
use common\models\crm\SuiteCorpCrmCustomer;
use common\models\crm\SuiteCorpCrmCustomerContact;
use common\models\crm\SuiteCorpCrmCustomerContactInformation;
use common\models\Account;
use common\models\crm\SuiteCorpCrmCustomerContactTag;
use common\models\crm\SuiteCorpCrmCustomerRequirementTag;
use common\models\SuiteCorpAccountsDepartment;
use common\models\SuiteCorpIndustry;
use common\models\SuiteSop;
use common\models\SuiteSopVersion;
use common\services\Service;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\db\Expression;

class SuiteCorpCrmBusinessOpportunitiesService extends Service
{
    /**
     * 验证:同行业同客户一周内不能重复创建
     * @param string $industryNo
     * @param string $customerNo
     * @return void
     * @throws ErrException
     */
    public static function beforeWeekValidate(string $industryNo, string $customerNo)
    {
        $exists = SuiteCorpCrmBusinessOpportunities::corp()
            ->andWhere([
                'industry_no' => $industryNo,
                'customer_no' => $customerNo,
                'status' => [SuiteCorpCrmBusinessOpportunities::STATUS_1, SuiteCorpCrmBusinessOpportunities::STATUS_2],
            ])
            ->andWhere(['>', 'created_at', time() - 7 * 86400])
            ->exists();
        if ($exists) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '同行业同客户一周内不能重复创建');
        }
    }

    /**
     * 新增商机
     * @param array $params
     * @return int
     * @throws ErrException|\yii\db\Exception
     */
    public static function create(array $params)
    {
        $createdId = auth()->accountId();
        if (!$createdId) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '用户信息异常');
        }

        //行业
        $industryNo = self::getString($params, 'industry_no');
        if (!$industryNo) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '行业id不能为空');
        }
        $exists = SuiteCorpIndustry::corp()->andWhere(['industry_no' => $industryNo])->exists();
        if (!$exists) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '行业不存在');
        }

        //写入商机记录
        $businessOpportunities = new SuiteCorpCrmBusinessOpportunities();
        $attributeLabels = $businessOpportunities->attributeLabels();
        $businessOpportunities->bindCorp();
        $businessOpportunities->business_opportunities_no = self::getSnowflakeId();
        $businessOpportunities->name = self::getRequireString($params, $attributeLabels, 'name');
        $businessOpportunities->estimate_sale_money = self::getFloat($params, 'estimate_sale_money');
        $businessOpportunities->source = self::getEnumInt($params, $attributeLabels, 'source', array_keys(SuiteCorpCrmBusinessOpportunities::SOURCE_MAP));
        $businessOpportunities->remark = self::getString($params, 'remark');
        $businessOpportunities->created_id = $createdId;
        $customerNo = $businessOpportunities->customer_no = SuiteCorpCrmCustomerService::findOrCreateByCustomer($params, $businessOpportunities->source);
        $businessOpportunities->industry_no = $industryNo;
        self::beforeWeekValidate($industryNo, $customerNo);
        if (!$businessOpportunities->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '创建失败');
        }
        self::createBusinessOpportunitiesSopByIndustry($industryNo, $customerNo, $businessOpportunities->business_opportunities_no);

        //联系人
        $contactNo = self::autoCreateContact($customerNo, $createdId, $params);
        $contact = new SuiteCorpCrmBusinessOpportunitiesContact();
        $contact->bindCorp();
        $contact->customer_no = $customerNo;
        $contact->business_opportunities_no = $businessOpportunities->business_opportunities_no;
        $contact->contact_no = $contactNo;
        $contact->is_main = SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_1;
        $contact->role = self::getString($params, 'role');
        if (!$contact->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '保存联系人失败');
        }

        //关系人
        $links = collect(self::getArray($params, 'links'))
            ->map(function ($link) use ($customerNo, $businessOpportunities) {
                $link['customer_no'] = $customerNo;
                $link['business_opportunities_no'] = $businessOpportunities->business_opportunities_no;
                return SuiteCorpCrmBusinessOpportunitiesLinkService::createVerify($link);
            })
            ->toArray();
        !empty($links) && SuiteCorpCrmBusinessOpportunitiesLink::batchInsert($links);

        $followContent = self::getString($params, 'follow_content');
        if ($followContent) {
            SuiteCorpCrmCustomerFollowService::create([
                'customer_no' => $customerNo,
                'business_opportunities_no' => $businessOpportunities->business_opportunities_no,
                'contact_no' => $contactNo,
                'content' => $followContent,
            ]);
        }

        SuiteCorpCrmBusinessOpportunitiesLinkService::checkCreated($customerNo, $businessOpportunities->business_opportunities_no);
        SuiteCorpCrmBusinessOpportunitiesSessionService::sync($businessOpportunities->business_opportunities_no);
        return $businessOpportunities->id;
    }

    /**
     * 保存商机信息
     * @param array $params
     * @return true
     * @throws ErrException
     * @throws InvalidConfigException
     * @throws Exception
     */
    public static function save(array $params)
    {
        $businessOpportunitiesNo = self::getString($params, 'business_opportunities_no');
        if (!$businessOpportunitiesNo) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少商机编号参数');
        }
        $createdId = auth()->accountId();
        if (!$createdId) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '用户信息异常');
        }

        /** @var SuiteCorpCrmBusinessOpportunities $businessOpportunities */
        $businessOpportunities = SuiteCorpCrmBusinessOpportunities::corp()->andWhere(['business_opportunities_no' => $businessOpportunitiesNo,])->one();
        if (!$businessOpportunities) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '商机不存在');
        }

        $is_analyze = $businessOpportunities->is_analyze;//是否分析
        $status = $businessOpportunities->status;//状态

        //商机名称
        if ($name = self::getString($params, 'name')) {
            $businessOpportunities->name = $name;
        }

        //商机来源
        if ($source = self::getInt($params, 'source')) {
            if (!in_array($source, array_keys(SuiteCorpCrmBusinessOpportunities::SOURCE_MAP))) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '商机来源参数错误');
            }
            $businessOpportunities->source = $source;
        }

        //预计销售金额
        if (isset($params['estimate_sale_money'])) {
            $businessOpportunities->estimate_sale_money = $params['estimate_sale_money'];
        }

        //已回款金额
        if (isset($params['order_money'])) {
            $businessOpportunities->order_money = $params['order_money'];
        }

        //商机等级
        if (isset($params['level'])) {
            $businessOpportunities->level = $params['level'];
        }

        //备注
        if (isset($params['remark'])) {
            $businessOpportunities->remark = $params['remark'];
        }

        //客户
        $oldCustomerNo = $businessOpportunities->customer_no;
        $businessOpportunities->customer_no = SuiteCorpCrmCustomerService::findOrCreateByCustomer($params, $businessOpportunities->source);
        $customerNo = $businessOpportunities->customer_no;
        $isChangeCustomer = $oldCustomerNo != $customerNo;//是否修改了客户
        $businessOpportunitiesNo = $businessOpportunities->business_opportunities_no;
        $isSyncBusinessOpportunitiesSession = false;

        //变更客户联动更新相关数据
        if ($isChangeCustomer) {
            $isSyncBusinessOpportunitiesSession = true;
            //联系人
            SuiteCorpCrmBusinessOpportunitiesContact::updateAll([
                'customer_no' => $customerNo,
            ], [
                'suite_id' => $businessOpportunities->suite_id,
                'corp_id' => $businessOpportunities->corp_id,
                'customer_no' => $oldCustomerNo,
                'business_opportunities_no' => $businessOpportunitiesNo,
            ]);
            //关系人
            SuiteCorpCrmBusinessOpportunitiesLink::updateAll([
                'customer_no' => $customerNo,
            ], [
                'suite_id' => $businessOpportunities->suite_id,
                'corp_id' => $businessOpportunities->corp_id,
                'customer_no' => $oldCustomerNo,
                'business_opportunities_no' => $businessOpportunitiesNo,
            ]);
            //sop
            SuiteCorpCrmBusinessOpportunitiesSop::updateAll([
                'customer_no' => $customerNo,
            ], [
                'suite_id' => $businessOpportunities->suite_id,
                'corp_id' => $businessOpportunities->corp_id,
                'customer_no' => $oldCustomerNo,
                'business_opportunities_no' => $businessOpportunitiesNo,
            ]);
        }

        //主要联系人
        $contactParam = self::getArray($params, 'contact');
        if ($contactParam) {
            $contactNo = self::autoCreateContact($customerNo, $createdId, $params);
            $isSyncBusinessOpportunitiesSession = true;
            //取消主要联系人
            SuiteCorpCrmBusinessOpportunitiesContact::updateAll([
                'is_main' => SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_2,
            ], [
                'suite_id' => $businessOpportunities->suite_id,
                'corp_id' => $businessOpportunities->corp_id,
                'customer_no' => $customerNo,
                'business_opportunities_no' => $businessOpportunitiesNo,
                'is_main' => SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_1,
            ]);
            //设置主要联系人
            SuiteCorpCrmBusinessOpportunitiesContact::updateOrCreate([
                'suite_id' => $businessOpportunities->suite_id,
                'corp_id' => $businessOpportunities->corp_id,
                'customer_no' => $customerNo,
                'business_opportunities_no' => $businessOpportunitiesNo,
                'contact_no' => $contactNo,
            ], [
                'suite_id' => $businessOpportunities->suite_id,
                'corp_id' => $businessOpportunities->corp_id,
                'customer_no' => $customerNo,
                'business_opportunities_no' => $businessOpportunitiesNo,
                'contact_no' => $contactNo,
                'is_main' => SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_1,
            ]);
        }

        //跟进人
        $followId = self::getInt($params, 'follow_id');
        if ($followId) {
            /** @var SuiteCorpCrmBusinessOpportunitiesLink $follow */
            $follow = SuiteCorpCrmBusinessOpportunitiesLink::corp()
                ->andWhere([
                    'customer_no' => $customerNo,
                    'business_opportunities_no' => $businessOpportunitiesNo,
                    'relational' => SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_1,
                ])
                ->one();
            if (!$follow) {
                $follow = new SuiteCorpCrmBusinessOpportunitiesLink();
                $follow->bindCorp();
                $follow->customer_no = $customerNo;
                $follow->business_opportunities_no = $businessOpportunitiesNo;
                $follow->business_opportunities_link_no = self::getSnowflakeId();
                $follow->relational = SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_1;
                $follow->account_id = $followId;
            } else {
                $follow->account_id = $followId;
            }
            if (!$follow->save()) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '跟进人记录保存失败');
            }
            $isSyncBusinessOpportunitiesSession = true;
        }

        //协作人
        if (isset($params['collaborator_ids'])) {
            $collaboratorIds = self::getArray($params, 'collaborator_ids');
            $collaboratorIds = array_unique($collaboratorIds);
            $collaborators = SuiteCorpCrmBusinessOpportunitiesLink::corp()
                ->select('account_id')
                ->andWhere([
                    'customer_no' => $customerNo,
                    'business_opportunities_no' => $businessOpportunitiesNo,
                    'relational' => SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_2,
                ])
                ->column();
            $addLinks = array_diff($collaboratorIds, $collaborators);
            $deleteLinks = array_diff($collaborators, $collaboratorIds);
            if (!empty($addLinks)) {
                if (count($addLinks) + count($collaborators) - count($deleteLinks) > 5) {
                    throw new ErrException(Code::BUSINESS_ABNORMAL, '协作人个数不能超过5个');
                }
                $t = time();
                foreach ($addLinks as $k => $addLink) {
                    $addLinks[$k] = [
                        'suite_id' => $businessOpportunities->suite_id,
                        'corp_id' => $businessOpportunities->corp_id,
                        'customer_no' => $customerNo,
                        'business_opportunities_no' => $businessOpportunitiesNo,
                        'business_opportunities_link_no' => self::getSnowflakeId(),
                        'relational' => SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_2,
                        'account_id' => $addLink,
                        'created_at' => $t,
                        'updated_at' => $t,
                    ];
                }
                SuiteCorpCrmBusinessOpportunitiesLink::batchInsert($addLinks);
            }
            if (!empty($deleteLinks)) {
                SuiteCorpCrmBusinessOpportunitiesLink::deleteAll([
                    'suite_id' => $businessOpportunities->suite_id,
                    'corp_id' => $businessOpportunities->corp_id,
                    'customer_no' => $customerNo,
                    'business_opportunities_no' => $businessOpportunitiesNo,
                    'relational' => SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_2,
                    'account_id' => $deleteLinks,
                ]);
            }
        }

        //行业
        $isChangeIndustry = false;//是否变更了行业
        $industryNo = self::getString($params, 'industry_no');
        if ($industryNo) {
            $exists = SuiteCorpIndustry::corp()->andWhere(['industry_no' => $industryNo])->exists();
            if (!$exists) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '行业不存在');
            }
            if ($industryNo != $businessOpportunities->industry_no) {
                $isChangeIndustry = true;
            }
            $businessOpportunities->industry_no = $industryNo;
        }
        if ($isChangeIndustry) {
            //变更行业，sop阶段处理化为新的sop阶段
            SuiteCorpCrmBusinessOpportunitiesSop::deleteAll([
                'suite_id' => $businessOpportunities->suite_id,
                'corp_id' => $businessOpportunities->corp_id,
                //这里用老的客户编号，是因为更换客户之后，sop阶段不会变更
                'customer_no' => $oldCustomerNo,
                'business_opportunities_no' => $businessOpportunitiesNo,
            ]);
            self::createBusinessOpportunitiesSopByIndustry($industryNo, $customerNo, $businessOpportunitiesNo);
            $businessOpportunities->status = SuiteCorpCrmBusinessOpportunities::STATUS_1;
        } else {
            //没有变更行业，就判断是否变更了SOP阶段
            $currentSopItemsId = self::getInt($params, 'current_sop_items_id');
            if ($currentSopItemsId) {
                /** @var SuiteCorpCrmBusinessOpportunitiesSop $sop */
                $sop = SuiteCorpCrmBusinessOpportunitiesSop::corp()
                    ->andWhere([
                        'customer_no' => $customerNo,
                        'business_opportunities_no' => $businessOpportunitiesNo,
                    ])
                    ->one();
                if (!$sop) {
                    throw new ErrException(Code::BUSINESS_ABNORMAL, 'SOP阶段配置不存在,请先进行行业适配');
                }
                $sopContent = $sop->content ?? null;
                if (is_array($sopContent)) {
                    $sopItems = $sopContent['items'] ?? null;
                    if (is_array($sopItems)) {
                        $sopItemIds = array_column($sopItems, 'id');
                        if (!in_array($currentSopItemsId, $sopItemIds)) {
                            throw new ErrException(Code::BUSINESS_ABNORMAL, 'SOP阶段不存在');
                        }
                        $sopContent['items'] = collect($sopItems)
                            ->map(function ($sopItem) use ($currentSopItemsId, &$is_analyze, &$status) {
                                if ($sopItem['id'] == $currentSopItemsId) {
                                    $sopItem['is_current_step'] = true;
                                    if ($sopItem['name'] == '成交' || $sopItem['name'] == '作废') {
                                        $is_analyze = SuiteCorpCrmBusinessOpportunities::IS_ANALYZE_2;//不分析
                                        $status = $sopItem['name'] == '成交' ? SuiteCorpCrmBusinessOpportunities::STATUS_2 : SuiteCorpCrmBusinessOpportunities::STATUS_3;
                                    } else {
                                        $is_analyze = SuiteCorpCrmBusinessOpportunities::IS_ANALYZE_1;//要分析
                                        $status = SuiteCorpCrmBusinessOpportunities::STATUS_1;
                                    }
                                } else {
                                    $sopItem['is_current_step'] = false;
                                }
                                return $sopItem;
                            })
                            ->toArray();
                        $sop->content = $sopContent;
                        if (!$sop->save()) {
                            throw new ErrException(Code::BUSINESS_ABNORMAL, 'SOP阶段保存失败');
                        }
                    } else {
                        throw new ErrException(Code::BUSINESS_ABNORMAL, 'SOP阶段配置不存在,请先进行行业适配');
                    }
                } else {
                    throw new ErrException(Code::BUSINESS_ABNORMAL, 'SOP阶段配置不存在,请先进行行业适配');
                }
            }
        }

        if ($isChangeCustomer || $isChangeIndustry){
            self::beforeWeekValidate($businessOpportunities->industry_no, $businessOpportunities->customer_no);
        }

        //是否分析
        if ($is_analyze_params = self::getInt($params, 'is_analyze')) {
            if (!in_array($is_analyze_params, array_keys(SuiteCorpCrmBusinessOpportunities::IS_ANALYZE_MAP))) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '商机分析参数错误');
            }
            $is_analyze = $is_analyze_params;
        }
        $businessOpportunities->is_analyze = $is_analyze;
        $businessOpportunities->status = $status;
        if (
            $businessOpportunities->status == SuiteCorpCrmBusinessOpportunities::STATUS_2 &&
            $businessOpportunities->deal_at == 0
        ){
            $businessOpportunities->deal_at = time();
        }

        if (!$businessOpportunities->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '商机保存失败');
        }

        $isSyncBusinessOpportunitiesSession && SuiteCorpCrmBusinessOpportunitiesSessionService::sync($businessOpportunitiesNo);

        return true;
    }

    /**
     * 根据行业初始化创建SOP阶段配置数据
     * @param string $industryNo
     * @param string $customerNo
     * @param string $businessOpportunitiesNo
     * @return void
     * @throws ErrException
     */
    public static function createBusinessOpportunitiesSopByIndustry(string $industryNo, string $customerNo, string $businessOpportunitiesNo)
    {
        //SOP阶段配置
        /** @var SuiteSop $sop */
        $sop = SuiteSop::corp()->andWhere(['industry_no' => $industryNo])->one();
        if (!$sop) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, 'SOP阶段配置不存在,请先进行行业适配');
        }
        //获取对应版本的快照数据
        $sopVersion = SuiteSopVersion::find()->where(['sop_no' => $sop->sop_no, 'version' => $sop->version,])->one();
        if (!$sopVersion) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, 'SOP阶段配置快照不存在');
        }
        $sopVersionContent = $sopVersion->content ?? null;
        if (is_null($sopVersionContent) || !$sopVersionContent || !is_array($sopVersionContent)) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, 'SOP阶段配置快照内容无效');
        }

        $sopVersionContent['items'] = collect($sopVersionContent['items'] ?? [])
            ->map(function ($sopVersionContentItem) {
                //待办事项
                $todo_items = $sopVersionContentItem['todo_item'] ?? null;
                if (is_array($todo_items)) {
                    foreach ($todo_items as $todo_itemK => $todo_item) {
                        $todo_items[$todo_itemK] = [
                            'text' => $todo_item,
                            'is_completed' => false,//是否已完成
                        ];
                    }
                }
                $sopVersionContentItem['todo_item'] = $todo_items;

                //完成度百分比
                $sopVersionContentItem['step'] = '0';

                //结束时间
                $sopVersionContentItem['end_at'] = null;
                //开始时间
                $sopVersionContentItem['start_at'] = null;

                //跟进人
                $sopVersionContentItem['follow'] = null;

                //状态
                $sopVersionContentItem['state'] = '未开始';

                //AI分析结果
                $sopVersionContentItem['ai_analysis'] = null;//sop判定
                $sopVersionContentItem['suggest_phrases'] = null;//话术建议
                $sopVersionContentItem['follow_up_record'] = null;//沟通摘要
                $sopVersionContentItem['attention'] = null;//注意事项

                //是否当前阶段
                $sopVersionContentItem['is_current_step'] = $sopVersionContentItem['sort'] == 1;

                return $sopVersionContentItem;
            })
            ->toArray();

        $sop = new SuiteCorpCrmBusinessOpportunitiesSop();
        $sop->bindCorp();
        $sop->customer_no = $customerNo;
        $sop->business_opportunities_no = $businessOpportunitiesNo;
        $sop->content = $sopVersionContent;
        if (!$sop->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '保存SOP阶段配置失败');
        }
    }

    /**
     * 自动创建联系人
     * @param $customerNo
     * @param $createdId
     * @param $params
     * @return float|int|string|null
     * @throws ErrException
     * @throws Exception
     */
    public static function autoCreateContact($customerNo, $createdId, $params)
    {
        $contactParam = self::getArray($params, 'contact');
        $contactNo = self::getString($contactParam, 'contact_no');//联系人编号
        $contactName = self::getString($contactParam, 'contact_name');//联系人姓名
        $information = self::getArray($contactParam, 'information');//联系方式
        $isCreateInformation = true;//是否创建联系方式
        if ($contactNo) {
            //复用联系人
            $exists = SuiteCorpCrmCustomerContact::corp()->andWhere(['customer_no' => $customerNo, 'contact_no' => $contactNo,])->exists();
            if ($exists) {
                return $contactNo;
            }
            //这里走的是另外一种新建联系人数据，只新增联系人数据，不增加联系方式
            //查询联系人是否存在
            $informationSql = SuiteCorpCrmCustomerContactInformation::find()->where(['contact_no' => $contactNo])->asArray()->all();
            if (!$informationSql){
                throw new ErrException(Code::BUSINESS_ABNORMAL, '联系人编号无效');
            }
            //联系人在其他客户中，直接复用过来
            $hasQw = collect($informationSql)->where('contact_information_type', SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4)->isNotEmpty();
            /** @var SuiteCorpCrmCustomerContact $OldContact */
            $OldContact = SuiteCorpCrmCustomerContact::corp()->andWhere(['contact_no' => $contactNo])->one();
            $contactName = $OldContact->contact_name;
            $isCreateInformation = false;
        } else {
            //新建联系人
            $contactNo = self::getSnowflakeId();
            if (!$information) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '联系人联系方式号码不能为空');
            }
            if (!$contactName) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '联系人姓名不能为空');
            }
            $hasQw = collect($information)->where('contact_information_type', SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4)->isNotEmpty();
        }

        //增加联系人记录
        $crmContact = new SuiteCorpCrmCustomerContact();
        $crmContact->bindCorp();
        $crmContact->customer_no = $customerNo;
        $crmContact->contact_no = $contactNo;
        $crmContact->contact_name = $contactName;
        $crmContact->created_id = $createdId;
        $crmContact->type = $hasQw ? SuiteCorpCrmCustomerContact::TYPE_2 : SuiteCorpCrmCustomerContact::TYPE_1;;
        if (!$crmContact->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '保存联系人失败');
        }
        if ($isCreateInformation) {
            $informationIns = [];
            $information = SuiteCorpCrmCustomerContactService::createVerify($contactNo, $contactName, $information);
            foreach ($information as $informationItem) {
                $informationIns[] = $informationItem;
            }
            //增加联系人联系方式记录
            $informationIns && SuiteCorpCrmCustomerContactInformation::batchInsert($informationIns);
        }
        return $contactNo;
    }

    /**
     * 商机列表
     * @param $params
     * @return array|void
     * @throws ErrException
     */
    public static function index($params)
    {
        //跟进人姓名或电话
        $sort = self::getInt($params, 'sort');//0：默认排序, 1：跟进时间倒序,2:跟进时间顺序,3：创建时间倒序,4：创建时间顺序
        switch ($sort) {
            case 1:
                $orderBy = [
                    SuiteCorpCrmBusinessOpportunities::asField('last_follow_at') => SORT_DESC,
                    SuiteCorpCrmBusinessOpportunities::asField('created_at') => SORT_DESC,
                    SuiteCorpCrmBusinessOpportunities::asField('id') => SORT_DESC,
                ];
                break;
            case 2:
                $orderBy = [
                    SuiteCorpCrmBusinessOpportunities::asField('last_follow_at') => SORT_ASC,
                    SuiteCorpCrmBusinessOpportunities::asField('created_at') => SORT_DESC,
                    SuiteCorpCrmBusinessOpportunities::asField('id') => SORT_DESC,
                ];
                break;
            case 3:
                $orderBy = [
                    SuiteCorpCrmBusinessOpportunities::asField('created_at') => SORT_DESC,
                    SuiteCorpCrmBusinessOpportunities::asField('id') => SORT_DESC,
                ];
                break;
            case 4:
                $orderBy = [
                    SuiteCorpCrmBusinessOpportunities::asField('created_at') => SORT_ASC,
                    SuiteCorpCrmBusinessOpportunities::asField('id') => SORT_DESC,
                ];
                break;
            default:
                $orderBy = [
                    SuiteCorpCrmBusinessOpportunities::asField('status') => SORT_ASC,
                    SuiteCorpCrmBusinessOpportunities::asField('last_follow_at') => SORT_DESC,
                    SuiteCorpCrmBusinessOpportunities::asField('created_at') => SORT_DESC,
                    SuiteCorpCrmBusinessOpportunities::asField('id') => SORT_DESC,
                ];
                break;
        }

        return SuiteCorpCrmBusinessOpportunities::corp()
            ->select([
                SuiteCorpCrmBusinessOpportunities::asField('id'),
                SuiteCorpCrmBusinessOpportunities::asField('suite_id'),
                SuiteCorpCrmBusinessOpportunities::asField('corp_id'),
                SuiteCorpCrmBusinessOpportunities::asField('customer_no'),
                SuiteCorpCrmBusinessOpportunities::asField('industry_no'),
                SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no'),
                SuiteCorpCrmBusinessOpportunities::asField('name'),
                SuiteCorpCrmBusinessOpportunities::asField('status'),
                SuiteCorpCrmBusinessOpportunities::asField('loss_risk'),
                SuiteCorpCrmBusinessOpportunities::asField('estimate_sale_money'),
                SuiteCorpCrmBusinessOpportunities::asField('level'),
                SuiteCorpCrmBusinessOpportunities::asField('order_money'),
            ])
            ->joinWith([
                //客户
                'customer' => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomer::asField('id'),
                        SuiteCorpCrmCustomer::asField('suite_id'),
                        SuiteCorpCrmCustomer::asField('corp_id'),
                        SuiteCorpCrmCustomer::asField('customer_no'),
                        SuiteCorpCrmCustomer::asField('customer_name'),
                    ]);
                },
            ])
            ->with([
                //行业
                'industry' => function ($query) {
                    $query->select([
                        SuiteCorpIndustry::asField('id'),
                        SuiteCorpIndustry::asField('industry_no'),
                        SuiteCorpIndustry::asField('name'),
                    ]);
                },
                //主要联系人
                'businessOpportunitiesContacts' => function ($query) {
                    $query->select([
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('id'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('suite_id'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('corp_id'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('customer_no'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('contact_no'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('business_opportunities_no'),
                    ])
                        ->joinWith([
                            'contact' => function ($query) {
                                $query->select([
                                    SuiteCorpCrmCustomerContact::asField('id'),
                                    SuiteCorpCrmCustomerContact::asField('suite_id'),
                                    SuiteCorpCrmCustomerContact::asField('corp_id'),
                                    SuiteCorpCrmCustomerContact::asField('customer_no'),
                                    SuiteCorpCrmCustomerContact::asField('contact_no'),
                                    SuiteCorpCrmCustomerContact::asField('contact_name'),
                                ]);
                            }
                        ])
                        ->where([
                            SuiteCorpCrmBusinessOpportunitiesContact::asField('is_main') => SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_1,
                        ]);
                },
                //关系人
                'businessOpportunitiesLinks' => function ($query) {
                    $query->select([
                        SuiteCorpCrmBusinessOpportunitiesLink::asField('business_opportunities_no'),
                        SuiteCorpCrmBusinessOpportunitiesLink::asField('relational'),
                        SuiteCorpCrmBusinessOpportunitiesLink::asField('account_id'),
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
                //SOP
                'sop' => function ($query) {
                    $query->select([
                        SuiteCorpCrmBusinessOpportunitiesSop::asField('id'),
                        SuiteCorpCrmBusinessOpportunitiesSop::asField('business_opportunities_no'),
                        SuiteCorpCrmBusinessOpportunitiesSop::asField('content'),
                    ]);
                },
            ])
            ->when(self::getString($params, 'contact_no'), function ($query, $contactNo) {
                if (!is_numeric($contactNo)) {
                    $contactNo = SuiteCorpCrmCustomerContact::corp()->select(['contact_no'])->andWhere(['like', 'contact_name', $contactNo])->column();
                }
                $query->leftJoin(
                    SuiteCorpCrmBusinessOpportunitiesContact::tableName(),
                    sprintf(
                        '%s = %s',
                        SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('business_opportunities_no'),
                    )
                )
                    ->andWhere([SuiteCorpCrmBusinessOpportunitiesContact::asField('contact_no') => $contactNo,]);
            })
            ->innerJoin(
                SuiteCorpCrmBusinessOpportunitiesLink::tableName(),
                sprintf(
                    '%s = %s',
                    SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no'),
                    SuiteCorpCrmBusinessOpportunitiesLink::asField('business_opportunities_no'),
                )
            )
            // 权限控制
            ->accessControl(SuiteCorpCrmBusinessOpportunitiesLink::asField('account_id'))
            ->when(self::getString($params, 'follow'), function ($query, $follow) use ($params) {
                if (is_numeric($follow)) {
                    $accountIds = Account::corp()->select(['id'])->andWhere(['mobile' => $follow])->column();
                } else {
                    $suiteId = auth()->suiteId();
                    $corpId = auth()->corpId();
                    $api = \common\services\SuiteService::contactSearch([
                        //查询的企业corpid
                        'auth_corpid' => $corpId,
                        //搜索关键词。当查询用户时应为用户名称、名称拼音或者英文名；当查询部门时应为部门名称或者部门名称拼音
                        'query_word' => $follow,
                        //查询类型 1：查询用户，返回用户userid列表 2：查询部门，返回部门id列表。 不填该字段或者填0代表同时查询部门跟用户
                        'query_type' => 1,
                        //查询范围，仅查询类型包含用户时有效。 0：只查询在职用户 1：同时查询在职和离职用户（离职用户仅当离职前有激活企业微信才可以被搜到）
                        //'query_range' => 1,
                        //查询返回的最大数量，默认为50，最多为200，查询返回的数量可能小于limit指定的值。limit会分别控制在职数据和离职数据的数量。
                        'limit' => 200,
                        // 精确匹配的字段。1：匹配用户名称或者部门名称 2：匹配用户英文名。不填则为模糊匹配
                        'full_match_field' => 1,
                    ]);
                    $userids = $api['query_result']['user']['userid'] ?? [];
                    $accountIds = Account::find()->select(['id'])->where(['suite_id' => $suiteId, 'corp_id' => $corpId, 'userid' => $userids,])->column();
                }
                // $accountIds[] = auth()->accountId();
                $accountIds = array_values(array_unique($accountIds));
                // 构建子查询
                $query->andWhere([
                    'exists',
                    SuiteCorpCrmBusinessOpportunitiesLink::find()->andWhere(sprintf('%s.business_opportunities_no = %s.business_opportunities_no',
                        SuiteCorpCrmBusinessOpportunities::tableName(),
                        SuiteCorpCrmBusinessOpportunitiesLink::tableName()
                    ))
                    ->andWhere(['account_id' => array_map('intval', $accountIds)])
                ]);
            })
            ->when(self::getInt($params, 'loss_risk'), function ($query, $lossRisk) {
                $query->andWhere([SuiteCorpCrmBusinessOpportunities::asField('loss_risk') => $lossRisk]);
            })
            ->when(isset($params['status']) && !empty($params['status']), function ($query) use ($params) {
                $query->andWhere([SuiteCorpCrmBusinessOpportunities::asField('status') => $params['status']]);
            })
            ->when(self::getString($params, 'customer_no'), function ($query, $customerNo) {
                if (!is_numeric($customerNo)) {
                    $customerNo = SuiteCorpCrmCustomer::corp()->select(['customer_no'])->andWhere(['like', 'customer_name', $customerNo])->column();
                }
                $query->andWhere(['IN', SuiteCorpCrmBusinessOpportunities::asField('customer_no'), $customerNo]);
            })
            ->keyword(self::getString($params, 'name'), SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no'), SuiteCorpCrmBusinessOpportunities::asField('name'))
            ->rangeGte(self::getInt($params, 'update_start'), SuiteCorpCrmBusinessOpportunities::asField('updated_at'))
            ->rangeLte(self::getInt($params, 'update_end'), SuiteCorpCrmBusinessOpportunities::asField('updated_at'))
            // 重点商机
            ->when(self::getInt($params, 'is_important'), function ($query, $isImportant) {
                // 重点商机：当前登录人为跟进人或协助人，商机创建时间7天内，客户质量等级为最高两级，按照最后跟进时间由远及近排序
                $query->andWhere([
                    'AND',
                    [SuiteCorpCrmBusinessOpportunities::asField('level') => ['S级', 'A级']],
                    [
                        'exists',
                        SuiteCorpCrmBusinessOpportunitiesLink::find()
                            ->andWhere(sprintf('%s.business_opportunities_no=%s.business_opportunities_no', SuiteCorpCrmBusinessOpportunities::tableName(), SuiteCorpCrmBusinessOpportunitiesLink::tableName()))
                            ->andWhere([SuiteCorpCrmBusinessOpportunitiesLink::asField('account_id') => auth()->accountId()])
                    ]
                ]);
            })
            // 新增商机
            ->when(self::getInt($params, 'is_new'), function ($query, $isNew) {
                $query->andWhere([
                    'exists',
                    SuiteCorpCrmBusinessOpportunitiesLink::find()
                        ->andWhere(sprintf('%s.business_opportunities_no=%s.business_opportunities_no', SuiteCorpCrmBusinessOpportunities::tableName(), SuiteCorpCrmBusinessOpportunitiesLink::tableName()))
                ]);
            })
            // 待跟进商机
            ->when(self::getInt($params, 'is_follow'), function ($query, $isFollow) {
                $query->andWhere([
                    'AND',
                    [SuiteCorpCrmBusinessOpportunities::asField('status') => 1],
                    // 超过3天未跟进的商机
                    ['<=', SuiteCorpCrmBusinessOpportunities::asField('last_follow_at'), time() - 60 * 60 * 24 * 3]
                ]);
            })
            ->when(self::getInt($params, 'account_id'), function ($query, $accountId) {
                $query->andWhere([
                    'exists',
                    SuiteCorpCrmBusinessOpportunitiesLink::find()->andWhere(sprintf('%s.business_opportunities_no = %s.business_opportunities_no',
                        SuiteCorpCrmBusinessOpportunities::tableName(),
                        SuiteCorpCrmBusinessOpportunitiesLink::tableName()
                    ))
                        ->andWhere(['account_id' => $accountId])
                ]);
            })
            ->groupBy([SuiteCorpCrmBusinessOpportunities::asField('id')])
            ->orderBy($orderBy)
            ->myPage($params, function ($item) {
                $sop = $item['sop'] ?? null;
                if (is_array($sop)) {
                    $item['sop']['content'] = json_decode($sop['content'], true);
                }
                return $item;
            });
    }

    /**
     * 商机详情
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function info($params)
    {
        $businessOpportunitiesNo = self::getString($params, 'business_opportunities_no');
        if (!$businessOpportunitiesNo) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少商机编号参数');
        }

        $info = SuiteCorpCrmBusinessOpportunities::corp()
            ->select([
                SuiteCorpCrmBusinessOpportunities::asField('id'),
                SuiteCorpCrmBusinessOpportunities::asField('suite_id'),
                SuiteCorpCrmBusinessOpportunities::asField('corp_id'),
                SuiteCorpCrmBusinessOpportunities::asField('customer_no'),
                SuiteCorpCrmBusinessOpportunities::asField('industry_no'),
                SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no'),
                SuiteCorpCrmBusinessOpportunities::asField('level'),
                SuiteCorpCrmBusinessOpportunities::asField('name'),
                SuiteCorpCrmBusinessOpportunities::asField('estimate_sale_money'),
                SuiteCorpCrmBusinessOpportunities::asField('order_money'),
                SuiteCorpCrmBusinessOpportunities::asField('deal_rate'),
                SuiteCorpCrmBusinessOpportunities::asField('status'),
                SuiteCorpCrmBusinessOpportunities::asField('loss_risk'),
                SuiteCorpCrmBusinessOpportunities::asField('source'),
                SuiteCorpCrmBusinessOpportunities::asField('remark'),
                SuiteCorpCrmBusinessOpportunities::asField('created_at'),
                SuiteCorpCrmBusinessOpportunities::asField('updated_at'),
                SuiteCorpCrmBusinessOpportunities::asField('deal_at'),
                SuiteCorpCrmBusinessOpportunities::asField('last_follow_at'),
                SuiteCorpCrmBusinessOpportunities::asField('created_id'),
            ])
            ->joinWith([
                //客户
                'customer' => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomer::asField('id'),
                        SuiteCorpCrmCustomer::asField('corp_id'),
                        SuiteCorpCrmCustomer::asField('customer_no'),
                        SuiteCorpCrmCustomer::asField('customer_name'),
                    ]);
                },
            ])
            ->with([
                //创建人
                'creator' => function ($query) {
                    $query->select([
                        Account::asField('id'),
                        Account::asField('suite_id'),
                        Account::asField('corp_id'),
                        Account::asField('userid'),
                        Account::asField('nickname'),
                    ]);
                },
                //行业
                'industry' => function ($query) {
                    $query->select([
                        SuiteCorpIndustry::asField('id'),
                        SuiteCorpIndustry::asField('industry_no'),
                        SuiteCorpIndustry::asField('name'),
                    ]);
                },
                //主要联系人
                'businessOpportunitiesContacts' => function ($query) {
                    $query->select([
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('id'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('suite_id'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('corp_id'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('customer_no'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('contact_no'),
                        SuiteCorpCrmBusinessOpportunitiesContact::asField('business_opportunities_no'),
                    ])
                        ->joinWith([
                            //客户联系人
                            'contact' => function ($query) {
                                $query->select([
                                    SuiteCorpCrmCustomerContact::asField('id'),
                                    SuiteCorpCrmCustomerContact::asField('suite_id'),
                                    SuiteCorpCrmCustomerContact::asField('corp_id'),
                                    SuiteCorpCrmCustomerContact::asField('customer_no'),
                                    SuiteCorpCrmCustomerContact::asField('contact_no'),
                                    SuiteCorpCrmCustomerContact::asField('contact_name'),
                                ])
                                    ->with([
                                        'tags' => function ($query) {
                                            $query->select([
                                                SuiteCorpCrmCustomerContactTag::asField('id'),
                                                SuiteCorpCrmCustomerContactTag::asField('contact_no'),
                                                SuiteCorpCrmCustomerContactTag::asField('group_name'),
                                                SuiteCorpCrmCustomerContactTag::asField('tag_name'),
                                            ]);
                                        },
                                        'requirementTags' => function ($query) {
                                            $query->select([
                                                SuiteCorpCrmCustomerRequirementTag::asField('id'),
                                                SuiteCorpCrmCustomerRequirementTag::asField('suite_id'),
                                                SuiteCorpCrmCustomerRequirementTag::asField('corp_id'),
                                                SuiteCorpCrmCustomerRequirementTag::asField('customer_no'),
                                                SuiteCorpCrmCustomerRequirementTag::asField('contact_no'),
                                                SuiteCorpCrmCustomerRequirementTag::asField('group_name'),
                                                SuiteCorpCrmCustomerRequirementTag::asField('tag_name'),
                                            ]);
                                        }
                                    ]);
                            }
                        ])
                        ->where([
                            SuiteCorpCrmBusinessOpportunitiesContact::asField('is_main') => SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_1,
                        ]);
                },
                //关系人
                'businessOpportunitiesLinks' => function ($query) {
                    $query->select([
                        SuiteCorpCrmBusinessOpportunitiesLink::asField('business_opportunities_no'),
                        SuiteCorpCrmBusinessOpportunitiesLink::asField('relational'),
                        SuiteCorpCrmBusinessOpportunitiesLink::asField('account_id'),
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
                //SOP
                'sop' => function ($query) {
                    $query->select([
                        SuiteCorpCrmBusinessOpportunitiesSop::asField('id'),
                        SuiteCorpCrmBusinessOpportunitiesSop::asField('business_opportunities_no'),
                        SuiteCorpCrmBusinessOpportunitiesSop::asField('content'),
                    ]);
                },
            ])
            ->leftJoin(
                SuiteCorpCrmBusinessOpportunitiesLink::tableName(),
                sprintf(
                    '%s = %s',
                    SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no'),
                    SuiteCorpCrmBusinessOpportunitiesLink::asField('business_opportunities_no'),
                )
            )
            ->dataPermission(SuiteCorpCrmBusinessOpportunitiesLink::asField('account_id'))
            ->andWhere([
                SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no') => $businessOpportunitiesNo,
            ])
            ->groupBy([SuiteCorpCrmBusinessOpportunities::asField('id')])
            ->one();
        if ($info) {
            $info = $info->toArray();
            $sop = $info['sop'] ?? null;
            if (is_array($sop)) {
                if (!is_array($sop['content'])) {
                    $info['sop']['content'] = json_decode($sop['content'], true);
                }
            }
        }

        return SuiteCorpCrmBusinessOpportunities::transform($info);
    }

    /**
     * 作废商机
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function cancel($params)
    {
        $businessOpportunitiesNo = self::getString($params, 'business_opportunities_no');
        if (!$businessOpportunitiesNo) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少商机编号参数');
        }
        /** @var SuiteCorpCrmBusinessOpportunities $businessOpportunities */
        $businessOpportunities = SuiteCorpCrmBusinessOpportunities::corp()
            ->andWhere([
                SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no') => $businessOpportunitiesNo,
            ])
            ->one();
        if (!$businessOpportunities) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '商机不存在');
        }
        $businessOpportunities->status = SuiteCorpCrmBusinessOpportunities::STATUS_3;
        if (!$businessOpportunities->save()) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '作废失败');
        }
        return true;
    }

    /**
     * 转移商机
     * @param $params
     * @return void
     * @throws ErrException
     * @throws Exception
     */
    public static function move($params)
    {
        $businessOpportunitiesNo = self::getString($params, 'business_opportunities_no');
        if (!$businessOpportunitiesNo) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少商机编号参数');
        }
        $accountId = self::getInt($params, 'account_id');
        if (!$accountId) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少员工ID参数');
        }

        // 是否添加协作人:1是,2否
        $isAddCollaborator = self::getInt($params, 'is_add_collaborator');
        if (!in_array($isAddCollaborator, [1, 2])) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少是否添加协作人参数');
        }

        /** @var SuiteCorpCrmBusinessOpportunities $businessOpportunities */
        $businessOpportunities = SuiteCorpCrmBusinessOpportunities::corp()
            ->andWhere([
                SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no') => $businessOpportunitiesNo,
            ])
            ->one();
        if (!$businessOpportunities) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '商机不存在');
        }

        /** @var SuiteCorpCrmBusinessOpportunitiesLink $follow */
        $follow = SuiteCorpCrmBusinessOpportunitiesLink::corp()
            ->andWhere([
                'customer_no' => $businessOpportunities->customer_no,
                'business_opportunities_no' => $businessOpportunitiesNo,
                'relational' => SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_1,
            ])
            ->one();
        if (!$follow) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '跟进人不存在');
        }
        if ($follow->account_id != $accountId) {
            $follow->account_id = $accountId;
            if (!$follow->save()) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '接收人保存失败');
            }
        }

        //增加协作人
        if ($isAddCollaborator == 1) {
            $createdId = auth()->accountId();
            if (!$createdId) {
                throw new ErrException(Code::BUSINESS_ABNORMAL, '用户信息异常');
            }
            $links = SuiteCorpCrmBusinessOpportunitiesLink::corp()
                ->select(['account_id'])
                ->andWhere([
                    'customer_no' => $businessOpportunities->customer_no,
                    'business_opportunities_no' => $businessOpportunitiesNo,
                    'relational' => SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_2,
                ])
                ->column();
            if (!in_array($createdId, $links)) {
                if ((count($links) + 1) > 5) {
                    throw new ErrException(Code::BUSINESS_ABNORMAL, '一个商机最多支持5个协作人');
                }
                $link = new SuiteCorpCrmBusinessOpportunitiesLink();
                $link->bindCorp();
                $link->customer_no = $businessOpportunities->customer_no;
                $link->business_opportunities_no = $businessOpportunitiesNo;
                $link->business_opportunities_link_no = self::getSnowflakeId();
                $link->relational = SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_2;
                $link->account_id = $createdId;
                if (!$link->save()) {
                    throw new ErrException(Code::BUSINESS_ABNORMAL, '添加协作人失败');
                }
            }
        }

        SuiteCorpCrmBusinessOpportunitiesSessionService::sync($businessOpportunitiesNo);
    }

    /**
     * 根据商机编号获取最新会话分析结果
     * @param array $params
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function sessionAnalysis(array $params)
    {
        $businessOpportunitiesNo = self::getString($params, 'business_opportunities_no');
        if (!$businessOpportunitiesNo) return [];
        /** @var SuiteCorpCrmBusinessOpportunities $bo */
        $bo = SuiteCorpCrmBusinessOpportunities::corp()->andWhere([SuiteCorpCrmBusinessOpportunities::asField('business_opportunities_no') => $businessOpportunitiesNo,])->one();
        if (!$bo) return [];

        //商机主要联系人
        /** @var SuiteCorpCrmBusinessOpportunitiesContact $contact */
        $contact = SuiteCorpCrmBusinessOpportunitiesContact::find()
            ->andWhere([
                'AND',
                ['=', SuiteCorpCrmBusinessOpportunitiesContact::asField('suite_id'), $bo->suite_id],
                ['=', SuiteCorpCrmBusinessOpportunitiesContact::asField('corp_id'), $bo->corp_id],
                ['=', SuiteCorpCrmBusinessOpportunitiesContact::asField('customer_no'), $bo->customer_no],
                ['=', SuiteCorpCrmBusinessOpportunitiesContact::asField('business_opportunities_no'), $bo->business_opportunities_no],
                ['=', SuiteCorpCrmBusinessOpportunitiesContact::asField('is_main'), SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_1],
            ])
            ->with([
                'contact' => function ($query) {
                    $query->select([
                        SuiteCorpCrmCustomerContact::asField('suite_id'),
                        SuiteCorpCrmCustomerContact::asField('corp_id'),
                        SuiteCorpCrmCustomerContact::asField('customer_no'),
                        SuiteCorpCrmCustomerContact::asField('contact_no'),
                    ])
                        ->with([
                            //一个联系人会存在多个企业微信
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
                }
            ])
            ->one();
        if (!$contact) return [];
        $contact = $contact->toArray();
        $information = $contact['contact']['information'] ?? [];
        if (!$information) return [];
        $information = $information[0];//一个联系人可能会有多个联系方式，其中包括可能多个企业微信联系方式，因为存在合并联系人操作，所以这里默认取第一个

        //获取商机跟进人
        /** @var SuiteCorpCrmBusinessOpportunitiesLink $follow */
        $follow = SuiteCorpCrmBusinessOpportunitiesLink::find()
            ->with([
                'account' => function ($query) {
                    $query->select([
                        Account::asField('id'),
                        Account::asField('suite_id'),
                        Account::asField('corp_id'),
                        Account::asField('userid'),
                    ]);
                },
            ])
            ->andWhere([
                'AND',
                ['=', SuiteCorpCrmBusinessOpportunitiesLink::asField('suite_id'), $bo->suite_id],
                ['=', SuiteCorpCrmBusinessOpportunitiesLink::asField('corp_id'), $bo->corp_id],
                ['=', SuiteCorpCrmBusinessOpportunitiesLink::asField('customer_no'), $bo->customer_no],
                ['=', SuiteCorpCrmBusinessOpportunitiesLink::asField('business_opportunities_no'), $bo->business_opportunities_no],
                ['=', SuiteCorpCrmBusinessOpportunitiesLink::asField('relational'), SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_1],
            ])
            ->one();
        if (!$follow) return [];
        $follow = $follow->toArray();
        $followAccount = $follow['account']['userid'] ?? '';
        if (!$followAccount) return [];
        $sessionId = dictSortMd5([$followAccount, $information['contact_number'] ?? '']);

        /** @var SuiteCorpAnalysisTaskDate $sessionTaskDate */
        $sessionTaskDate = SuiteCorpAnalysisTaskDate::find()
            ->where([
                'suite_id' => $bo->suite_id,
                'corp_id' => $bo->corp_id,
                'session_id' => $sessionId,
                'analysis_type' => SuiteCorpAnalysisTaskDate::ANALYSIS_TYPE_2,
            ])
            ->orderBy(['analysis_date' => SORT_DESC])
            ->one();
        if (!$sessionTaskDate) return [];

        return SuiteCorpAnalysisTaskResultDetails::find()
            ->where(['task_id' =>  $sessionTaskDate->task_id])
            ->asArray()
            ->all();
    }
}
