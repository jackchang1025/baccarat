<?php

use App\Baccarat\Service\BettingAmountStrategy\FlatNote;
use App\Baccarat\Service\BettingAmountStrategy\LayeredStrategy;
use App\Baccarat\Service\BettingAmountStrategy\MartingaleStrategy;

return [
    'strategy' => [
        FlatNote::class,
        LayeredStrategy::class,
        MartingaleStrategy::class,
    ],

    'platform'=>[
        'bacc'=>[
            'proxy'=>'http://192.168.31.35:10811',
//            'cookie' => BASE_PATH . '/runtime/baccarat.cookie',

//cookie:__stripe_mid=ee0027a9-d13e-484b-b77f-591d9e65fadc532cef; __Host-next-auth.csrf-token=48fd242cf7b39211d5cab1019029c9654ebf4a3ce6f6225f00b3fed737582cee%7C41b331aebe52a217509a808cb3bc26ab967afad1e9ae57e672129b929e54fe13; __Secure-next-auth.callback-url=%2Fdashboard; ph_phc_sd3InY6ZTAIvmJE88kvUKQkgo3pkaUy9X1U8CXbSGTq_posthog=%7B%22distinct_id%22%3A%22019232ef-3090-7e03-a617-8764508e2fa0%22%2C%22%24sesid%22%3A%5B1735454893795%2C%220194112b-b7fc-741c-95d7-365a391f1aaf%22%2C1735454865404%5D%7D; __Secure-next-auth.session-token=eyJhbGciOiJkaXIiLCJlbmMiOiJBMjU2R0NNIn0..Udeqbldsb5irDvIh.cziBH9KjZg7iMAof01d8110mFFT3I0t7iodoVrK0saDjmOLgaBeAWDaiNq16p5i6OHO0SSv2t8f5JpU1tC1n7ZRTXfRM_4xSIoPIoc1z1hjJXyMBrTQH591UVo18F8K_-6E7beyDxsXvzSwDST2_3F3DHy1c1BuxEqeo1iJMsovcUTGSx4xtcTklyduDLA32VmBoyr-67RXtZhRpuXjrvpbG1_lByXhz8bLa_qMOMkfMW3YllOcnXDRE6aSjTvPAyLBPUh-OC8lCFYjmLou_uBvWKDHcTOv-QfdHBMZt871GPSe6WytIM77mAOpVWSGbccQBke_mh3bNkBm547hsw0BV60lngousMbDBGaffABjAzQgoxuQK2w4TyZ2nNjRAVkf0Aav2pWEiq8WgsiFhqnZkloNmF1s9huWUUbfCIceLP_SpLQ0.ANNOAG4PMWxzRsjwaMfyOA; crisp-client%2Fsession%2F0b622f68-5455-4157-b67d-e05429347276=session_dd889629-1b95-42a0-9d4b-507496125a96

            'accounts' => [
                'account_01' => [
                    'weight' => 1,
                    'cookies' => [
                        // [
                        //     'Name'=>'__stripe_mid',
                        //     'Value' => 'ee0027a9-d13e-484b-b77f-591d9e65fadc532cef',
                        //     'Domain' => '.www.bacc.bot',
                        //     'Path' => '/',
                        //     'Max-Age' => '2025-05-01T16:14:46.000Z',
                        //     'Expires' => '2025-05-01T16:14:46.000Z',
                        //     'Secure' => true,
                        //     'Discard' => true,
                        //     'HttpOnly' => false,
                        // ],
                        [
                            'Name'=>'__Host-next-auth.csrf-token',
                            'Value' => 'f21b4c30f4d64c8c2a67f1179ec2354d00f5b6714e8c3e89f85063ddb8fe91f8%7Cf460829994814d074b5a70d1112ee53734a6c25e5d4439e7cb97769ffb923755',
                            'Domain' => 'www.bacc.bot',
                            'Path' => '/',
                            'Max-Age' => 'Session',
                            'Expires' => 'Session',
                            'Secure' => true,
                            'Discard' => true,
                            'HttpOnly' => true,
                        ],
                        [
                            'Name'=>'__Secure-next-auth.callback-url',
                            'Value' => '%2Fdashboard',
                            'Domain' => 'www.bacc.bot',
                            'Path' => '/',
                            'Max-Age' => 'Session',
                            'Expires' => 'Session',
                            'Secure' => true,
                            'Discard' => true,
                            'HttpOnly' => true,
                        ],
                        [
                            'Name'=>'ph_phc_sd3InY6ZTAIvmJE88kvUKQkgo3pkaUy9X1U8CXbSGTq_posthog',
                            'Value' => '%7B%22distinct_id%22%3A%22019232ef-3090-7e03-a617-8764508e2fa0%22%2C%22%24sesid%22%3A%5B1744022858824%2C%2201960fdc-04b7-769f-93c4-8e304aa2a763%22%2C1744022799543%5D%7D',
                            'Domain' => '.www.bacc.bot',
                            'Path' => '/',
                            'Max-Age' => 'Session',
                            'Expires' => 'Session',
                            'Secure' => true,
                            'Discard' => true,
                            'HttpOnly' => true,
                        ],
                        [
                            'Name'=>'__Secure-next-auth.session-token',
                            'Value' => 'eyJhbGciOiJkaXIiLCJlbmMiOiJBMjU2R0NNIn0..n3kuiLaOApn7OBSu.OfquLQyWRBn5pejWe0lF0ZO4X_GlsBnZmY9NlRqErut_sEuYQyCmXmScVFisxaHiNGB1rmaP8dwZN1sA673wgHZfc2L7Iz5GVYPIcLC_C-ZnyzfG4iialnqKtS9HbviJMZns02mSZvTUOg1AJou0drsD7cUOIO1uca5V5CJQdmqTdgf9qH3In_l7gyCw2y05wLu5wmWqyIPUJcGPXtcA6C9A4OdInu-0JQEPSwpU3iJQKnrZyA9pbW23Qa27_wJdKwCKQ5ek6If9IcxXt_k57-jhw_R97GDVpALxrpmDV2Yo6higWe69mUgm2XDCfx3QGBfM8DIgX8X7Ec0vyNA83GYLp9vsS5KQdoeit08i2q4770U9xR7E5xzvdA__dWjY7sPMdjMrUwntps9-lEPcdlTKJcWe5xniugc_TWrMmYS6Du0NxlY.ZWXCyDh07Uq8Cto8Ql8tUw',
                            'Domain' => '.www.bacc.bot',
                            'Path' => '/',
                            'Max-Age' => 'Session',
                            'Expires' => 'Session',
                            'Secure' => true,
                            'Discard' => true,
                            'HttpOnly' => true,
                        ],
                        [
                            'Name'=>'crisp-client%2Fsession%2F0b622f68-5455-4157-b67d-e05429347276',
                            'Value' => 'session_dd889629-1b95-42a0-9d4b-507496125a96',
                            'Domain' => '.www.bacc.bot',
                            'Path' => '/',
                            'Max-Age' => 'Session',
                            'Expires' => 'Session',
                            'Secure' => true,
                            'Discard' => true,
                            'HttpOnly' => true,
                        ],
                    ]
                ],
                'account_02' => [
                    'weight' => 1,
                    'cookies' => [
                        // [
                        //     'Name'=>'__stripe_mid',
                        //     'Value' => 'ee0027a9-d13e-484b-b77f-591d9e65fadc532cef',
                        //     'Domain' => '.www.bacc.bot',
                        //     'Path' => '/',
                        //     'Max-Age' => '2025-05-01T16:14:46.000Z',
                        //     'Expires' => '2025-05-01T16:14:46.000Z',
                        //     'Secure' => true,
                        //     'Discard' => true,
                        //     'HttpOnly' => false,
                        // ],
                        [
                            'Name'=>'__Host-next-auth.csrf-token',
                            'Value' => 'b4bdadedb985db9d3dff2e9666413d23335ad771e85429ebe56cf49d595df4c9%7C7bd4f42810157df738964cc3b4ddff067ef52ad858100510472b8d71e6214d0b',
                            'Domain' => 'www.bacc.bot',
                            'Path' => '/',
                            'Max-Age' => 'Session',
                            'Expires' => 'Session',
                            'Secure' => true,
                            'Discard' => true,
                            'HttpOnly' => true,
                        ],
                        [
                            'Name'=>'__Secure-next-auth.callback-url',
                            'Value' => '%2Fdashboard',
                            'Domain' => 'www.bacc.bot',
                            'Path' => '/',
                            'Max-Age' => 'Session',
                            'Expires' => 'Session',
                            'Secure' => true,
                            'Discard' => true,
                            'HttpOnly' => true,
                        ],
                        [
                            'Name'=>'ph_phc_sd3InY6ZTAIvmJE88kvUKQkgo3pkaUy9X1U8CXbSGTq_posthog',
                            'Value' => '%7B%22distinct_id%22%3A%22019232ef-3090-7e03-a617-8764508e2fa0%22%2C%22%24sesid%22%3A%5B1744022858824%2C%2201960fdc-04b7-769f-93c4-8e304aa2a763%22%2C1744022799543%5D%7D',
                            'Domain' => '.www.bacc.bot',
                            'Path' => '/',
                            'Max-Age' => 'Session',
                            'Expires' => 'Session',
                            'Secure' => true,
                            'Discard' => true,
                            'HttpOnly' => true,
                        ],
                        [
                            'Name'=>'__Secure-next-auth.session-token',
                            'Value' => 'eyJhbGciOiJkaXIiLCJlbmMiOiJBMjU2R0NNIn0..qtohJ0NxUSkeG3Ye.WZBDCZdngDLr0gaPu4j-9Mbu-Z_W9ifaK-rkVqvIr67XyNaLqOSEh8VJiCXRYt5SmupsPUHsHMqJ_JzRNd9DCruEBdfizsr8dqmiEMsA6ESGM8PRey-O8TTnrtFNJ63PbXJaQFGsBa5_IjllWWorfDgIIxsaSGKNgw-9ddQHaSLcdvaWv4s9Vh-jvOEyi2stSFW6fUk1bX9ouUPS9-pNxZmdGLvwtBaci1zRP-OF7uIFfEt54AhviXVGk86zWi0IZLXM4syNgFFfBt3iBeXFxUll.6UB9nlg4YJtumTnwi3FIIQ',
                            'Domain' => '.www.bacc.bot',
                            'Path' => '/',
                            'Max-Age' => 'Session',
                            'Expires' => 'Session',
                            'Secure' => true,
                            'Discard' => true,
                            'HttpOnly' => true,
                        ],
                        [
                            'Name'=>'crisp-client%2Fsession%2F0b622f68-5455-4157-b67d-e05429347276',
                            'Value' => 'session_c0c2b1c8-41e5-4d0e-a5f7-d47efa4ea714',
                            'Domain' => '.www.bacc.bot',
                            'Path' => '/',
                            'Max-Age' => 'Session',
                            'Expires' => 'Session',
                            'Secure' => true,
                            'Discard' => true,
                            'HttpOnly' => true,
                        ],
                    ]
                ]
            ]
        ],
    ]
];