<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

use App\Baccarat\Notifications\NotifierFactory;
use App\Baccarat\Service\Websocket\Channel\RedisChannel;
use App\Baccarat\Service\Websocket\MessageHandler\MessageHandler;
use App\Baccarat\Service\Websocket\MessageHandler\MessageHandlerInterface;
use Hyperf\Crontab\Strategy\CoroutineStrategy;
use Hyperf\Crontab\Strategy\StrategyInterface;
use Hyperf\Engine\Contract\ChannelInterface;
use Symfony\Component\Notifier\Notifier;
use Symfony\Component\Serializer\SerializerInterface;

return [
    SerializerInterface::class => App\Baccarat\Service\Serializer\SerializerFactory::class,
    Notifier::class => NotifierFactory::class,
    ChannelInterface::class => RedisChannel::class,
    MessageHandlerInterface::class => MessageHandler::class,
//    StrategyInterface::class => CoroutineStrategy::class,
];
