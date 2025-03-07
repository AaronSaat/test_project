<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Accounterror;
use app\models\Journalerror;

class AccurateController extends Controller
{ 
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function init()
    {
        parent::init();
        $params = Yii::$app->params['accurate'];
        $this->clientId = $params['client_id'];
        $this->clientSecret = $params['client_secret'];
        $this->redirectUri = $params['redirect_uri'];
    }

    public function actionAuthorize($batchIndex)
    {   
        Yii::$app->session->set('batchIndex', $batchIndex);

        //kalo sudah ada 3 itu langsung saja, gausah authorize lagi
        if(Yii::$app->session->get('sessionWeb') && Yii::$app->session->get('hostWeb') && Yii::$app->session->get('accesstokenWeb')) {
            $session = Yii::$app->session->get('sessionWeb');
            $host = Yii::$app->session->get('hostWeb');
            $accessToken = Yii::$app->session->get('accesstokenWeb');
            var_dump($session, $host, $accessToken);die;
            $this->actionAddJournalVoucher($accessToken, $session, $host, $batchIndex);
        } else {
            $url = "https://account.accurate.id/oauth/authorize?" . http_build_query([
                'client_id' => $this->clientId,
                'response_type' => 'code',
                'redirect_uri' => $this->redirectUri,
                // 'scope' => 'journal_voucher_delete',
                'scope' => Yii::$app->session['inputScope']
            ]);
    
            return $this->redirect($url);
        }

    }

    public function actionCallback()
    {
        $code = Yii::$app->request->get('code');
        $basicAuth = base64_encode("$this->clientId:$this->clientSecret");

        $header = [
            "Authorization: Basic $basicAuth",
            "Content-Type: application/x-www-form-urlencoded"
        ];

        $content = [
            "grant_type" => "authorization_code",
            "code" => $code,
            "redirect_uri" => $this->redirectUri
        ];

        $url = "https://account.accurate.id/oauth/token";

        $opts = [
            "http" => [
                "method" => "POST",     
                "header" => $header,
                "content" => http_build_query($content),
                "ignore_errors" => true,
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $json = json_decode($response);
        // var_dump($json);die;
        $accessToken = $json->{"access_token"};
        $refreshToken = $json->{"refresh_token"};
        
        if (isset($accessToken) && isset($refreshToken)) {
            Yii::$app->session->set('access_token', $accessToken);
            Yii::$app->session->set('refresh_token', $refreshToken);
            $this->getDatabaseList($accessToken);
        } else {
            echo "Failed to get access token";
            die;
        }
    }

    private function getDatabaseList($accessToken)
    {
        $url = "https://account.accurate.id/api/db-list.do";
        
        $header = [
            'Authorization: Bearer ' . $accessToken,
        ];

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => $header,
                "ignore_errors" => true,
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);

        $databaseList = json_decode($response)->{"d"};


        if (count($databaseList) > 0) {
            $id = $databaseList[6]->{"id"};
            $this->openDatabase($accessToken, $id);
        } else {
            echo "You do not have any database, please create database from https://accurate.id";
            die;
        }
    }

    private function openDatabase($accessToken, $id)
    {
        $header = [
            "Authorization: Bearer $accessToken",
        ];

        $url = "https://account.accurate.id/api/open-db.do?id=" . $id;

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => $header,
                "ignore_errors" => true,
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $session = json_decode($response)->{"session"};
        $host = json_decode($response)->{"host"};
        $accessToken = Yii::$app->session->get('accesstokenWeb', $accessToken);
        if (isset($session) && isset($host)) {
            Yii::$app->session->set('sessionWeb',$session);
            Yii::$app->session->set('hostWeb', $host);
        } else {
            echo "Failed to open database";
            die;
        }

        // $this->getJournal($accessToken, $session, $host);    
        // $this->deleteJournal($accessToken, $session, $host);    
        if (Yii::$app->session->get('inputScope') == "glaccount_save") {
            $this->bulkSaveAccount($accessToken, $session, $host);
        }        
        else if (Yii::$app->session->get('inputScope') == "journal_voucher_save") {
            $batchIndex = Yii::$app->session->get('batchIndex');
            $this->actionAddJournalVoucher($accessToken, $session, $host, $batchIndex);
        } 
        else if (Yii::$app->session->get('inputScope') == "journal_voucher_delete") {
            $this->deleteJournal($accessToken, $session, $host);    
        } 
        // Session::flush();
    }

    private function getJournal($accessToken, $session, $host)
    {               

        $header = [
            "Authorization: Bearer $accessToken",
            "X-SESSION-ID: $session",
        ];

        // Content
        $content = array(
            "fields" => "id,no,name"
        );

        $url = $host . "/accurate/api/item/list.do?" . http_build_query($content);
        // $url = $host . "/accurate/api/item/detail.do" . $number;

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => $header,
                "ignore_errors" => true,
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);

        // var_dump($response); die;
    }

