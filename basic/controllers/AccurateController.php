<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Accounterror;
use app\models\Journalerror;
use app\models\DetailCompare;
use app\models\JournalCompare;
use app\models\Oauth2Model;

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
        // Yii::$app->session->destroy();
        // panggil cek API
        Yii::$app->session->set('batchIndex', $batchIndex);
        $oauth = Oauth2Model::find()->one();

        //kalo sudah ada 3 itu langsung saja, gausah authorize lagi
        if($oauth) {
            $is_session_id_active = $this->_cekSessionId($oauth->accessToken, $oauth->session_id,);
            if ($is_session_id_active) {
                // $aol = $this->_accessAPI($action, $oauth->accessToken, $oauth->session_id, $oauth->host, $id);
                $this->actionAddJournalVoucher($oauth->accessToken, $oauth->session_id, $oauth->host, $batchIndex);
                // return $aol;
            } else {
                $refresh_session_id = $this->_refreshSessionId($oauth->db_id, $oauth->session_id);
                if ($refresh_session_id) {
                    Oauth2Model::updateAll(['session_id' => $refresh_session_id['x_session_id']], ['id' => $oauth->id]);
                }
                // $aol = $this->_accessAPI($action, $oauth->accessToken, $refresh_session_id['x_session_id'], $refresh_session_id['host'], $id);
                $this->actionAddJournalVoucher($oauth->accessToken, $refresh_session_id['x_session_id'], $refresh_session_id['host'], $batchIndex);
                // return $aol;
            }
        } else {
            $url = "https://account.accurate.id/oauth/authorize?" . http_build_query([
                'client_id' => $this->clientId,
                'response_type' => 'code',
                'redirect_uri' => $this->redirectUri,
                'scope' => 'journal_voucher_save',
                // 'scope' => Yii::$app->session['inputScope']
            ]);
    
            return $this->redirect($url);
        }

    }

    public function actionCallback()
    {
        // die;
        if(Yii::$app->request->get('error')){
            var_dump(Yii::$app->request->get('error'));
            die;
        }
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
        
        if (isset($json->{"access_token"}) && isset($json->{"refresh_token"})) {
            $accessToken = $json->{"access_token"};
            $refreshToken = $json->{"refresh_token"};
            // var_dump($accessToken, $refreshToken);die;
            
            $model = new Oauth2Model();
            $model->accessToken = $accessToken;
            $model->refreshToken = $refreshToken;
            $model->save();
            $this->getDatabaseList($accessToken);
        } else {
            var_dump($json);
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
            $model = Oauth2Model::find()->one();
            $model->db_id = 1755270; //pos baru
            // $model->db_id = 1755091; //pos lama
            $model->save();
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
        $json = json_decode($response);
        if (isset($json->{"session"}) && isset($json->{"host"})) {
            $session = $json->{"session"};
            $host = $json->{"host"};

            $oauth = Oauth2Model::find()->one();
            $oauth->session_id = $session;
            $oauth->host = $host;
            $oauth->save();
        } else {
            var_dump($json);
            die;
        }

        $oauth = Oauth2Model::find()->one();
        $this->actionAddJournalVoucher($oauth->accessToken, $oauth->session_id, $oauth->host, 0);
        // $this->getJournal($accessToken, $session, $host);    
        // $this->deleteJournal($accessToken, $session, $host);    
        // if (Yii::$app->session->get('inputScope') == "glaccount_save") {
        //     $this->bulkSaveAccount($accessToken, $session, $host);
        // }        
        // else if (Yii::$app->session->get('inputScope') == "journal_voucher_save") {
        //     $batchIndex = Yii::$app->session->get('batchIndex');
        //     $this->actionAddJournalVoucher($accessToken, $session, $host, $batchIndex);
        // } 
        // else if (Yii::$app->session->get('inputScope') == "journal_voucher_delete") {
        //     $this->deleteJournal($accessToken, $session, $host);    
        // } 
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
        try{
            // if($batchIndex == 1){
            //     Yii::$app->session->set('batchSave', 0);
            // }

            // $batchSave =  Yii::$app->session->get('batchSave');
            // if($batchIndex == $batchSave){
            //     $batchIndex = $batchIndex + 1;
            // }
            //too many redirect di index 19, jadi delete saat indexnya - 1
            

            $totalCount = JournalCompare::find()->count();
            $batchSize = 100;
            $totalBatch = ceil($totalCount / $batchSize);

            $journals = JournalCompare::find()
            ->with('details')
            ->asArray()
            ->offset(($batchIndex - 1) * $batchSize) 
            ->limit($batchSize) // Ambil 100 data
            ->all();

            $formattedData = [];
            foreach ($journals as $journal) {
                $journalData = [
                    'number' => $journal['number'],
                    'transDate' => $journal['transDate'],
                    'description' => $journal['description'],
                    'branchName' => $journal['branchName'],
                    'detailJournalVoucher' => [],
                ];
                
                foreach ($journal['details'] as $detail) {
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
            
            // titik berhenti rekursi
            if ($batchIndex == $totalBatch + 1) {
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

            $content = ["data" => []];

            // JournalError::deleteAll();
            foreach ($formattedData as $journalIndex => $journal) {
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

            // Hitung waktu eksekusi setelah batch dijalankan
            $executionTime = microtime(true) - $startTime;
            $remainingTime = $maxExecutionTime - $executionTime;   
            
            $this->logBatchResults($logFile, $result, "Journal", $executionTime, $remainingTime, $accessToken, $session, $host, $batchIndex);
            
            Yii::$app->session->set('journalBatchIndex', $batchIndex + 1);

            if($batchIndex % 10 == 0){
                return $this->redirect(['accurate/authorize', 'batchIndex' => $batchIndex + 1]);
            } else {
                $this->actionAuthorize($batchIndex + 1);
            }
        }catch (\yii\web\HttpException $e) {
            $this->logError($e);
            return [
                'status' => $e->statusCode,
                'message' => $e->getMessage(),
            ];
        }
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

    private function logBatchResults($logFile, $result, $type, $executionTime, $remainingTime, $accessToken, $session, $host, $batchIndex)
    {
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
            $logMessages = "--------------------------------------------\n";
            $logMessages .= "Access Token : $accessToken \n";
            $logMessages .= "Session : $session \n";
            $logMessages .= "Host : $host \n";
            $logMessages .= "Batch Index : $batchIndex \n";
            $logMessages .= "--------------------------------------------\n";
            $logMessages .= "Batch #{$batchIndex} - " . date('Y-m-d H:i:s') . "\n";
            $logMessages .= "--------------------------------------------\n";

            // Log hasil batch dan sisa time limit
            file_put_contents($logFile, "Batch: " . ($batchIndex) . " | Execution Time: " . round($executionTime, 2) . "s | Remaining Time: " . round($remainingTime, 2) . "s\n", FILE_APPEND);
        
            if (isset($result['d']) && is_array($result['d'])) {
                foreach ($result['d'] as $item) {
                    $response = is_array($item['d'] ?? null) ? json_encode($item['d']) : ($item['d'] ?? 'No message');
                    $number = is_array($item['r']['number'] ?? null) ? json_encode($item['r']['number']) : ($item['r']['number'] ?? 'No journal number');
    
                    // Tambahkan ke log
                    $logMessages .= "Message: {$response}\n";
                    $logMessages .= "Journal Number: {$number}\n";
                    $logMessages .= "--------------------------------------------\n";

                    $status;
    
                    if(strpos($response, 'berhasil disimpan') == true){
                        $status = 'success';
                    } else {
                        $status = 'error';
                    }
                    // Simpan ke database
                    $journalError = new JournalError();
                    $journalError->info = $status;
                    $journalError->number = $number;
                    $journalError->response = $response;
                    $journalError->save();
                }
            } else {
                //data nya sebenarnya masuk, tapi missing response aja / log file 
                $logMessages .= "Data in batch $batchIndex succesfully uploaded, but no response retrived.\n";

                for ($i = 0; $i < 100; $i++) {
                    $journalError = new JournalError();
                    $journalError->info = 'Missing response';
                    $journalError->number = 'No journal number';
                    $journalError->response = 'Data kemungkinan sudah terupload, namun tidak ada response. Coba cek AOL';
                    $journalError->save();
                }
            }
            
            //sini
            $resultString = json_encode($result, JSON_PRETTY_PRINT);
            $logMessages .= "Batch Result: {$resultString}\n\n";

            $uploadPath = Yii::getAlias('@webroot/uploads/logfile/');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            $dateTime = date('Ymd_His'); 
            file_put_contents($logFile, $logMessages, FILE_APPEND);
        }
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

    public function cekApi(){
        $oauth = Oauth2Model::find()->one();


        $is_session_id_active = $this->_cekSessionId($oauth->accessToken, $oauth->session_id,);
        if ($is_session_id_active) {
            // $aol = $this->_accessAPI($action, $oauth->accessToken, $oauth->session_id, $oauth->host, $id);
            $this->actionAddJournalVoucher($oauth->accessToken, $oauth->session_id, $oauth->host, $batchIndex);
            // return $aol;
        } else {
            $refresh_session_id = $this->_refreshSessionId($oauth->db_id, $oauth->session_id);
            if ($refresh_session_id) {
                Oauth2Model::updateAll(['session_id' => $refresh_session_id['x_session_id']], ['id' => $oauth->id]);
            }
            // $aol = $this->_accessAPI($action, $oauth->accessToken, $refresh_session_id['x_session_id'], $refresh_session_id['host'], $id);
            $this->actionAddJournalVoucher($oauth->accessToken, $oauth->session_id, $oauth->host, $batchIndex);
            // return $aol;
        }
    }

    private function _cekSessionId($accessToken, $session_id)
    {
        $header = [
            "Authorization: Bearer $accessToken",
        ];

        $url = "https://account.accurate.id/api/db-check-session.do?session=" . $session_id;

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => $header,
                "ignore_errors" => true,
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);

        $result = json_decode($response, true);

        if ($result["s"] && $result["d"]) {
            return true;
        } else {
            return false;
        }
    }

    private function _refreshSessionId($db_id, $session_id)
    {
        $header = [
            "Content-Type: application/json",
        ];

        $content = [
            "id" => $db_id,
            "session" => $session_id,
        ];

        $url = "https://account.accurate.id/api/db-refresh-session.do";

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => $header,
                "content" => json_encode($content),
                "ignore_errors" => true,
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);

        $result = json_decode($response, true);

        if ($result["s"] && $result["d"]) {
            return $result["d"];
        } else {
            return false;
        }
    }

    private function logError($e)
    {
        date_default_timezone_set('Asia/Jakarta');

        $uploadPath = Yii::getAlias('@webroot/uploads/logfile/');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $dateTime = date('Ymd_His'); 
        $logFile = $uploadPath . "logfileerror_{$dateTime}.txt";
        $statuscode = $e->statusCode;
        $message = $e->getMessage();

        $dateTime = date('Ymd_His'); // Format: YYYYMMDD_HHMMSS
        file_put_contents($logFile, "Error log file created at: " . date('Y-m-d H:i:s') . "\n");
        $logMessages = "--------------------------------------------\n";
        $logMessages .= "Status Code : $statuscode \n"; //ganti error message
        $logMessages .= "Message : $message \n"; //ganti error message
        $logMessages .= "--------------------------------------------\n";
        file_put_contents($logFile, $logMessages, FILE_APPEND);
    }
} 