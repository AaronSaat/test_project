<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "accountcompare".
 *
 * @property string|null $no
 * @property string|null $accountType
 * @property string|null $name
 * @property string|null $parentNo
 */
class Accountcompare extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'accountcompare';
    }

    /**~
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['no', 'accountType', 'name', 'parentNo'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'no' => 'No',
            'accountType' => 'Account Type',
            'name' => 'Name',
            'parentNo' => 'Parent No',
        ];
    }
}
