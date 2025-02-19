<?php

namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;

class JsonUploadForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $file;
    public $journalFile;
    public $journalDetailFile;

    public function rules()
    {
        return [
            [['file'], 'file', 'extensions' => 'json', 'skipOnEmpty' => false],
            [['journalFile', 'journalDetailFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'json', 'maxSize' => 50 * 1024 * 1024, 'checkExtensionByMimeType' => false],
        ];
    }

    public function attributeLabels()
    {
        return [
            'file' => 'Upload JSON File',
        ];
    }
}
