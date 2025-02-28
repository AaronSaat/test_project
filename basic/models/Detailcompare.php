<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "detailcompare".
 *
 * @property int $id
 * @property string|null $number
 * @property string|null $transDate
 * @property string|null $accountNo
 * @property float|null $amount
 * @property string|null $amountType
 * @property string $created_at
 * @property string|null $accountOri
 * @property string|null $memo
 * @property string|null $vendorNo
 */
class Detailcompare extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'detailcompare';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['amount'], 'number'],
            [['created_at'], 'safe'],
            [['number', 'transDate', 'accountNo', 'amountType', 'accountOri', 'memo', 'vendorNo'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'number' => 'Number',
            'transDate' => 'Trans Date',
            'accountNo' => 'Account No',
            'amount' => 'Amount',
            'amountType' => 'Amount Type',
            'created_at' => 'Created At',
            'accountOri' => 'Account Ori',
            'memo' => 'Memo',
            'vendorNo' => 'Vendor No',
        ];
    }
}
