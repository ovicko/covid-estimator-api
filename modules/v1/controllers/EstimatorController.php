<?php

namespace api\modules\v1\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\web\Response;

/**
* Default controller for the `v1` module
*/
class EstimatorController extends ActiveController
{
    public $modelClass = 'api\models\TestModel';
    
    public function behaviors()
    {
        
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator'] = [
            'class' => 'yii\filters\ContentNegotiator',
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
                ]
                
            ];
            
            return $behaviors;
        }	
        
        
        public function actionCovid($response = 'json')
        {
            //used to manage float values
            if (version_compare(phpversion(), '7.1', '>=')) {
                ini_set( 'serialize_precision', -1 );
            }

            //check if the params are in array
            if (!in_array($response, ['json', 'xml','logs'])) {
                throw new \Exception("Response type of $response is not supported!");         
            }
            
            if ($response === 'xml') {
                //change to xml response
                Yii::$app->response->format = Response::FORMAT_XML;
            }

            if ($response === 'logs') {
                //returns the logs
                Yii::$app->response->format = Response::FORMAT_RAW;
                $responseTime = intval(\Yii::getLogger()->getElapsedTime() * 1000) . 'ms';
                $logMessage = Yii::$app->request->method . "\t" . Yii::$app->request->getUrl() . "\t" . Yii::$app->response->statusCode . "\t" . $responseTime;
                $responseData['message'] = $logMessage;
                Yii::info($logMessage, 'api_request');
                $logs = file_get_contents(Yii::getAlias("@api/runtime/logs/requests.log"));
                Yii::$app->response->headers->add('Content-Type', 'text/plain');
                return $logs;

            }

           //data received from post request
           $data = Yii::$app->request->post();
            //do a estimation
            $responseData = $this->covid19ImpactEstimator($data);
            //calculate time
            $responseTime = intval( \Yii::getLogger()->getElapsedTime() * 1000).'ms';
            //log messages
            $logMessage = Yii::$app->request->method."\t". Yii::$app->request->getUrl() . "\t". Yii::$app->response->statusCode."\t". $responseTime;
            //write the log message
            Yii::info($logMessage, 'api_request');

            return $responseData;
            
        }

        protected function covid19ImpactEstimator($data)
        {
        $response = array();

        $response['data'] = $data;
        $response['impact']['currentlyInfected'] = (int) $data['reportedCases'] * 10;
        $response['severeImpact']['currentlyInfected'] = (int) $data['reportedCases'] * 50;

        $timeToElapse = $data['timeToElapse'];

        if ($data['periodType'] === 'days') {
            $elapsedDays = $timeToElapse;
        }

        if ($data['periodType'] === 'weeks') {
            $elapsedDays = $timeToElapse * 7;
        }

        if ($data['periodType'] === 'months') {
            $elapsedDays = $timeToElapse * 30;
        }

        $_factor = intdiv($elapsedDays, 3);

        $response['impact']['infectionsByRequestedTime'] = (int) ($data['reportedCases'] * 10 * pow(2, $_factor));
        $response['severeImpact']['infectionsByRequestedTime'] = (int) ($data['reportedCases'] * 50 * pow(2, $_factor));

        $response['impact']['severeCasesByRequestedTime'] = (int) ($response['impact']['infectionsByRequestedTime'] * 0.15);
        $response['severeImpact']['severeCasesByRequestedTime'] = (int) ($response['severeImpact']['infectionsByRequestedTime'] * 0.15);

        $response['impact']['hospitalBedsByRequestedTime'] = (int) (($data['totalHospitalBeds'] * 35 / 100) - $response['impact']['severeCasesByRequestedTime']);
        $response['severeImpact']['hospitalBedsByRequestedTime'] = (int) (($data['totalHospitalBeds'] * 35 / 100) - $response['severeImpact']['severeCasesByRequestedTime']);

        $response['impact']['casesForICUByRequestedTime'] = (int) ($response['impact']['infectionsByRequestedTime'] * 5 / 100);
        $response['severeImpact']['casesForICUByRequestedTime'] = (int) ($response['severeImpact']['infectionsByRequestedTime'] * 5 / 100);

        $response['impact']['casesForVentilatorsByRequestedTime'] = (int) ($response['impact']['infectionsByRequestedTime'] * 2 / 100);
        $response['severeImpact']['casesForVentilatorsByRequestedTime'] = (int) ($response['severeImpact']['infectionsByRequestedTime'] * 2 / 100);

        $_avgDailyIncomeInUSD = $data['region']['avgDailyIncomeInUSD'];
        $_avgDailyIncomePopulation = $data['region']['avgDailyIncomePopulation'];

        $response['impact']['dollarsInFlight'] = (int) (($response['impact']['infectionsByRequestedTime'] * $_avgDailyIncomePopulation  * $_avgDailyIncomeInUSD) / $elapsedDays);
        $response['severeImpact']['dollarsInFlight'] = (int) (($response['severeImpact']['infectionsByRequestedTime'] * $_avgDailyIncomePopulation  * $_avgDailyIncomeInUSD) / $elapsedDays);

        return $response;
        }
        
        public function actionTest()
        {
            exit("Correct!");
        }

        
    }
    