    public function actionAddJournalVoucher($accessToken, $session, $host, $batchIndex)
    {   
        // var_dump($accessToken, $session, $host, $batchIndex);
        $data = Yii::$app->session->get('journalData');
        // $accessToken = Yii::$app->session->get('accessToken');
        // $session = Yii::$app->session->get('session');
        // $host = Yii::$app->session->get('host');

        // $data = [
        //     [
        //         "number" => "TEST_001_INPUT_VENDOR",
        //         "transDate" => "27/01/2025",
        //         "description" => "PELANGGAN MASUKIN",
        //         "detailJournalVoucher" => [
        //             [
        //                 "accountNo" => "210102",
        //                 "amount" => 100000,
        //                 "amountType" => "DEBIT",
        //                 // "employeeNo" => "E.00001",
        //                 // "customerNo" => "C.00001",
        //                 "vendorNo" => "1000",
        //                 "memo" => "Test"
        //             ],
        //             [
        //                 "accountNo" => "6221.02",
        //                 "amount" => 100000,
        //                 "amountType" => "CREDIT",
        //                 // "employeeNo" => "",
        //                 // "customerNo" => "",
        //                 // "vendorNo" => "1000",
        //                 "memo" => "Test"
        //             ],
        //         ]
        //     ]
        // ];
        
        $batchSize = 100; // Batasi jumlah data per batch
        $batches = array_chunk($data, $batchSize);
        $totalBatch = count($batches);
        // var_dump($accessToken, $session, $host);die;
        
        // titik berhenti rekursi
        if ($batchIndex == $totalBatch) {
            Yii::$app->session->setFlash('success', 'Semua jurnal berhasil dikirim.');
            return $this->redirect(['/error/journal-errors']); // Redirect ke halaman sukses
        }

        $header = [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $session",
            "Content-Type: application/json"
        ];
        
        date_default_timezone_set('Asia/Jakarta');

        $uploadPath = Yii::getAlias('@webroot/uploads/logfile/');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $dateTime = date('Ymd_His'); // Format: YYYYMMDD_HHMMSS
        $logFile = $uploadPath . "logfilejournal_{$dateTime}.txt";

        // Hitung waktu eksekusi sebelum batch dijalankan
        $startTime = microtime(true);
        $maxExecutionTime = ini_get('max_execution_time');

        $batch = $batches[$batchIndex];
        $content = ["data" => []];

        // JournalError::deleteAll();
        foreach ($batch as $journalIndex => $journal) {
            $content["data"][$journalIndex] = [
                "number" => $journal["number"],
                "transDate" => $journal["transDate"],
                "description" => $journal["description"],
                "branchName" => $journal["branchName"],
                "detailJournalVoucher" => []
            ];
    
            foreach ($journal["detailJournalVoucher"] as $detailIndex => $detail) {
                $content["data"][$journalIndex]["detailJournalVoucher"][$detailIndex] = [
                    "accountNo" => $detail["accountNo"],
                    "amount" => $detail["amount"],
                    "amountType" => $detail["amountType"],
                    "memo" => $detail["memo"],  
                    "vendorNo" => $detail["vendorNo"]
                ];
    
                if (isset($detail["vendorNo"])) {
                    $content["data"][$journalIndex]["detailJournalVoucher"][$detailIndex]["vendorNo"] = "1000";
                    $content["data"][$journalIndex]["detailJournalVoucher"][$detailIndex]["subsidiaryType"] = "VENDOR";
                }
            }
        }

        $url = $host . "/accurate/api/journal-voucher/bulk-save.do";

        $opts = [
            "http" => [
                "method" => "POST",
                "header" => $header,
                "content" => json_encode($content),
                "ignore_errors" => true,
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);
        // var_dump($result);die;

        // Hitung waktu eksekusi setelah batch dijalankan
        $executionTime = microtime(true) - $startTime;
        $remainingTime = $maxExecutionTime - $executionTime;   
        
        $this->logBatchResults($logFile, $batch, $result, $batchIndex + 1, "Journal", $executionTime, $remainingTime);
        
        Yii::$app->session->set('journalBatchIndex', $batchIndex + 1);

        return Yii::$app->response->redirect([
            'accurate/authorize', 'batchIndex' => $batchIndex + 1
        ]);
        
        // return $this->addJournalVoucher($accessToken, $session, $host, $batchIndex+1);
    }

    private function bulkSaveAccount($accessToken, $session, $host)
    {
        $accountData = Yii::$app->session->get('accountData');
        
        // Pisahkan data berdasarkan parentNo
        $batchWithEmptyParentNo = [];
        $batchWithFilledParentNo = [];

        foreach ($accountData as $data) {
            if (empty($data['parentNo'])) {
                $batchWithEmptyParentNo[] = $data;
            } else {
                $batchWithFilledParentNo[] = $data;
            }
        }

        $sortedAccountData = array_merge($batchWithEmptyParentNo, $batchWithFilledParentNo);
        
        $batchSize = 100;
        $batches = array_chunk($sortedAccountData, $batchSize);

        $header = [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $session",
            "Content-Type: application/json"
        ];

        $uploadPath = Yii::getAlias('@webroot/uploads/logfile/');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $logFile = $uploadPath . 'logfileaccount.txt';
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        
        $successCount = 0;
        $errorCount = 0;
        // AccountError::deleteAll();

        foreach ($batches as $batchIndex => $batch) {
            $cont = [];
            foreach ($batch as $key => $data) {
                $cont["data"][$key] = [
                    "accountType" => $data["accountType"],
                    "asOf" => $data["asOf"],
                    "currencyCode" => $data["currencyCode"],
                    "name" => $data["name"],
                    "no" => $data["no"],
                    "parentNo" => $data["parentNo"] ?? "", 
                    "memo" => $data["memo"],
                ];
            }

            $url = $host . "/accurate/api/glaccount/bulk-save.do";
            // $url = $host . "/accurate/api/glaccount/save.do";
        
            $opts = [
                "http" => [
                    "method" => "POST",
                    "header" => $header,
                    "content" => json_encode($cont),
                    "ignore_errors" => true,
                ]
            ];

            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);
            $result = json_decode($response, true);

            if (isset($result['d']) && is_array($result['d'])) {
                foreach ($result['d'] as $item) {
                    $message = $item['d'] ?? '';
                    if (is_string($message) && strpos($message, 'berhasil disimpan') !== false) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }                    
                }
            }

            $data = json_decode($response, true);
            $this->logBatchResults($logFile, $batch, $result, $batchIndex + 1, "Account");
        }   

