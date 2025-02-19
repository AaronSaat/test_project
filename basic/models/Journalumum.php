<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "journalumum".
 *
 * @property int $id
 * @property int|null $idInternal
 * @property string|null $number
 * @property string $transDate
 * @property string|null $description
 */
class Journalumum extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'journalumum';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idInternal'], 'integer'],
            [['transDate'], 'required'],
            [['number', 'transDate', 'description'], 'string', 'max' => 255],
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
            'number' => 'Number',
            'transDate' => 'Trans Date',
            'description' => 'Description',
        ];
    }

    public function getJournaldetail()
    {
        return $this->hasMany(Journaldetail::class, ['idInternal' => 'idInternal']);
    }
}
