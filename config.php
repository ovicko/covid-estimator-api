<?php
return [
    'id' => 'api-app',
    // the basePath of the application will be the `micro-app` directory
    'basePath' => __DIR__,
    // this is where the application will find all controllers
    'controllerNamespace' => 'api\controllers',
    'bootstrap' => ['log'],

    'modules' => [
        'v1' => [
            'class' => 'api\modules\v1\Module',
        ],
    ],
    // set an alias to enable autoloading of classes from the 'micro' namespace
    'aliases' => [
        '@api' => __DIR__,
    ],
    

    'components' => [
        'request' => [
            'csrfParam' => '_csrf-salon-frontend',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],

        'response' => [
            // ...
            'formatters' => [
                \yii\web\Response::FORMAT_JSON => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    'prettyPrint' => YII_DEBUG, // use "pretty" output in debug mode
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ],
            ],
        ],

        'user' => [
            'identityClass' => 'api\models\User',
            'enableAutoLogin' => false,
            'identityCookie' => ['name' => '_identity_covid_api', 'httpOnly' => true],
        ],

        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'api\models\CustomLogger',
                    'categories' => ['api_request'],
                    'logFile' => '@api/runtime/logs/requests.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 20,
                    'levels' => ['info'],
                    'logVars' => [],
                    'prefix' => function ($message) {
                        return null;
                    }
                ],
            ],
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // [
                //     'class' => 'yii\rest\UrlRule', 
                //     'controller' => 'v1/estimator',
                //     'tokens' => [
                //         '{response}' => '<response:\\w+>'
                //     ],
                //     'patterns' => [
                //         'GET {response}' => 'covid',
                //     ],
                // ],
                'POST v1/on-covid-19/' => 'v1/estimator/covid',
                'POST,GET v1/on-covid-19/<response>' => 'v1/estimator/covid',
                //'GET v1/on-covid-19/logs' => 'v1/estimator/covid',
            ],
        ],
    ]
];