        // Yii::$app->session->setFlash('success', "Total data berhasil: {$successCount}");
        // Yii::$app->session->setFlash('error', "Total data gagal: {$errorCount}");

        return $this->redirect(['error/account-errors']);
    }

    private function logBatchResults($logFile, $batch, $result, $batchNumber, $type, $executionTime, $remainingTime)
    {
        //perlu di cek lagi buat ['d']
        if($type == "Account"){
            if (isset($result['d']) && is_array($result['d'])) {
                foreach ($result['d'] as $item) {
                    $message = is_array($item['d'] ?? null) ? json_encode($item['d']) : ($item['d'] ?? 'No message');
                    $accountNo = is_array($item['r']['no'] ?? null) ? json_encode($item['r']['no']) : ($item['r']['no'] ?? 'No account number');
                    $accountTypeName = is_array($item['r']['accountTypeName'] ?? null) ? json_encode($item['r']['accountTypeName']) : ($item['r']['accountTypeName'] ?? 'No account type');
                    $nameWithIndentStrip = is_array($item['r']['nameWithIndentStrip'] ?? null) ? json_encode($item['r']['nameWithIndentStrip']) : ($item['r']['nameWithIndentStrip'] ?? 'No name');
    
                    // Tambahkan ke log
                    $logMessages .= "Message: {$message}\n";
                    $logMessages .= "Account No: {$accountNo}\n";
                    $logMessages .= "Account Type: {$accountTypeName}\n";
                    $logMessages .= "Name: {$nameWithIndentStrip}\n";
                    $logMessages .= "--------------------------------------------\n";
    
                    $status = strpos($message, 'berhasil disimpan') !== false ? 'success' : 'error';
                    // Simpan ke database
                    $accountError = new AccountError();
                    $accountError->info = $status;
                    $accountError->name = str_replace('-', '', $nameWithIndentStrip);;
                    $accountError->accountType = $accountTypeName;
                    $accountError->no = $accountNo;
                    $accountError->response = $message;
                    $accountError->save();
                }
            } else {
                $logMessages .= "No data found in the batch result.\n";
            }
    
            $resultString = json_encode($result, JSON_PRETTY_PRINT);
            $logMessages .= "Batch Result: {$resultString}\n\n";
    
            $uploadPath = Yii::getAlias('@webroot/uploads/logfile/');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            $logFileRaw = $uploadPath . 'logfileaccount_raw.txt';
            if (file_exists($logFileRaw)) {
                unlink($logFileRaw);
            }
            file_put_contents($logFileRaw, $resultString, FILE_APPEND);
            file_put_contents($logFile, $logMessages, FILE_APPEND);
        } else if($type == "Journal"){
            $dateTime = date('Ymd_His'); // Format: YYYYMMDD_HHMMSS
            file_put_contents($logFile, "Log file created at: " . date('Y-m-d H:i:s') . "\n");
            $logMessages = "Batch #{$batchNumber} - " . date('Y-m-d H:i:s') . "\n";
            $logMessages .= "--------------------------------------------\n";
            // Log hasil batch dan sisa time limit
            file_put_contents($logFile, "Batch: " . ($batchNumber + 1) . " | Execution Time: " . round($executionTime, 2) . "s | Remaining Time: " . round($remainingTime, 2) . "s\n", FILE_APPEND);
        
            if (isset($result['d']) && is_array($result['d'])) {
                foreach ($result['d'] as $item) {
                    $response = is_array($item['d'] ?? null) ? json_encode($item['d']) : ($item['d'] ?? 'No message');
                    $number = is_array($item['r']['number'] ?? null) ? json_encode($item['r']['number']) : ($item['r']['number'] ?? 'No journal number');
    
                    // Tambahkan ke log
                    $logMessages .= "Message: {$response}\n";
                    $logMessages .= "Journal Number: {$number}\n";
                    $logMessages .= "--------------------------------------------\n";
    
                    $status = strpos($response, 'berhasil disimpan') !== false ? 'success' : 'error';
                    // Simpan ke database
                    $journalError = new JournalError();
                    $journalError->info = $status;
                    $journalError->number = $number;
                    $journalError->response = $response;
                    $journalError->save();
                }
            } else {
                $logMessages .= "No data found in the batch result.\n";
            }
    
            $resultString = json_encode($result, JSON_PRETTY_PRINT);
            $logMessages .= "Batch Result: {$resultString}\n\n";

            $uploadPath = Yii::getAlias('@webroot/uploads/logfile/');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            $dateTime = date('Ymd_His'); // Format: YYYYMMDD_HHMMSS
            // $logFile = $uploadPath . "logfilejournal_{$dateTime}.txt";
            // if (file_exists($logFileRaw)) {
            //     unlink($logFileRaw);
            // }
            // file_put_contents($logFile, $resultString);
            file_put_contents($logFile, $logMessages, FILE_APPEND);
        }
        // Simpan jumlah sukses dan error ke flash
        // Yii::$app->session->addFlash('success', "{$successCount} account(s) berhasil disimpan.");
        // Yii::$app->session->addFlash('error', "{$errorCount} account(s) gagal disimpan.");
    }

    private function deleteJournal($accessToken, $session, $host)
    {
        // $data = Yii::$app->session->get('journalData');
        // $id = 2200;
        // 2197-2202
        // $ids = [2197, 2198, 2199, 2202]; 2000 - 2299
        // 999, 1999
        // 3304 - 3953
        
        // set id nya
        $ids = range(3954, 7.969);
        // $ids = Yii::$app->session->get('deleteJournalData');

        $header = [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $session",
            "Content-Type: application/json"
        ];
         
        foreach ($ids as $id) {
            $url = $host . "/accurate/api/journal-voucher/delete.do?id=" . $id;

            $opts = [
                "http" => [
                    "method" => "DELETE",
                    "header" => $header,
                    // "content" => json_encode($content),
                    "ignore_errors" => true,
                ]
            ];
            
            //delete
            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);
            $result = json_decode($response, true);
            
            // var_dump($resu/lt); die;
            if ($result['s']) {
                Yii::$app->session->setFlash('success', 'Journal deleted successfully.');
            } else {
                Yii::$app->session->setFlash('error', 'Failed to delete journal: ' . implode(", ", $result['d']));
            }
        }

        return $this->redirect(['/error/journal-errors']);
    }
    private function deleteAccount($accessToken, $session, $host)
    {
        // 1, 999
        $ids = range(1000, 1999);

        $header = [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $session",
            "Content-Type: application/json"
        ];
         
        foreach ($ids as $id) {
            $url = $host . "/accurate/api/glaccount/delete.do?id=" . $id;

            $opts = [
                "http" => [
                    "method" => "DELETE",
                    "header" => $header,
                    // "content" => json_encode($content),
                    "ignore_errors" => true,
                ]
            ];
            
            //delete
            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);
            $result = json_decode($response, true);
            
            // var_dump($result); die;
            if ($result['s']) {
                Yii::$app->session->setFlash('success', 'Account deleted successfully.');
            } else {
                Yii::$app->session->setFlash('error', 'Failed to delete Account: ' . implode(", ", $result['d']));
            }
        }

        return $this->redirect(['/error/account-errors']);
    }
} 