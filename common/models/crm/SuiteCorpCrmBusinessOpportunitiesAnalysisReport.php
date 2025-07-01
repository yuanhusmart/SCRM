<?php

namespace common\models\crm;

use common\models\concerns\traits\CorpNotSoft;

/**
 * 企业的CRM商机分析报告表
 * create table suite_corp_crm_business_opportunities
 * (
 * @property int $id                           int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id                  varchar(50)  default ''  not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id                   varchar(50)  default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $business_opportunities_no varchar(50)  default ''  not null comment '商机编号=suite_corp_crm_business_opportunities.business_opportunities_no',
 * @property string $job_id                    varchar(80) default '' not null comment '模型任务ID',
 * @property string $model_id                  varchar(80)  default ''  null comment '执行的模型ID=suite_agent_ability.model_id',
 * @property string $ability_id                varchar(80)  default ''  null comment '执行的能力ID=suite_agent_ability.model_id',
 * @property string $session_id                varchar(50)  default ''  not null comment '会话ID=suite_corp_crm_business_opportunities_session.session_id',
 * @property string $follow_userid             varchar(50)  default ''  not null comment '跟进人企微userid(企业员工)',
 * @property string $contact_userid            varchar(50)  default ''  not null comment '外部联系人企微userid(企业外部联系人)',
 * @property string $contact_role              varchar(50)  default ''  not null comment '执行时联系人的角色',
 * @property string|null $think                     text         default null comment '模型思考内容',
 * @property json|null $request                   json         default null comment '请求内容',
 * @property json|null $result                    json         default null comment '模型结果内容(原内容)',
 * @property int $state                     tinyint      default 1   not null comment '任务状态:1进行中,2成功,3失败',
 * @property string|null $err_msg                     text      default null comment '错误信息',
 * @property string|null $created_date              date         default null comment '创建日期',
 * @property int $created_at                int unsigned default '0' not null comment '创建时间',
 * @property int $updated_at                int unsigned default '0' not null comment '更新时间',
 * @property int $last_msg_at int unsigned default '0' not null comment '分析时最新消息时间',
 * @property string $last_msg_id varchar(80)  default ''  not null comment '分析时最新消息ID',
 * @property int $msg_total int unsigned default '0' not null comment '分析消息总数',
 * @property int $status tinyint default 1 not null comment '记录状态:1待应用,2已应用,3已过期,4应用失败',
 * @property int $type tinyint default 1 not null comment '记录类型:1自动,2手动',
 * @property string $customer_quality_level    varchar(20)  default ''  not null comment '客户质量等级',
 * @property string|null $rating_explanation        text         default null comment '评级说明',
 * @property string|null $sop_judgment              text  default null comment 'sop判定',
 * @property json|null $add_tags                  json         default null comment '新增的标签',
 * @property string|null $customer_communication_analysis text default null comment '客户沟通分析',
 * @property string|null $suggest_phrases text default null comment '建议话术',
 * @property json|null $attention json default null comment '注意事项',
 * @property json|null $competition_list json default null comment '竞品分析',
 * @property json|null $completion_items json default null comment '事项完成情况',
 * @property json|null $requirement_tags json default null comment '需求标签',
 * @property string $sop_name varchar(50)  default '' not null comment 'sop阶段名称',
 * @property string $follow_up_record text default null comment '跟进记录内容',
 * UNIQUE KEY uk_main (suite_id, corp_id, business_opportunities_no, created_date)
 * )
 */
class SuiteCorpCrmBusinessOpportunitiesAnalysisReport extends \common\db\ActiveRecord
{
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_business_opportunities_analysis_report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'state','last_msg_at','msg_total','status','type'], 'integer'],
            [['suite_id', 'corp_id', 'business_opportunities_no', 'session_id', 'follow_userid', 'contact_userid', 'contact_role','sop_name'], 'string', 'max' => 50],
            [['model_id','ability_id','job_id','last_msg_id'], 'string', 'max' => 80],
            [['customer_quality_level'], 'string', 'max' => 20],
            [['think','err_msg','rating_explanation','sop_judgment','customer_communication_analysis','suggest_phrases','follow_up_record'], 'string','skipOnEmpty' => true],
            [['result','request','add_tags','attention','competition_list','completion_items','requirement_tags'], 'safe', 'skipOnEmpty' => true],
            [['created_date'],  'date', 'format' => 'yyyy-MM-dd'],
            [['suite_id','corp_id','business_opportunities_no','created_date'], 'unique', 'targetAttribute' => ['suite_id','corp_id','business_opportunities_no','created_date']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'suite_id' => '服务商ID',
            'corp_id' => '企业ID',
            'business_opportunities_no' => '商机编号',
            'job_id' => '模型任务ID',
            'model_id' => '模型ID',
            'ability_id' => '能力ID',
            'session_id' => '会话ID',
            'follow_userid' => '跟进人企微id',
            'contact_userid' => '外部联系人企微id',
            'contact_role' => '执行时联系人的角色',
            'think' => '模型思考内容',
            'request' => '请求内容',
            'result' => '模型结果内容',
            'state' => '任务状态',
            'err_msg' => '错误信息',
            'created_date' => '创建日期',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'last_msg_at' => '分析时最新消息时间',
            'last_msg_id' => '分析时最新消息ID',
            'msg_total' => '分析消息总数',
            'status' => '记录状态',
            'type' => '记录类型',
            'customer_quality_level' => '客户质量等级',
            'rating_explanation' => '评级说明',
            'sop_judgment' => 'sop判定',
            'customer_communication_analysis' => '客户沟通分析',
            'suggest_phrases' => '建议话术',
            'add_tags' => '新增的标签',
            'attention' => '注意事项',
            'competition_list' => '竞品分析',
            'completion_items' => '事项完成情况',
            'requirement_tags' => '需求标签',
            'sop_name' => 'SOP名称',
            'follow_up_record' => '跟进内容'
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        $time = time();
        if ($this->isNewRecord) {
            $this->created_at = $time;
            $this->created_date = date('Y-m-d');
        }
        $this->updated_at = $time;
        return parent::beforeValidate();
    }

    /**
     * 一对一:关联联系方式
     * @return \yii\db\ActiveQuery
     */
    public function getInformation()
    {
        return $this->hasOne(SuiteCorpCrmCustomerContactInformation::class, ['contact_number' => 'contact_userid',]);
    }

    /** @var int 任务状态:进行中 */
    const STATE_1 = 1;
    /** @var int 任务状态:成功 */
    const STATE_2 = 2;
    /** @var int 任务状态:失败 */
    const STATE_3 = 3;
    /** @var array 任务状态 */
    const STATE_MAP = [
        self::STATE_1 => '进行中',
        self::STATE_2 => '成功',
        self::STATE_3 => '失败',
    ];

    /** @var int 记录状态:待应用 */
    const STATUS_1 = 1;
    /** @var int 记录状态:已应用 */
    const STATUS_2 = 2;
    /** @var int 记录状态:已过期 */
    const STATUS_3 = 3;
    /** @var int 记录状态:应用失败 */
    const STATUS_4 = 4;
    /** @var array 记录状态 */
    const STATUS_MAP = [
        self::STATUS_1 => '待应用',
        self::STATUS_2 => '已应用',
        self::STATUS_3 => '已过期',
        self::STATUS_4 => '应用失败',
    ];

}
