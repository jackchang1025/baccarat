<?php

use App\Baccarat\Service\BettingAmountStrategy\FlatNote;
use App\Baccarat\Service\BettingAmountStrategy\LayeredStrategy;
use App\Baccarat\Service\BettingAmountStrategy\MartingaleStrategy;
use App\Baccarat\Service\BettingAmountStrategy\WinEnter;
use App\Baccarat\Service\BettingAmountStrategy\WinningAdvancesLosingMinusStrategy;

return [
    'strategy' => [
        FlatNote::class,
        LayeredStrategy::class,
        MartingaleStrategy::class,
        WinningAdvancesLosingMinusStrategy::class,
        WinEnter::class,
    ],

    'platform'=>[
        'bacc'=>[
            'proxy'=>'http://192.168.31.204:10809',
//            'cookie' => BASE_PATH . '/runtime/baccarat.cookie',
            'cookie'=>[
                [
                    'Name'=>'__Host-next-auth.csrf-token',
                    'Value' => 'c77ff7835741d8091f45373b0c5f6beb4c292b865d08e3e86ee966fa022900df%7C1ae73569448d9823910bbb7109d7fb2c8676874ceb9da830d22b562464641894',
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
                    'Value' => 'https://www.bacc.bot',
                    'Domain' => 'www.bacc.bot',
                    'Path' => '/',
                    'Max-Age' => 'Session',
                    'Expires' => 'Session',
                    'Secure' => true,
                    'Discard' => true,
                    'HttpOnly' => true,
                ],
                [
                    'Name'=>'__Secure-next-auth.session-token',
                    'Value' => 'eyJhbGciOiJkaXIiLCJlbmMiOiJBMjU2R0NNIn0..6IiTN0OPaYDmk564.N58CftW_m0GreGGtV1GZXSiPoD4k_Ex9kzK6308U5dod7pKwPhmwwakVbXFAMqkv8yPqNFo4zo1BemiBaa5SphfiK7mm_R_L-Ty5eMMLeSUBAACg5lKOSuUS3wPQ9Tob6NxEPhWXd1RqypfCYwDLbcGeQXgaGX7jSMsJzX-sMvRX56OPo1c3ADy3NdjbIxGFC1oWB6mM_0vSSdtIAF8b35-Cdo4p1C8TrMohCWYw1gnwEwKSPdBCXBBKMcbBR4S7CKRvYndEzFkkPT4JTmwur0Bf3LKSiZMQTftl8KrUmzGl7eix8vgR6E1rLrfIA89EDmfR-EG2gfVze4a4kTLQtox4WSJ1-YzmyQ7V1pzpSl7WiYoPFZjpg5FByK8_nfyG-FEZDSQf-esHg9oTkeLNSluTQ9RziOvZ6pnHZhMRu4e5rWrYSf0.wME_znZ98XcQ9pS1sArOYw',
                    'Domain' => 'www.bacc.bot',
                    'Path' => '/',
                    'Max-Age' => '2024-05-31T16:29:43.304Z',
                    'Expires' => '2024-05-31T16:29:43.304Z',
                    'Secure' => true,
                    'Discard' => true,
                    'HttpOnly' => true,
                ],
                [
                    'Name'=>'__stripe_mid',
                    'Value' => '92bfcb88-a930-4cb3-9c3a-4fa8b0365d169df5f2',
                    'Domain' => '.www.bacc.bot',
                    'Path' => '/',
                    'Max-Age' => '2025-05-01T16:14:46.000Z',
                    'Expires' => '2025-05-01T16:14:46.000Z',
                    'Secure' => true,
                    'Discard' => true,
                    'HttpOnly' => false,
                ],
                [
                    'Name'=>'__stripe_sid',
                    'Value' => '7dd3f32a-fa2e-4adc-982d-c665d1f6166dd64ab6',
                    'Domain' => '.www.bacc.bot',
                    'Path' => '/',
                    'Max-Age' => '2024-05-01T16:44:46.000Z',
                    'Expires' => '2024-05-01T16:44:46.000Z',
                    'Secure' => true,
                    'Discard' => true,
                    'HttpOnly' => false,
                ],
                [
                    'Name'=>'crisp-client%2Fsession%2F0b622f68-5455-4157-b67d-e05429347276',
                    'Value' => 'session_a85beb1f-e435-4e3f-823d-c63998035a5e',
                    'Domain' => '.bacc.bot',
                    'Path' => '/',
                    'Max-Age' => '2024-10-31T04:14:50.000Z',
                    'Expires' => '2024-10-31T04:14:50.000Z',
                    'Secure' => false,
                    'Discard' => false,
                    'HttpOnly' => false,
                ],
                [
                    'Name'=>'crisp-client%2Fsocket%2F0b622f68-5455-4157-b67d-e05429347276',
                    'Value' => '1',
                    'Domain' => '.bacc.bot',
                    'Path' => '/',
                    'Max-Age' => '2024-05-03T16:14:51.000Z',
                    'Expires' => '2024-05-03T16:14:51.000Z',
                    'Secure' => false,
                    'Discard' => false,
                    'HttpOnly' => false,
                ],
                //__Host-next-auth.csrf-token	d8d8f6b6bcc632ac92d9868c323db4ec61fc75fde8f7d6299972c185d957c61f%7C195dbe64bc7ab7f69b61241faabca93e2007e7d1b4ac37f03f6028a976c94c6c	www.bacc.bot	/	Session	158	✓	✓	Lax		Medium
                //__Secure-next-auth.callback-url	https%3A%2F%2Fwww.bacc.bot	www.bacc.bot	/	Session	57	✓	✓	Lax		Medium
                //__Secure-next-auth.session-token	eyJhbGciOiJkaXIiLCJlbmMiOiJBMjU2R0NNIn0..8W6jNgLNmHUAEcCe.1V0ZEfxC5jsacL_Sf7HNgqe_49QDW66mjdEb9JKGO2KXRIRlYxylWbK5Wa5o-BtpW1tcsCJM3jd3sUwqWi-YpKJzn8SjoV1Fi82Jd1Sy8qlDwciAFcSHeirLNREzIZIRbtpxPKWRUE1cwENYRfN5l_J6ZNKhDORTA-SyIkxDXl6IyUYoLrnLnznf7AUQ3WUmsf5mWV0LsUS0zgNmwCe5BDxpcOrhqKft50B6WutvO4mJx19sU_hA4lQx6YubDMwLNMBKI3ACUqw9ebduPuSCkLPDLXm6-w7-RZCtvKWtu2wsYyurJ_TQCFQ1g9aI7WFGbZKclLpiKKdmylNofJVsLo4_WDD4X9SCjMQNx0XpaOYTBytxEqNCecuDGE6ytuTEILN8XPy_oJyz_GpjukNgcJ3rtbiLM8PGJmAsqFjNwlefT7WteVI.o6yEbtw3rHQgcFWDbsNiXg	www.bacc.bot	/	2024-05-31T16:24:47.935Z	548	✓	✓	Lax		Medium
                //__stripe_mid	92bfcb88-a930-4cb3-9c3a-4fa8b0365d169df5f2	.www.bacc.bot	/	2025-05-01T16:14:46.000Z	54		✓	Strict		Medium
                //__stripe_sid	7dd3f32a-fa2e-4adc-982d-c665d1f6166dd64ab6	.www.bacc.bot	/	2024-05-01T16:44:46.000Z	54		✓	Strict		Medium
                //crisp-client%2Fsession%2F0b622f68-5455-4157-b67d-e05429347276	session_a85beb1f-e435-4e3f-823d-c63998035a5e	.bacc.bot	/	2024-10-31T04:14:50.000Z	105			Lax		Medium
                //crisp-client%2Fsocket%2F0b622f68-5455-4157-b67d-e05429347276	1	www.bacc.bot	/	2024-05-03T16:14:51.000Z	61			Lax		Medium
            ]
        ],
    ]
];