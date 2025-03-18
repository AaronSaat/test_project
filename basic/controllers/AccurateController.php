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
        Yii::$app->session->set('batchIndex', $batchIndex);
        $oauth = Oauth2Model::find()->one();
        
        if($oauth) {
            $is_session_id_active = $this->_cekSessionId($oauth->accessToken, $oauth->session_id,);
            if ($is_session_id_active) {
                $this->actionAddJournalVoucher($oauth->accessToken, $oauth->session_id, $oauth->host, $batchIndex);
            } else {
                $refresh_session_id = $this->_refreshSessionId($oauth->db_id, $oauth->session_id);
                if ($refresh_session_id) {
                    $this->logRefreshSessionId($refresh_session_id);
                    Oauth2Model::updateAll(['session_id' => $refresh_session_id['x_session_id']], ['id' => $oauth->id]);
                }
                $this->actionAddJournalVoucher($oauth->accessToken, $refresh_session_id['x_session_id'], $refresh_session_id['host'], $batchIndex);
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
            var_dump("callback function");
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
            $id = $databaseList[7]->{"id"};
            $model = Oauth2Model::find()->one();
            $model->db_id = 1768087; //pos baru 2
            // $model->db_id = 1755270; //pos baru
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
    }

    private function executeCurlRequest($url, $method = 'GET', $headers = [], $postData = null) 
    {

        $curl = curl_init();
        
        $options = [
            CURLOPT_URL => $url,

            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false, // Untuk development, hapus di production
        ];

        if ($postData && in_array($method, ['POST', 'PUT'])) {
            $options[CURLOPT_POSTFIELDS] = $postData;
        }

        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_TIMEOUT, 360); //timeout
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if ($err) {
            Yii::error("cURL Error: " . $err);
            return [
                'success' => false,
                'error' => $err,
                'httpCode' => $httpCode
            ];
        }

        return [
            'success' => true,
            'data' => json_decode($response, true),
            'httpCode' => $httpCode
        ];
    }

    public function actionAddJournalVoucher($accessToken, $session, $host, $batchIndex)
    {     
        $totalCount = JournalCompare::find()->count();
        $batchSize = 100;
        $totalBatch = ceil($totalCount / $batchSize);

        $journals = JournalCompare::find()
        ->with('details')
        ->asArray()
        ->offset(($batchIndex) * $batchSize) 
        ->limit($batchSize) // Ambil 100 data
        ->all();

        // titik berhenti rekursi
        if ($batchIndex == $totalBatch) {
            Yii::$app->session->setFlash('success', 'Semua jurnal berhasil dikirim.');
            return $this->redirect(['/error/journal-errors']); // Redirect ke halaman sukses
        }

        $headers = [
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
        $logFile = $uploadPath . "logfilejournal_batch_{$batchIndex}_{$dateTime}.txt";

        // Hitung waktu eksekusi sebelum batch dijalankan
        $startTime = microtime(true);
        $maxExecutionTime = ini_get('max_execution_time');

        foreach ($journals as $key => $journal) {
            
            $content["data"][$key] = [
                "number" => $journal["number"],
                "transDate" => $journal["transDate"],
                "description" => $journal["description"],
                "branchName" => $journal["branchName"],
                "detailJournalVoucher" => []
            ];
    
            foreach ($journal["details"] as $detailIndex => $detail) {
                $content["data"][$key]["detailJournalVoucher"][$detailIndex] = [
                    "accountNo" => $detail["accountNo"],
                    "amount" => $detail["amount"],
                    "amountType" => $detail["amountType"],
                    "memo" => $detail["memo"],  
                    "vendorNo" => $detail["vendorNo"]
                ];
    
                if (isset($detail["vendorNo"])) {
                    $content["data"][$key]["detailJournalVoucher"][$detailIndex]["vendorNo"] = "1000";
                    $content["data"][$key]["detailJournalVoucher"][$detailIndex]["subsidiaryType"] = "VENDOR";
                }
            }
        }

        // echo "<pre>";
        // print_r($content);
        // echo "</pre>";
        // exit;

        $url = $host . "/accurate/api/journal-voucher/bulk-save.do";

        $result = $this->executeCurlRequest(
            $url, 
            'POST', 
            $headers, 
            json_encode($content)
        );  
        // var_dump($result);
        
        // Log hasil eksekusi
        $executionTime = microtime(true) - $startTime;
        $remainingTime = $maxExecutionTime - $executionTime;   

        if ($result){
            // echo "<pre>";
            // print_r($result);
            // echo "</pre>";
            // exit;   
            if (!$result['success']) {
                //throw new \Exception("Curl request failed: " . $result['error']);
                $this->logBatchResults($logFile, "Gagal", $executionTime, $remainingTime, $accessToken, $session, $host, $batchIndex);
            } else {
                $response = $result['data'];
                $this->logBatchResults($logFile, $response, $executionTime, $remainingTime, $accessToken, $session, $host, $batchIndex);
            }
            
        } else {
            $this->logBatchResults($logFile, "Gagal", $executionTime, $remainingTime, $accessToken, $session, $host, $batchIndex);
        }

        sleep(5);
        ini_set('max_execution_time', 9999);
        if ($batchIndex % 30 == 0) {
            return $this->redirect(['accurate/authorize', 'batchIndex' => $batchIndex + 1]);
        } else {
            $this->actionAuthorize($batchIndex + 1);
        }
    }

    private function logBatchResults($logFile, $result, $executionTime, $remainingTime, $accessToken, $session, $host, $batchIndex)
    {
        $dateTime = date('Ymd_His');
        $logMessages = "--------------------------------------------\n";
        $logMessages .= "Log file created at: " . date('Y-m-d H:i:s') . "\n";
        $logMessages .= "--------------------------------------------\n";
        $logMessages .= "Access Token : $accessToken \n";
        $logMessages .= "Session : $session \n";
        $logMessages .= "Host : $host \n";
        $logMessages .= "Batch Index : $batchIndex \n";
        $logMessages .= "--------------------------------------------\n";
        $logMessages .= "Batch #{$batchIndex} - " . date('Y-m-d H:i:s') . "\n";
        $logMessages .= "--------------------------------------------\n";
        $logMessages .= "Batch: " . ($batchIndex) . " | Execution Time: " . round($executionTime, 2) . "s | Remaining Time: " . round($remainingTime, 2) . "s\n";
    
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
                } else if(strpos($response, 'Sudah ada data lain dengan Nomor') == true){
                    $status = 'duplicate';
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
            //sini
            $resultString = json_encode($result, JSON_PRETTY_PRINT);
            $logMessages .= "Batch Result: {$resultString}\n\n";
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

        $uploadPath = Yii::getAlias('@webroot/uploads/logfile/');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $dateTime = date('Ymd_His'); 
        file_put_contents($logFile, $logMessages, FILE_APPEND);
    }

    public function cekApi(){
        $oauth = Oauth2Model::find()->one();


        $is_session_id_active = $this->_cekSessionId($oauth->accessToken, $oauth->session_id,);
        if ($is_session_id_active) {
            $this->actionAddJournalVoucher($oauth->accessToken, $oauth->session_id, $oauth->host, $batchIndex);
        } else {
            $refresh_session_id = $this->_refreshSessionId($oauth->db_id, $oauth->session_id);
            if ($refresh_session_id) {
                Oauth2Model::updateAll(['session_id' => $refresh_session_id['x_session_id']], ['id' => $oauth->id]);
            }
            $this->actionAddJournalVoucher($oauth->accessToken, $oauth->session_id, $oauth->host, $batchIndex);
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
        $message = $e->getMessage();

        file_put_contents($logFile, "Error log file created at: " . date('Y-m-d H:i:s') . "\n");
        $logMessages = "--------------------------------------------\n";
        $logMessages .= "Message : $message \n"; 
        $logMessages .= "--------------------------------------------\n";
        file_put_contents($logFile, $logMessages, FILE_APPEND);
    }

    private function logRefreshSessionId($refresh_session_id)
    {
        date_default_timezone_set('Asia/Jakarta');

        $uploadPath = Yii::getAlias('@webroot/uploads/logfile/');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $dateTime = date('Ymd_His'); 
        $logFile = $uploadPath . "logfilerefreshsession_{$dateTime}.txt";
        
        file_put_contents($logFile, "Log file created at: " . date('Y-m-d H:i:s') . "\n");
        if (isset($result['d']) && is_array($result['d'])) {
            $response = is_array($item['d'] ?? null) ? json_encode($item['d']) : ($item['d'] ?? 'No message');
            $logMessages = "--------------------------------------------\n";
            $logMessages .= "Message : $response \n"; 
            $logMessages .= "--------------------------------------------\n";
        } else {
            $logMessages = "--------------------------------------------\n";
            $logMessages .= "No refresh session id \n"; 
            $logMessages .= "--------------------------------------------\n";
        }

        file_put_contents($logFile, $logMessages, FILE_APPEND);
    }
} 