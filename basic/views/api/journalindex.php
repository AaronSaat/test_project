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
    'action' => ['api/sendjournalapi'], // Arahkan ke action controller
    'method' => 'post',
]); 
?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        ['class' => 'yii\grid\CheckboxColumn'], // Checkbox untuk memilih data

        'id',             
        'number',        
        'transDate',      
        'description',      
        'accountNo',      
        'amount',      
        'amountType',      
        'memo',      
        [
            'class' => 'yii\grid\ActionColumn',
            // 'template' => '{send-to-api}',
            // 'buttons' => [
            //     'send-to-api' => function ($url, $model, $key) {
            //         return Html::a('Send API', ['api/sendjournalapi', 'id' => $model->id], [
            //             'class' => 'btn btn-success btn-sm',
            //             'data-method' => 'post',
            //             'data-confirm' => 'Are you sure you want to send this data to API Accurate Online?',
            //         ]);
            //     },
            // ],
        ],
    ],
]); ?>

<div class="pagination-container">
        <?= \yii\widgets\LinkPager::widget([
            'pagination' => $dataProvider->pagination,
            'options' => ['class' => 'pagination justify-content-center'], // Bootstrap classes for styling
            'linkOptions' => ['class' => 'page-link'], // Bootstrap class for links
            'activePageCssClass' => 'active', // Active page class
            'disabledPageCssClass' => 'disabled', // Disabled page class
        ]); ?>
    </div>

<div class="form-group">
    <?= Html::submitButton('Send Selected to API', [
        'class' => 'btn btn-primary',
        'style' => 'display: block; width: 100%;',
        'data-confirm' => 'Are you sure you want to send the selected accounts to Accurate Online?', 
    ]) ?>
</div>

<?php ActiveForm::end(); ?>
