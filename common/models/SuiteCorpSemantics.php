<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_semantics".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property int $semantics 关键行为列表 1-红包 2-手机号码 3-邮箱地址 4-微信名片 5-带二维码图片 6-撤回消息 7-银行卡号 8-身份证号 9-发送文件（不包括微盘文件） 10-发送链接（发送链接消息或者发送的文本消息中包含链接） 11-发送小程序 12-发送客户欢迎语
 */
class SuiteCorpSemantics extends \yii\db\ActiveRecord
{

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'semantics'];

    const SEMANTICS_1  = 1;
    const SEMANTICS_2  = 2;
    const SEMANTICS_3  = 3;
    const SEMANTICS_4  = 4;
    const SEMANTICS_5  = 5;
    const SEMANTICS_6  = 6;
    const SEMANTICS_7  = 7;
    const SEMANTICS_8  = 8;
    const SEMANTICS_9  = 9;
    const SEMANTICS_10 = 10;
    const SEMANTICS_11 = 11;
    const SEMANTICS_12 = 12;

    const SEMANTICS_DESC = [
        self::SEMANTICS_1  => '红包',
        self::SEMANTICS_2  => '手机号码',
        self::SEMANTICS_3  => '邮箱地址',
        self::SEMANTICS_4  => '微信名片',
        self::SEMANTICS_5  => '带二维码图片',
        self::SEMANTICS_6  => '撤回消息',
        self::SEMANTICS_7  => '银行卡号',
        self::SEMANTICS_8  => '身份证号',
        // self::SEMANTICS_9  => '发送文件',
        // self::SEMANTICS_10 => '发送链接',
        // self::SEMANTICS_11 => '发送小程序',
        // self::SEMANTICS_12 => '发送客户欢迎语',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_semantics';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['semantics'], 'integer'],
            [['suite_id', 'corp_id'], 'string', 'max' => 50],
            [['suite_id', 'corp_id', 'semantics'], 'unique', 'targetAttribute' => ['suite_id', 'corp_id', 'semantics']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'        => 'ID',
            'suite_id'  => 'Suite ID',
            'corp_id'   => 'Corp ID',
            'semantics' => 'Semantics',
        ];
    }
}
