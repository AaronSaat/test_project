<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "journal".
 *
 * @property int $id
 * @property string|null $number
 * @property string $transDate
 * @property string|null $description
 * @property string $accountNo
 * @property float $amount
 * @property string $amountType
 * @property string|null $memo
 */
class Journal extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'journal';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transDate', 'accountNo', 'amount', 'amountType'], 'required'],
            [['amount'], 'number'],
            [['number', 'transDate', 'description', 'accountNo', 'amountType', 'memo'], 'string', 'max' => 255],
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
            'description' => 'Description',
            'accountNo' => 'Account No',
            'amount' => 'Amount',
            'amountType' => 'Amount Type',
            'memo' => 'Memo',
        ];
    }
}
