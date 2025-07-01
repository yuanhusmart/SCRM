<?php

namespace common\models;

use common\models\concerns\traits\Helper;
/**
 * This is the model class for table "suite_role_account".
 *
 * @property int $id
 * @property int $role_id
 * @property int $account_id
 */
class SuiteRoleAccount extends \common\db\ActiveRecord
{
    use Helper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_role_account';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'role_id' => 'Role ID',
            'account_id' => 'Account ID',
        ];
    }
}
