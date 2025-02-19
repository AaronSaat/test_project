<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "accounterror".
 *
 * @property int $id
 * @property string|null $info
 * @property string|null $name
 * @property string|null $accountType
 * @property string|null $no
 * @property string|null $response
 */
class Accounterror extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'accounterror';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['info', 'name', 'accountType', 'no', 'response'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'info' => 'Info',
            'name' => 'Name',
            'accountType' => 'Account Type',
            'no' => 'No',
            'response' => 'Response',
        ];
    }
}
