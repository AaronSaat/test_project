<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "accounts".
 *
 * @property int $id
 * @property string $accountType
 * @property string $asOf
 * @property string $currencyCode
 * @property string $name
 * @property string $no
 * @property string|null $memo
 */
class Accounts extends \yii\db\ActiveRecord
{
    /**
     * @var \yii\web\UploadedFile The uploaded JSON file
     */
    public $jsonFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'accounts';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['accountType', 'currencyCode', 'name', 'no'], 'required'],
            [['memo'], 'string'],
            [['accountType', 'asOf', 'name', 'no', 'parentNo'], 'string', 'max' => 255],
            [['currencyCode'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'accountType' => 'Account Type',
            'asOf' => 'As Of',
            'currencyCode' => 'Currency Code',
            'name' => 'Name',
            'no' => 'No',
            'parentNo' => 'parentNo',
            'memo' => 'Memo',
        ];
    }
}
