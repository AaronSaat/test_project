<?php
use yii\helpers\Html;

$this->title = 'Imported Accounts from JSON';
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>
<p class="text-muted">
    Notes:<br>
    -> <b>asOf</b> adalah tanggal dari pos akun yang harus diisi, namun pada tabel accurate desktop tidak ditemukan, jadi diisi kosong " " <br>
    -> <b>currencyCode</b> adalah mata uang yang harus diisi, dikonversikan menjadi IDR <br>
    -> <b>acccountType</b> adalah nama akun yang harus diisi, dimulai dari indeks 7 hingga 22 (dari accurate desktop) dengan keterangan masing-masing sebagai berikut:<br>
    <ul>
        <li>7 = ACCOUNT_PAYABLE</li>
        <li>8 = ACCOUNT_RECEIVABLE</li>
        <li>9 = ACCUMULATED_DEPRECIATION</li>
        <li>10 = CASH_BANK</li>
        <li>11 = COGS</li>
        <li>12 = EQUITY</li>
        <li>13 = EXPENSE</li>
        <li>14 = FIXED_ASSET</li>
        <li>15 = INVENTORY</li>
        <li>16 = LONG_TERM_LIABILITY</li>
        <li>17 = OTHER_ASSET</li>
        <li>18 = OTHER_CURRENT_ASSET</li>
        <li>19 = OTHER_CURRENT_LIABILITY</li>
        <li>20 = OTHER_EXPENSE</li>
        <li>21 = OTHER_INCOME</li>
        <li>22 = REVENUE</li>
    </ul>
</p>

<table class="table table-bordered table-striped"> 
    <thead>
        <tr>
            <th>No</th>
            <th>As Of</th>
            <th>Account Type</th>
            <th>Currency Code</th>
            <th>Name</th>
            <th>Memo</th>
            <th>Parent No</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($filteredData as $row): ?>
            <tr>
                <td><?= Html::encode($row['no']) ?></td>
                <td><?= Html::encode($row['asOf']) ?></td>
                <td><?= Html::encode($row['accountType']) ?></td>
                <td><?= Html::encode($row['currencyCode']) ?></td>
                <td><?= Html::encode($row['name']) ?></td>
                <td><?= Html::encode($row['memo']) ?></td>
                <td><?= Html::encode($row['parentNo']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="text-center">
    <strong>Total Data: <?= count($filteredData) ?></strong>
</div>
<!-- <div class="text-center">
    <em>Maksimum data yang diterima API Accurate Online: 100 data</em>
</div> -->

<div class="form-group text-center">
    <?= Html::a(
        'Insert All Accounts to Accurate Online', 
        ['api/sendapi'], 
        [
            'class' => 'btn btn-success', 
            'style' => 'width: 100%;',
            'data-confirm' => 'Are you sure you want to send all accounts to Accurate Online?', 
            'data-method' => 'post', 
        ]
    ) ?>
</div>