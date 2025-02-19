<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "journaldetail".
 *
 * @property int $id
 * @property int|null $idInternal
 * @property string $accountNo
 * @property float $amount
 * @property string $amountType
 * @property string|null $memo
 */
class Journaldetail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'journaldetail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idInternal'], 'integer'],
            [['accountNo', 'amount', 'amountType'], 'required'],
            [['amount'], 'number'],
            [['accountNo', 'amountType', 'memo'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'idInternal' => 'Id Internal',
            'accountNo' => 'Account No',
            'amount' => 'Amount',
            'amountType' => 'Amount Type',
            'memo' => 'Memo',
        ];
    }
}
