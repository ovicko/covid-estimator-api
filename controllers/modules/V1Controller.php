<?php

namespace micro\controllers;

use yii\rest\ActiveController;

class V1Controller extends ActiveController
{
    public $modelClass = 'micro\models\Post';

    public function behaviors()
    {
        // remove rateLimiter which requires an authenticated user to work
        $behaviors = parent::behaviors();
        unset($behaviors['rateLimiter']);
        return $behaviors;
    }

    public function actionOnCovid19()
    {
        return "Hello test";
    }
}