<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Accounts;
use app\models\Journal;
use app\models\Journalumum;
use app\models\Journaldetail;
use app\models\DetailCompare;
use app\models\JournalCompare;
use app\models\AccountCompare;
use yii\data\ActiveDataProvider;
use yii\web\UploadedFile;
use app\models\JsonUploadForm;

class ApiController extends Controller
{ 
    public function beforeAction($action) 
    { 
        $this->enableCsrfValidation = false; 
        return parent::beforeAction($action); 
    }

    public function actionIndex()
    {
        $query = Accounts::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 100, 
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionJournalIndex()
    {
        $query = Journal::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 100, 
            ],
        ]);

        return $this->render('journalindex', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionJsonUploadIndex()
    {
        $model = new JsonUploadForm();

        if (Yii::$app->request->isPost) {
            $model->file = UploadedFile::getInstance($model, 'file');

            if ($model->validate()) {
                $uploadPath = Yii::getAlias('@webroot/uploads/');

                // Pastikan direktori tujuan ada, jika tidak buat baru
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                //hapus semua dulu lalu input ulang
                AccountCompare::deleteAll();

                // Simpan file di direktori
                $filePath = $uploadPath . $model->file->name;
                if ($model->file->saveAs($filePath)) {
                    // Baca isi file JSON
                    $jsonData = file_get_contents($filePath);
                    $decodedData = json_decode($jsonData, true);
                    
                    if (isset($decodedData['GLACCNT'])) {
                        $filteredData = [];
                        foreach ($decodedData['GLACCNT'] as $item) {
                            if (isset($item['GLACCOUNT']) && isset($item['ACCOUNTNAME'])) {
                                $currencyCode = "";
                                $accountType = "";
                                if (isset($item['CURRENCYID'])) {
                                    $currencyCode = $item['CURRENCYID'] == 1 ? "IDR" : "";
                                }
                                if (isset($item['FIRSTPARENTACCOUNT'])) {
                                    $parentNo = $item['FIRSTPARENTACCOUNT'] ?? "";
                                }
                                if (isset($item['ACCOUNTTYPE'])) {
                                    switch ($item['ACCOUNTTYPE']) {
                                        case 7:
                                            $accountType = "ACCOUNT_PAYABLE";
                                            break;
                                        case 8:
                                            $accountType = "ACCOUNT_RECEIVABLE";
                                            break;
                                        case 9:
                                            $accountType = "ACCUMULATED_DEPRECIATION";
                                            break;
                                        case 10:
                                            $accountType = "CASH_BANK";
                                            break;
                                        case 11:
                                            $accountType = "COGS";
                                            break;
                                        case 12:
                                            $accountType = "EQUITY";
                                            break;
                                        case 13:
                                            $accountType = "EXPENSE";
                                            break;
                                        case 14:
                                            $accountType = "FIXED_ASSET";
                                            break;
                                        case 15:
                                            $accountType = "INVENTORY";
                                            break;
                                        case 16:
                                            $accountType = "LONG_TERM_LIABILITY";
                                            break;
                                        case 17:
                                            $accountType = "OTHER_ASSET";
                                            break;
                                        case 18:
                                            $accountType = "OTHER_CURRENT_ASSET";
                                            break;
                                        case 19:
                                            $accountType = "OTHER_CURRENT_LIABILITY";
                                            break;
                                        case 20:
                                            $accountType = "OTHER_EXPENSE";
                                            break;
                                        case 21:
                                            $accountType = "OTHER_INCOME";
                                            break;
                                        case 22:
                                            $accountType = "REVENUE";
                                            break;
                                        default:
                                            $accountType = "";  // Default 
                                            break;
                                    }
                                }

                                $filteredData[] = [
                                    'no' => $item['GLACCOUNT'],
                                    'asOf' => date('d/m/Y'), 
                                    'accountType' => $accountType,
                                    'currencyCode' => $currencyCode,
                                    'name' => $item['ACCOUNTNAME'],
                                    'memo' => $item['MEMO'],
                                    'parentNo' => $parentNo,
                                ];

                                $AccountCompare = new AccountCompare();
                                $AccountCompare->no = $item['GLACCOUNT'];
                                $AccountCompare->accountType = $accountType;
                                $AccountCompare->name =$item['ACCOUNTNAME'];
                                $AccountCompare->parentNo = $parentNo;
                                $AccountCompare->save();
                            }
                        }
                        
                        Yii::$app->session->set('importAccountFromJson', $filteredData);
                        // Render view untuk menampilkan tabel
                        Yii::$app->session->setFlash('success', "File berhasil diunggah ke: {$filePath}");
                        return $this->render('jsonuploadaccounttable', [
                            'filteredData' => $filteredData,
                        ]);
                    } else {
                        Yii::$app->session->setFlash('error', 'Key "GLACCNT" tidak ditemukan dalam file JSON.');
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Gagal menyimpan file.');
                }

                return $this->refresh();
            }
        }

        return $this->render('jsonuploadindex', [
            'model' => $model,
        ]);
    }
    public function actionJsonUploadJurnalIndex()
    {
        $model = new JsonUploadForm();

        if (Yii::$app->request->isPost) {
            $model->journalFile = UploadedFile::getInstance($model, 'journalFile');
            $model->journalDetailFile = UploadedFile::getInstance($model, 'journalDetailFile');

            if ($model->validate()) {
                $uploadPath = Yii::getAlias('@webroot/uploads/');

                // Pastikan direktori tujuan ada, jika tidak buat baru
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                $journalFilePath = $uploadPath . $model->journalFile->name;
                $journalDetailFilePath = $uploadPath . $model->journalDetailFile->name;

                //hapus semua dulu lalu input ulang
                JournalCompare::deleteAll();
                DetailCompare::deleteAll();

                if ($model->journalFile->saveAs($journalFilePath) && $model->journalDetailFile->saveAs($journalDetailFilePath)) {
                    // Decode kedua file
                    $journalData = json_decode(file_get_contents($journalFilePath), true);
                    $journalDetailData = json_decode(file_get_contents($journalDetailFilePath), true);

                    if (isset($journalData['JV']) && isset($journalDetailData['JVDET'])) {
                        $filteredData = [];

                        // Gabungkan data Journal dan Journal Detail
                        foreach ($journalData['JV'] as $journal) {
                            $details = array_filter($journalDetailData['JVDET'], function ($detail) use ($journal) {
                                return $detail['JVID'] === $journal['JVID'];
                            });

                            $journalCompare = new JournalCompare();
                            $journalCompare->number = $journal['JVNUMBER'];
                            $journalCompare->trans_date = date('d/m/Y', strtotime($journal['TRANSDATE']));
                            $journalCompare->branchName = $journal['BRANCHCODE'];
                            $journalCompare->save();
                            
                            $filteredData[] = [
                                'number' => $journal['JVNUMBER'],
                                'transDate' => date('d/m/Y', strtotime($journal['TRANSDATE'])),
                                'description' => $journal['TRANSDESCRIPTION'],
                                'branchName' => $journal['BRANCHCODE'],
                                'journaldetail' => array_values(array_map(function ($detail) {
                                    // pemetaan GLACCOUNT
                                    $hasChanged = false;
                                    $accountNoOri = $detail['GLACCOUNT']; //simpan account original nya
                                    if ($detail['GLACCOUNT'] === '1002.01' || $detail['GLACCOUNT'] === '1002.03') {
                                        $accountNoOri = $detail['GLACCOUNT'];
                                        $detail['GLACCOUNT'] = '1002.00';
                                        $hasChanged = true;  //untuk menandakan di tabel berikutnya
                                    } else if ($detail['GLACCOUNT'] === '6202.01' || $detail['GLACCOUNT'] === '6202.02') {
                                        $accountNoOri = $detail['GLACCOUNT'];
                                        $detail['GLACCOUNT'] = '6202.00';
                                        $hasChanged = true;  //untuk menandakan di tabel berikutnya
                                    } 
                                    return [
                                        'accountNo' => $detail['GLACCOUNT'],
                                        'accountOri' => $accountNoOri,
                                        'amount' => (double)(str_replace('-', '', $detail['GLAMOUNT'])),
                                        'amountType' => isset($detail['SEQ']) && $detail['SEQ'] == 1 ? 'CREDIT' : 'DEBIT',
                                        'memo' => $detail['DESCRIPTION'],
                                        'vendorNo' => $detail['SUBSIDIARY'] ?? "",
                                        'hasChanged' => $hasChanged
                                    ];
                                }, $details)),
                            ];

                            foreach ($details as $detail) {
                                $detailCompare = new DetailCompare();
                                
                                // pemetaan GLACCOUNT
                                $accountMap = $detail['GLACCOUNT'];
                                if ($detail['GLACCOUNT'] === '1002.01' || $detail['GLACCOUNT'] === '1002.03') {
                                    $accountMap = '1002.00';
                                } else if ($detail['GLACCOUNT'] === '6202.01' || $detail['GLACCOUNT'] === '6202.02') {
                                    $accountMap = '6202.00';
                                } 

                                $detailCompare->number = $journal['JVNUMBER'];
                                $detailCompare->trans_date = date('Y-m-d', strtotime($journal['TRANSDATE'])); // Pastikan format tanggal sesuai dengan kolom di database
                                $detailCompare->account_no = $accountMap;
                                $detailCompare->account_ori = $detail['GLACCOUNT'];
                                $detailCompare->amount = (double)(str_replace('-', '', $detail['GLAMOUNT']));
                                $detailCompare->amount_type = isset($detail['SEQ']) && $detail['SEQ'] == 1 ? 'CREDIT' : 'DEBIT';
                                $detailCompare->save();
                            }                   
                        }

                        // echo "<pre>";
                        // print_r($filteredData);
                        // echo "</pre>";
                        // exit;

                        Yii::$app->session->set('importJournalFromJson', $filteredData);
                        Yii::$app->session->setFlash('success', "Files successfully uploaded and processed.");

                        // Render view untuk menampilkan tabel atau cek hasil
                        return $this->render('jsonuploadjournaltable', [
                            'filteredData' => $filteredData,
                        ]);
                    } else {
                        Yii::$app->session->setFlash('error', 'Invalid JSON format. Expected keys "JV" and "JVDET".');
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to save uploaded files.');
                }

                return $this->refresh();
            }
        }

        return $this->render('jsonuploadjurnalindex', [
            'model' => $model,
        ]);
    }

    //kalo send satu, perlu set id di parameter
    public function actionSendapi() 
    {
        // $selectedIds = Yii::$app->request->post('selection', []); 
        // $accounts = Accounts::findAll($selectedIds);
        $accounts = Yii::$app->session->get('importAccountFromJson', []);

        Yii::$app->session->set('accountData', $accounts);
        Yii::$app->session->set('inputScope', 'glaccount_save');

        return $this->redirect(['accurate/authorize']);
    }
    public function actionSendjournalapi() 
    {
        // $selectedIds = Yii::$app->request->post('selection', []);
        // $journal = Journal::find()->where(['id' => $selectedIds])->all();
        // $journals = Journal::find()->andWhere(['in', 'id', $selectedIds])->asArray()->all();

        // echo "<pre>";
        // print_r($journal);
        // echo "</pre>";
        // exit;

        // foreach ($journal as $journalprint) {
        //     var_dump($journalprint->attributes); // Menampilkan semua atribut dari model
        // }
        // exit; // Menghentikan eksekusi untuk melihat hasil

        $journals = Yii::$app->session->get('importJournalFromJson', []);

        // $journals = Journalumum::find()
        // ->with('journaldetail') // Menggunakan relasi ke detail
        // ->asArray()
        // ->all();

        // echo "<pre>";
        // print_r($journals);
        // echo "</pre>";
        // exit;

        // Siapkan struktur data untuk API
        $formattedData = [];
        foreach ($journals as $journal) {
            // Format data utama
            $journalData = [
                'number' => $journal['number'],
                'transDate' => $journal['transDate'],
                'description' => $journal['description'],
                'branchName' => $journal['branchName'],
                'detailJournalVoucher' => [],
            ];

            // Tambahkan detail jurnal
            foreach ($journal['journaldetail'] as $detail) {
                $journalData['detailJournalVoucher'][] = [
                    'accountNo' => $detail['accountNo'],
                    'amount' => $detail['amount'],
                    'amountType' => $detail['amountType'],
                    'memo' => $detail['memo'],
                    'vendorNo' => $detail['vendorNo'],
                ];
            }

            $formattedData[] = $journalData;
        }

        Yii::$app->session->set('journalData', $formattedData);
        Yii::$app->session->set('inputScope', "journal_voucher_save");

        return $this->redirect(['accurate/authorize']);
    }

    public function actionSendaccountstoaol() //ini ke database XD
    {
        // Ambil data dari session yang disimpan sebelumnya
        $importedAccounts = Yii::$app->session->get('importAccountFromJson', []);
        // var_dump($importedAccounts); die;
        
        if (empty($importedAccounts)) {
            Yii::$app->session->setFlash('error', 'Tidak ada data yang tersedia untuk diimpor.');
            return $this->redirect(['jsonuploadindex']);
        }
        
        try {
            foreach ($importedAccounts as $accountData) {
                $account = new Accounts();
                
                $data = [
                    'accountType' => $accountData['accountType'],
                    'asOf' => $accountData['asOf'],
                    'currencyCode' => $accountData['currencyCode'],
                    'name' => $accountData['name'],
                    'no' => $accountData['no'],
                    'parentNo' => $accountData['parentNo'],
                    'memo' => $accountData['memo'],
                ];

                $account->attributes = $data;
                $account->save();
            }
            
            // Menampilkan pesan sukses
            Yii::$app->session->setFlash('success', 'Akun berhasil masuk ke database!');
        } catch (\Exception $e) {
            // Jika terjadi error, batalkan transaksi dan tampilkan pesan error
            Yii::$app->session->setFlash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        return $this->redirect(['api/index']);
    }
    
    public function actionSendjournalstoaol() //ini ke database XD
    {
        $importedJournals = Yii::$app->session->get('importJournalFromJson', []);
        
        if (empty($importedJournals)) {
            Yii::$app->session->setFlash('error', 'Tidak ada data yang tersedia untuk diimpor.');
            return $this->redirect(['jsonuploadindex']);
        }
        
        try {
            foreach ($importedJournals as $journalData) {
                // Buat model baru untuk setiap account
                $journal = new Journal();
                
                $data = [
                    'number' => $journalData['number'],
                    'transDate' => $journalData['transDate'],
                    'description' => $journalData['description'],
                    'accountNo' => $journalData['accountNo'],
                    'amount' => $journalData['amount'],
                    'amountType' => $journalData['amountType'],
                    'memo' => $journalData['memo'],
                ];

                $journal->attributes = $data;
                $journal->save();
            }
            
            // Menampilkan pesan sukses
            Yii::$app->session->setFlash('success', 'Jurnal berhasil masuk ke database!');
        } catch (\Exception $e) {
            // Jika terjadi error, batalkan transaksi dan tampilkan pesan error
            Yii::$app->session->setFlash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        // Redirect ke halaman lain setelah proses selesai
        return $this->redirect(['api/journal-index']);
    }
}

