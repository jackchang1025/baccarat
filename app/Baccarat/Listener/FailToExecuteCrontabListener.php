<?php

namespace App\Baccarat\Listener;

use App\Baccarat\Service\LoggerFactory;
use Hyperf\Crontab\Event\FailToExecute;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;

#[Listener]
class FailToExecuteCrontabListener implements ListenerInterface
{

    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->create();
    }

    public function listen(): array
    {
        return [
            FailToExecute::class,
        ];
    }

    /**
     * @param object $event
     */
    public function process(object $event): void
    {
        /**
         * @var  FailToExecute $event
         */
        $this->logger->error("crontab:{$event->crontab->getName()} {$event->getThrowable()}");
    }
}