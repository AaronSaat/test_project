<?php
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $journalError app\models\JournalError */
/* @var $detailCompareData app\models\DetailCompare[] */

$this->title = "Detail Jurnal untuk Number: " . $journalCompare->number;
$this->params['breadcrumbs'][] = ['label' => 'Journal Errors', 'url' => ['journal-errors']];
$this->params['breadcrumbs'][] = $this->title;
?>

<h2><?= Html::encode($this->title) ?></h2>

<!-- Tampilkan Detail Jurnal Error -->
<table class="table table-bordered">
    <tr>
        <th>Id</th>
        <td><?= Html::encode($journalCompare->id) ?></td>
    </tr>
    <tr>
        <th>Number</th>
        <td><?= Html::encode($journalCompare->number) ?></td>
    </tr>
    <tr>
        <th>Transaction Date</th>
        <td><?= Html::encode($journalCompare->transDate) ?></td>
    </tr>
    <tr>
        <th>Branch Name</th>
        <td><?= Html::encode($journalCompare->branchName) ?></td>
    </tr>
    <tr>
        <td style="color: green; font-weight: bold; text-align: center; background-color: #e6f9e6; padding: 10px;">
            DEBIT: <?= Html::encode($totalDebit) ?>
        </td>
        <td style="color: red; font-weight: bold; text-align: center; background-color: #fde2e2; padding: 10px;">
            CREDIT: <?= Html::encode($totalCredit) ?>
        </td>
    </tr>
</table>

<h3>Detail Journal</h3>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'number',
        'transDate',
        'accountNo',
        'accountOri',
        'amount',
        'amountType',
        'created_at',
        'memo',
        'vendorNo'
    ],
]); ?>

<p>
    <?= Html::a('Kembali', ['journal-index'], ['class' => 'btn btn-secondary']) ?>
</p>
