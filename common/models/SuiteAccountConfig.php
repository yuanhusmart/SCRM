<?php

namespace common\models;

use common\models\concerns\traits\Helper;
use Yii;

/**
 * This is the model class for table "suite_account_config".
 *
 * @property int $id
 * @property int $account_id
 * @property string $key
 * @property string|null $value 内容
 * @property int $created_at
 * @property int $updated_at
 */
class SuiteAccountConfig extends \common\db\ActiveRecord
{
    use Helper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_account_config';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'key' => 'Key',
            'value' => 'Value',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
