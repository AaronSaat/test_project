<?php
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $journalError app\models\JournalError */
/* @var $detailCompareData app\models\DetailCompare[] */

$this->title = "Detail Jurnal untuk Number: " . $journalError->number;
$this->params['breadcrumbs'][] = ['label' => 'Journal Errors', 'url' => ['journal-errors']];
$this->params['breadcrumbs'][] = $this->title;
?>

<h2><?= Html::encode($this->title) ?></h2>

<!-- Tampilkan Detail Jurnal Error -->
<table class="table table-bordered">
    <tr>
        <th>Info</th>
        <td><?= Html::encode($journalError->info) ?></td>
    </tr>
    <tr>
        <th>Number</th>
        <td><?= Html::encode($journalError->number) ?></td>
    </tr>
    <tr>
        <th>Response</th>
        <td><?= Html::encode($journalError->response) ?></td>
    </tr>
    <tr>
        <th>Branch Name</th>
        <td><?= Html::encode($journalCompare->branchName) ?></td>
    </tr>
</table>

<h3>Detail Journal</h3>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'number',
        'trans_date',
        'account_no:text:Account No (Setelah dipetakan)',
        'account_ori',
        'amount',
        'amount_type',
        'created_at',
    ],
]); ?>

<p>
    <?= Html::a('Kembali', ['journal-errors'], ['class' => 'btn btn-secondary']) ?>
</p>
