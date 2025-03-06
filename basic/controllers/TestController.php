DAN 19 yey
<?php
namespace basic\controllers;

use yii\web\Controller;

class TestController extends Controller
{
    public function actionTestFunction()
    {
        $timestamp = date('Y-m-d H:i:s');
        return ['status' => 'success', 'message' => "Function executed at {$timestamp}"];
    }
}
