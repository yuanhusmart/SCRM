<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_rule".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $rule_id 关键词规则id
 * @property string $name 关键词规则名称，长度限制1~20个字符
 * @property int $open_keyword 开启敏感词 1.开启 2.关闭
 * @property int $is_case_sensitive 匹配关键词时是否区分大小写，0-不区分；1-区分，默认为区分。
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class SuiteCorpRule extends \yii\db\ActiveRecord
{

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'rule_id', 'name', 'open_keyword', 'is_case_sensitive', 'created_at', 'updated_at'];

    // 开启敏感词 1.开启 2.关闭
    const OPEN_KEYWORD_1 = 1;
    const OPEN_KEYWORD_2 = 2;

    const OPEN_KEYWORD_DESC = [
        self::OPEN_KEYWORD_1 => '开启',
        self::OPEN_KEYWORD_2 => '关闭',
    ];

    const IS_CASE_SENSITIVE_0 = 0;
    const IS_CASE_SENSITIVE_1 = 1;

    const IS_CASE_SENSITIVE_DESC = [
        self::IS_CASE_SENSITIVE_0 => '不区分',
        self::IS_CASE_SENSITIVE_1 => '区分',
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_rule';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['open_keyword', 'is_case_sensitive', 'created_at', 'updated_at'], 'integer'],
            [['suite_id', 'corp_id', 'name'], 'string', 'max' => 50],
            [['rule_id'], 'string', 'max' => 100],
            [['suite_id', 'corp_id', 'rule_id'], 'unique', 'targetAttribute' => ['suite_id', 'corp_id', 'rule_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'suite_id'          => 'Suite ID',
            'corp_id'           => 'Corp ID',
            'rule_id'           => 'Rule ID',
            'name'              => 'Name',
            'open_keyword'      => 'Open Keyword',
            'is_case_sensitive' => 'Is Case Sensitive',
            'created_at'        => 'Created At',
            'updated_at'        => 'Updated At',
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
        }
        $this->updated_at = $time;
        return parent::beforeValidate();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKeywordByCorp()
    {
        return $this->hasMany(SuiteCorpKeyword::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSemanticsByCorp()
    {
        return $this->hasMany(SuiteCorpSemantics::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id']);
    }


}
