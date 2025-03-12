<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Journal Index';
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?php
$form = ActiveForm::begin([
    'id' => 'send-journal-api-form',
    'action' => ['accurate/authorize?batchIndex=0'], // Arahkan ke action controller
    'method' => 'post',
]); 
?>

<?= ''
// GridView::widget([
//     'dataProvider' => $dataProvider,
//     'columns' => [
//         ['class' => 'yii\grid\CheckboxColumn'],
//         'id',             
//         'number',        
//         'description',        
//         'transDate',        
//         'branchName', 
//         [
//             'class' => 'yii\grid\ActionColumn',
//             'template' => '{view-journal}',
//             'buttons' => [
//                 'view-journal' => function ($url, $model) {
//                     return Html::a('Lihat Detail Jurnal', ['view-detail-journal-index', 'id' => $model->id], [
//                         'class' => 'btn btn-primary btn-sm',
//                     ]);
//                 },
//             ],
//         ],
//     ],
// ]); 
?>

<div class="form-group">
    <?= Html::submitButton('Send Selected to API', [
        'class' => 'btn btn-primary',
        'style' => 'display: block; width: 100%;',
        'data-confirm' => 'Are you sure you want to send the selected accounts to Accurate Online?', 
    ]) ?>
</div>

<?php ActiveForm::end(); ?>
