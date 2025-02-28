<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "journalerror".
 *
 * @property int $id
 * @property string|null $info
 * @property string|null $number
 * @property string|null $response
 * @property string $created_at
 */
class Journalerror extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'journalerror';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at'], 'safe'],
            [['info', 'number', 'response'], 'string', 'max' => 255],
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
            'number' => 'Number',
            'response' => 'Response',
            'created_at' => 'Created At',
        ];
    }
}
