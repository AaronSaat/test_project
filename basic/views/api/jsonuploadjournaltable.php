<?php
use yii\helpers\Html;

$this->title = 'Imported Journals from JSON';
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>
<p class="text-muted">
    Notes:<br>
    -> <b>ammountType</b> adalah debit atau kredit, pada database accurate desktop disimbolkan dengan 0 = DEBIT dan 1 = CREDIT <br>
    -> Detail yang menggunakan pos akun <b>2000.05 Hutang PPh 23/Jasa</b> akan dimasukkan data pemasoknya yaitu Pemasok Umum (ID = 1000) <br>
    -> <b>accountNo dan accountOri yang diberi warna merah</b> adalah detail jurnal dengan pemetaan pos akun: <br>
    -> jika tidak diberi warna merah, maka tidak dilakukan pemetaan<br>
</p>

<table class="table table-bordered table-striped"> 
    <thead>
        <tr>
            <th>number</th>
            <th>transDate</th>
            <th>description</th>
            <th>accountNo</th>
            <th>accountOri</th>
            <th>amount</th>
            <th>amountType</th>
            <th>memo No</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($filteredData as $journal): ?>
            <?php foreach ($journal['journaldetail'] as $detail): ?>
                <tr>
                    <td><?= Html::encode($journal['number']) ?></td>
                    <td><?= Html::encode($journal['transDate']) ?></td>
                    <td><?= Html::encode($journal['description']) ?></td>
                    <td style="<?= $detail['hasChanged'] ? 'background-color: red;' : ''; ?>"><?= Html::encode($detail['accountNo']) ?></td>
                    <td style="<?= $detail['hasChanged'] ? 'background-color: red;' : ''; ?>"><?= Html::encode($detail['accountOri']) ?></td>
                    <td><?= Html::encode($detail['amount']) ?></td>
                    <td><?= Html::encode($detail['amountType']) ?></td>
                    <td><?= Html::encode($detail['memo']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tbody>
</table>
    
<div class="text-center">
    <strong>
        Total Detail Data: 
        <?php 
            $totalDetails = 0;
            foreach ($filteredData as $journal) {
                $totalDetails += count($journal['journaldetail']); // Hitung jumlah detail dalam setiap jurnal
            }
            echo $totalDetails; // Cetak total jumlah detail
        ?>
    </strong>
</div>

<!-- <div class="text-center">
    <em>Maksimum data yang diterima API Accurate Online: 100 data</em>
</div> -->

<div class="form-group text-center">
    <?= Html::a(
        'Insert All Journals to Accurate Online', 
        ['api/sendjournalapi'], 
        [
            'class' => 'btn btn-success', 
            'style' => 'width: 100%;',
            'data-confirm' => 'Are you sure you want to send all journals to Accurate Online?', 
            'data-method' => 'post', 
        ]
    ) ?>
</div>