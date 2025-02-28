<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "journalcompare".
 *
 * @property int $id
 * @property string|null $number
 * @property string|null $transDate
 * @property string|null $description
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
            [['number', 'transDate', 'description', 'branchName'], 'string', 'max' => 255],
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
            'branchName' => 'Branch Name',
        ];
    }
}
