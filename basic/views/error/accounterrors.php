<?php

use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;

/** @var ActiveDataProvider $dataProvider */

$this->title = 'Account Errors';
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= $this->title ?></h1>

<strong>Note: Informasi log / error akan terhapus ketika mengimport data baru</strong>

<br><br>

<p>
    Total Success: <strong><?= $successCountAccountError  ?></strong><br>
    Total Error: <strong><?= $errorCountAccountError  ?></strong>
</p>

<!-- Filter Button -->
<p>
    <?= Html::a("Tampilkan Semua ({$successCountAccountError} sukses, {$errorCountAccountError} error)", 
        ['account-errors', 'filter' => 'all'], 
        ['class' => 'btn btn-primary']) 
    ?>
    <?= Html::a("Hanya Error ({$errorCountAccountError})", 
        ['account-errors', 'filter' => 'error'], 
        ['class' => 'btn btn-danger']) 
    ?>
    <?= Html::a("Hanya Success ({$successCountAccountError})", 
        ['account-errors', 'filter' => 'success'], 
        ['class' => 'btn btn-success']) 
    ?>
</p>

<div class="row">
    <div class="col-md-6">
        <h2>Account Error Log</h2>
        <?= GridView::widget([
            'dataProvider' => $dataProvider2,
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
                'name',
                'accountType',
                'no',
                'response',
            ],
        ]); ?>
    </div>

    <div class="col-md-6">
        <h2>Account Request Log</h2>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                'no',
                'accountType',
                'name',
                'parentNo',
            ],
        ]); ?>
    </div>
</div>
