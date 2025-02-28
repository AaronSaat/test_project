<?php

use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\helpers\Html;

/** @var ActiveDataProvider $dataProvider */
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= $this->title ?></h1>

<strong>Note: Informasi log / error akan terhapus ketika mengimport data baru <br>
(Dilakukan karena urutan jurnalerror dan jurnalcompare perlu dibandingkan untuk melihat detailnya)</strong>

<br><br>

<p>
    Total Success: <strong><?= $successCountJournalError  ?></strong><br>
    Total Error: <strong><?= $errorCountJournalError  ?></strong>
</p>

<p>

<?=
// Button to call actionDeletealljournalapi
 Html::a('Delete All Journals from Accurate Online', Url::to(['api/deletealljournalapi']), [
    'class' => 'btn btn-danger',
    'data' => [
        'confirm' => 'Are you sure you want to delete all journals?',
        'method' => 'post',
    ],
]);
?>
</p>

<h2>Journal Error Log</h2>
<p>
    <?= Html::a("Tampilkan Semua ({$successCountJournalError} sukses, {$errorCountJournalError} error)", ['journal-errors', 'filter' => 'all'], ['class' => 'btn btn-primary']) ?>
    <?= Html::a("Hanya Error ({$errorCountJournalError})", ['journal-errors', 'filter' => 'error'], ['class' => 'btn btn-danger']) ?>
    <?= Html::a("Hanya Success ({$successCountJournalError})", ['journal-errors', 'filter' => 'success'], ['class' => 'btn btn-success']) ?>
</p>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        [
            'attribute' => 'info',
            'contentOptions' => function ($model) {
                if ($model['info'] === 'error') {
                    return ['style' => 'background-color: red; color: white;'];
                } elseif ($model['info'] === 'success') {
                    return ['style' => 'background-color: green; color: white;'];
                }
                return [];
            },
        ],
        'number',
        'response',
        'created_at',
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{view-journal}',
            'buttons' => [
                'view-journal' => function ($url, $model) {
                    return Html::a('Lihat Detail Jurnal', ['view-journal-details', 'id' => $model->id], [
                        'class' => 'btn btn-primary btn-sm',
                    ]);
                },
            ],
        ],
    ],
]); ?>

