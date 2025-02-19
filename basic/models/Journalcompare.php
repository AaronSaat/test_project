<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "journalcompare".
 *
 * @property int $id
 * @property string|null $number
 * @property string|null $trans_date
 * @property string|null $branchName
 */
class Journalcompare extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'journalcompare';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['number', 'trans_date', 'branchName'], 'string', 'max' => 255],
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
            'branchName' => 'Branch Name',
        ];
    }
}
