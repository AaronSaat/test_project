<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "detailcompare".
 *
 * @property int $id
 * @property string|null $number
 * @property string|null $trans_date
 * @property string|null $account_no
 * @property float|null $amount
 * @property string|null $amount_type
 * @property string $created_at
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
            [['number', 'trans_date', 'account_no', 'account_ori', 'amount_type'], 'string', 'max' => 255],
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
            'trans_date' => 'Trans Date',
            'account_no' => 'Account No (Setelah dipetakan)',
            'account_ori' => 'Account Ori',
            'amount' => 'Amount',
            'amount_type' => 'Amount Type',
            'created_at' => 'Created At',
        ];
    }
}
