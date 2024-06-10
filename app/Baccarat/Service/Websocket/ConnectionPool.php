<?php

namespace App\Baccarat\Service\Websocket;

use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Websocket\MessageDecoder\MessageDecoder;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Crontab\LoggerInterface;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ConnectionPool extends Pool
{

    public function __construct(
        protected ContainerInterface $container,
        protected Output             $output,
        protected float              $connectionCreationIntervalTime = 300,
        protected float              $connectionCheckIntervalTime = 5,
        array                        $config = [],
    )
    {
        parent::__construct($container, $config);
    }

    /**
     * @return WebSocketConnectionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Exception
     */
    public function createConnection(): WebSocketConnectionInterface
    {
        try {

            $config = $this->container->get(ConfigInterface::class);

            return make(Connection::class, [
                'messageDecoder' => make(MessageDecoder::class),
                'host' => $config->get('websocket.host'),
                'connectionTimeout' => $config->get('websocket.connectionTimeout'),
                'remainingTimeOut' => $config->get('websocket.remainingTimeOut'),
            ]);

        } catch (\Exception $e) {
            // 记录日志或采取其他措施
            $this->container->get(LoggerFactory::class)->create()->error('Failed to create connection: ' . $e);
            throw $e;
        }
    }
}