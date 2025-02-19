<?php
use app\models\Accounts;

use yii\helpers\Html;
use yii\grid\GridView;
// use kartik\file\FileInput;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Accounts Data';
?>

<head>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<h1><?= Html::encode($this->title) ?></h1>

<?php
$form = ActiveForm::begin([
    'id' => 'send-api-form',
    'action' => ['api/sendapi'], // Ganti dengan action yang sesuai
    'method' => 'post',
]); 
?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        ['class' => 'yii\grid\CheckboxColumn', 'name' => 'selection[]'], // Menambahkan name untuk checkbox

        'id',              // Kolom id
        'accountType',     // Kolom accountType
        'asOf',            // Kolom asOf
        'currencyCode',    // Kolom currencyCode
        'name',            // Kolom name
        'no',              // Kolom no
        'parentNo',        
        'memo:ntext',      // Kolom memo ditampilkan dalam format teks panjang

        [
            'class' => 'yii\grid\ActionColumn', // Kolom tindakan (lihat, edit, hapus)
            // 'urlCreator' => function ($action, $model, $key, $index, $column) {
            //     return Url::toRoute([$action, 'id' => $model->id]); // Buat URL untuk setiap tindakan
            // },
            // 'template' => '{send-to-api}', // Tambahkan tombol custom
            // 'buttons' => [
            //     'send-to-api' => function ($url, $model, $key) {
            //         return Html::a('Send API', ['api/sendapi', 'id' => $model->id], [
            //             'class' => 'btn btn-success btn-sm',
            //             'data-method' => 'post',
            //             'data-confirm' => 'Are you sure you want to send this data to API Accurate Online?',
            //         ]);
            //     },
            // ],
        ],
    ],
]); 
?>

<div class="form-group">
    <?= Html::submitButton('Send Selected to API', [
        'class' => 'btn btn-primary',
        'style' => 'display: block; width: 100%;',
        'data-confirm' => 'Are you sure you want to send the selected accounts to Accurate Online?']) ?>
</div>

<?php ActiveForm::end(); ?>

    <div class="pagination-container">
        <?= \yii\widgets\LinkPager::widget([
            'pagination' => $dataProvider->pagination,
            'options' => ['class' => 'pagination justify-content-center'], // Bootstrap classes for styling
            'linkOptions' => ['class' => 'page-link'], // Bootstrap class for links
            'activePageCssClass' => 'active', // Active page class
            'disabledPageCssClass' => 'disabled', // Disabled page class
        ]); ?>
    </div>

    <style>
        .selected {
            background-color: #d1e7dd; // Gaya untuk sel yang dipilih
        }
    </style>

    <?php
        // $importUrl = \yii\helpers\Url::to(['site/import-sql']);
        $importUrl = \yii\helpers\Url::to(['site/import-json']); 

        $js = <<<JS
        $('.grid-view td').on('click', function() {
            $(this).toggleClass('selected'); // Menambahkan kelas 'selected' pada sel yang diklik   
        });
        
    JS;
    $this->registerJs($js);
    ?>