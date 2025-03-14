<?php

namespace app\controllers;

use Yii;
use app\models\JournalError;
use app\models\DetailCompare;
use app\models\JournalCompare;
use app\models\AccountError;
use app\models\AccountCompare;
use yii\data\ActiveDataProvider;
use yii\web\Controller;


class ErrorController extends Controller
{
    // public function actionJournalErrors()
    // {
    //     $dataProvider = new ActiveDataProvider([
    //         'query' => JournalError::find(),
    //         'pagination' => [
    //             'pageSize' => 100,
    //         ],
    //     ]);

    //     $dataProvider2 = new ActiveDataProvider([
    //         'query' => DetailCompare::find(),
    //         'pagination' => [
    //             'pageSize' => 100,
    //         ],
    //     ]);

    //      // Hitung jumlah 'success' dan 'error' di JournalError
    //     $successCountJournalError = JournalError::find()->where(['info' => 'success'])->count();
    //     $errorCountJournalError = JournalError::find()->where(['info' => 'error'])->count();

    //     return $this->render('journalerrors', [
    //         'dataProvider' => $dataProvider,
    //         // 'dataProvider2' => $dataProvider2,
    //         'successCountJournalError' => $successCountJournalError,
    //         'errorCountJournalError' => $errorCountJournalError,
    //     ]);
    // }

    public function actionJournalErrors($filter = 'error')
    {
        $query = JournalError::find();

        if ($filter === 'error') {
            $query->where(['info' => 'error']);
        } elseif ($filter === 'success') {
            $query->where(['info' => 'success']);
        } elseif ($filter === 'Missing response') {
            $query->where(['info' => 'Missing response']);
        } elseif ($filter === 'duplicate') {
            $query->where(['info' => 'duplicate']);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 200,
            ],
        ]);

        // Hitung jumlah 'success' dan 'error' di JournalError
        $successCountJournalError = JournalError::find()->where(['info' => 'success'])->count();
        $errorCountJournalError = JournalError::find()->where(['info' => 'error'])->count();
        $missingCountJournalError = JournalError::find()->where(['info' => 'Missing response'])->count();
        $duplicateCountJournalError = JournalError::find()->where(['info' => 'duplicate'])->count();

        return $this->render('journalerrors', [
            'dataProvider' => $dataProvider,
            'successCountJournalError' => $successCountJournalError,
            'errorCountJournalError' => $errorCountJournalError,
            'missingCountJournalError' => $missingCountJournalError,
            'duplicateCountJournalError' => $duplicateCountJournalError,
            'filter' => $filter,
        ]);
    }

    public function actionViewJournalDetails($id)
    {
        $journalError = JournalError::findOne($id);
        $journalErrorIds = JournalError::find()->select(['id'])->column();
        $position = array_search($id, $journalErrorIds);
        $journalCompareIds = JournalCompare::find()->select(['id'])->column();
        $journalCompare = JournalCompare::findOne($journalCompareIds[$position]);


        if (!$journalError) {
            throw new NotFoundHttpException('Jurnal tidak ditemukan.');
        }

        // Update the query to use indexStart and totalCount
        $dataProvider = new ActiveDataProvider([
            'query' => DetailCompare::find()
                ->where(['number' => $journalCompare->number]),
            'pagination' => false, // Jika tidak perlu pagination, tetap false
        ]);

        return $this->render('viewjournaldetails', [
            'journalError' => $journalError,
            'journalCompare' => $journalCompare,
            'dataProvider' => $dataProvider,
        ]);
    }


    public function actionAccountErrors($filter = 'error')
    {
        // Buat query dasar untuk AccountError
        $query = AccountError::find();

        // Terapkan filter berdasarkan parameter
        if ($filter === 'error') {
            $query->where(['info' => 'error']);
        } elseif ($filter === 'success') {
            $query->where(['info' => 'success']);
        }  

        $dataProvider2 = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);

        $successCountAccountError = AccountError::find()->where(['info' => 'success'])->count();
        $errorCountAccountError = AccountError::find()->where(['info' => 'error'])->count();

        $dataProvider = new ActiveDataProvider([
            'query' => AccountCompare::find(),
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);

        return $this->render('accounterrors', [
            'dataProvider' => $dataProvider,
            'dataProvider2' => $dataProvider2,
            'successCountAccountError' => $successCountAccountError,
            'errorCountAccountError' => $errorCountAccountError,
            'filter' => $filter,
        ]);
    }

    public function actionDeleteAllJournal()
    {
        if (Yii::$app->request->isPost) {
            JournalError::deleteAll();
            Yii::$app->session->setFlash('success', 'All error logs have been deleted.');
            return $this->redirect(['error/journal-errors']);
        }

        // Jika bukan POST request, tampilkan error
        throw new \yii\web\BadRequestHttpException('Invalid request method.');
    }
    public function actionDeleteAllAccount()
    {
        if (Yii::$app->request->isPost) {
            AccountError::deleteAll();
            Yii::$app->session->setFlash('success', 'All error logs have been deleted.');
            return $this->redirect(['error/account-errors']);
        }

        // Jika bukan POST request, tampilkan error
        throw new \yii\web\BadRequestHttpException('Invalid request method.');
    }
}
