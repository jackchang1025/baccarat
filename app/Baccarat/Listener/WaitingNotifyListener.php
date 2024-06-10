<?php

declare(strict_types=1);

namespace App\Baccarat\Listener;

use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Event\WaitingEvent;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Rule\CustomizeRules;
use App\Baccarat\Service\Rule\RuleEngine;
use App\Baccarat\Service\Rule\RuleInterface;
use App\Baccarat\Service\Websocket\Message\MessageConsumer;
use Carbon\Carbon;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notifier;

#[Listener]
class WaitingNotifyListener implements ListenerInterface
{
    public function __construct(
        protected readonly ContainerInterface         $container,
        protected readonly Notifier         $notifier,
        protected readonly Output $output
    )
    {
    }

    public function listen(): array
    {
        return [
            WaitingEvent::class,
        ];
    }

    public function process(object $event): void
    {
        /**
         * @var BettingEvent $event
         */

        try {

            $deck = $event->baccaratBettingWaitingResult->getDeck();
            if (!$deck->baccaratLotterySequence){
                return;
            }

            $lotteryResult = $event->baccaratBettingWaitingResult->getLotteryResult();
            if ($lotteryResult->terrace === MessageConsumer::LOTTERY_TERRACE) {
                $this->output->info("baccaratLotterySequence: {$deck->baccaratLotterySequence}");
            }

            $ruleEngine = make(RuleEngine::class);
            //$pattern,protected string $bettingValue,protected string $name
            $ruleEngine->addRule(make(CustomizeRules::class,[
                'pattern' => '/B{10,}$|P{10,}$/',
                'bettingValue' => '',
                'name' => '长龙 + 10',
            ]));

            $ruleEngine->addRule(make(CustomizeRules::class,[
                'pattern' => '/BPBPBPBPBP$|PBPBPBPBPB$/',
                'bettingValue' => '',
                'name' => '单跳长龙 + 10',
            ]));

            $ruleEngine->addRule(make(CustomizeRules::class,[
                'pattern' => '/B{4,}P{4,}B{4,}$|P{4,}B{4,}P{4,}$/',
                'bettingValue' => '',
                'name' => '金手指',
            ]));

            $rules = $ruleEngine->applyRules($deck->baccaratLotterySequence);
            $rules->each(function (RuleInterface $rule) use ($deck,$lotteryResult){

                $notification = new Notification(Carbon::now().": {$lotteryResult->getTerrace()} {$lotteryResult->getTerraceName()} {$rule->getName()}: $deck->baccaratLotterySequence",['wechat_work']);
                $this->notifier->send($notification);
            });

        } catch (\Exception $e) {
            $this->container->get(LoggerFactory::class)->create()->error($e);
        }
    }
}
