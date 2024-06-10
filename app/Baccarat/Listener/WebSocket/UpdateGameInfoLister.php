<?php

namespace App\Baccarat\Listener\WebSocket;

use App\Baccarat\Event\WebSocket\UpdateGameInfoEvent;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Websocket\Message\MessageProduct;
use Hyperf\Engine\Contract\ChannelInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
class UpdateGameInfoLister implements ListenerInterface
{
    public function __construct(
        protected readonly ContainerInterface $container,
        protected readonly ChannelInterface       $channel,
        protected readonly Output             $output
    )
    {
    }

    public function listen(): array
    {
        return [UpdateGameInfoEvent::class];
    }

    public function process(object $event): void
    {

        /** @var UpdateGameInfoEvent $event */
        $event->message->transformationLotteryResult()
            ->each(function (LotteryResult $lotteryResult) {
                if ($lotteryResult->isBaccarat() && $lotteryResult->issue) {

                    if ($lotteryResult->isBetting()) {
                        $this->channel->push($lotteryResult);
                        MessageProduct::pushMessage($lotteryResult->issue, $lotteryResult);
                        $this->output->info($lotteryResult);
                    }

                    if ($lotteryResult->isWaiting() && ($value = MessageProduct::popMessage($lotteryResult->issue)) && $value->rn) {
                        $lotteryResult->rn = $value->rn;
                        $this->channel->push($lotteryResult);
                        $this->output->info($lotteryResult);
                    }
                }
            });
    }
}