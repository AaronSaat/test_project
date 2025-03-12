<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Upload Account File - Pos Akun';
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin([
    'id' => 'upload-json-form',
    'options' => ['enctype' => 'multipart/form-data'], 
    'method' => 'post',
]); ?>

<?= $form->field($model, 'file')->fileInput()->label('Upload Account File')?>

<div class="form-group">
    <?= Html::submitButton('Upload', ['class' => 'btn btn-primary'])?>
</div>

<?php ActiveForm::end(); ?>
