<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_oauth2".
 *
 * @property int $id
 * @property string $accessToken
 * @property string $refreshToken
 * @property string $created_at
 * @property int $db_id
 * @property string $session_id
 * @property string $host
 */
class Oauth2Model extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_oauth2';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['accessToken', 'refreshToken', 'created_at', 'db_id', 'session_id', 'host'], 'required'],
            [['created_at'], 'safe'],
            [['db_id'], 'integer'],
            [['accessToken', 'refreshToken', 'session_id', 'host'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'accessToken' => 'Access Token',
            'refreshToken' => 'Refresh Token',
            'created_at' => 'Created At',
            'db_id' => 'Db ID',
            'session_id' => 'Session ID',
            'host' => 'Host',
        ];
    }
}
