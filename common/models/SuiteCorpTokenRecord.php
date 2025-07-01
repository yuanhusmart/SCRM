<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_token_record".
 *
 * @property int $id
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property int $current_tokens 当前token数
 * @property int $surplus_tokens 剩余token数
 * @property int $input_tokens 输入token数
 * @property int $output_tokens 输出token数
 * @property int $analysis_type 分析类型：1.微信分析 2.企微分析 3.电话分析 4.编辑总量
 * @property string $analysis_date 分析日期
 * @property int $analysis_time 分析时间
 * @property int $updated_at 操作时间
 * @property string $batch_id Batch任务ID
 */
class SuiteCorpTokenRecord extends \yii\db\ActiveRecord
{

    const ANALYSIS_TYPE_1 = 1;
    const ANALYSIS_TYPE_2 = 2;
    const ANALYSIS_TYPE_3 = 3;
    const ANALYSIS_TYPE_4 = 4;

    const ANALYSIS_TYPE_DESC = [
        self::ANALYSIS_TYPE_1 => '微信分析',
        self::ANALYSIS_TYPE_2 => '企微分析',
        self::ANALYSIS_TYPE_3 => '电话分析',
        self::ANALYSIS_TYPE_4 => '编辑总量',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_token_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['current_tokens', 'surplus_tokens', 'input_tokens', 'output_tokens', 'analysis_type', 'analysis_time', 'updated_at'], 'integer'],
            [['analysis_date'], 'safe'],
            [['suite_id', 'corp_id'], 'string', 'max' => 50],
            [['batch_id'], 'string', 'max' => 128],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'             => 'ID',
            'suite_id'       => 'Suite ID',
            'corp_id'        => 'Corp ID',
            'current_tokens' => 'Current Tokens',
            'surplus_tokens' => 'Surplus Tokens',
            'input_tokens'   => 'Input Tokens',
            'output_tokens'  => 'Output Tokens',
            'analysis_type'  => 'Analysis Type',
            'analysis_date'  => 'Analysis Date',
            'analysis_time'  => 'Analysis Time',
            'updated_at'     => 'Updated At',
            'batch_id'       => 'Batch ID',
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        $this->updated_at = time();
        return parent::beforeValidate();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSuiteCorpConfig()
    {
        return $this->hasOne(SuiteCorpConfig::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id'])->select('suite_id,corp_id,corp_name,liaison_name,liaison_mobile');
    }
}
