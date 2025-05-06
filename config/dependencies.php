<?php

use App\Baccarat\Service\RoomManager\RoomManager;

return [
    // 移除CookieJarFactory的绑定
    // \App\Baccarat\Service\Platform\Bacc\AccountManager::class => \App\Baccarat\Service\Platform\Bacc\AccountManager::class,
    // \App\Baccarat\Service\Platform\Bacc\ClientPool::class => function () {
    //     return new ClientPool(
    //         \Hyperf\Context\ApplicationContext::getContainer()->get(ClientFactory::class),
    //         \Hyperf\Context\ApplicationContext::getContainer()->get(PoolFactory::class),
    //         100, // max_connections
    //         10   // min_connections
    //     );
    // },
    // \App\Baccarat\Service\Platform\Bacc\Client::class => function () {
    //     return new Client(
    //         \Hyperf\Context\ApplicationContext::getContainer()->get(ClientPool::class),
    //         \Hyperf\Context\ApplicationContext::getContainer()->get(AccountManager::class),
    //         \Hyperf\Context\ApplicationContext::getContainer()->get(LoggerFactory::class)->create('bacc')
    //     );
    // }

    RoomManager::class => RoomManager::class,
]; 