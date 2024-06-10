<?php

declare(strict_types=1);

namespace App\Baccarat\Listener;

use App\Baccarat\Cache\DeckBettingCache;
use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Mapper\BaccaratSimulatedBettingLogMapper;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Platform\Bacc\Bacc;
use App\Baccarat\Service\Rule\CustomizeRules;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
class BaccListener implements ListenerInterface
{
    public function __construct(
        protected readonly ContainerInterface                $container,
        protected readonly Bacc                              $bacc,
        protected readonly BaccaratSimulatedBettingLogMapper $bettingLogMapper,
        protected readonly DeckBettingCache                  $bettingCache,
        protected readonly Output                            $output
    )
    {
    }

    public function listen(): array
    {
        return [
            BettingEvent::class,
        ];
    }

    public function process(object $event): void
    {
        /**
         * @var BettingEvent $event
         */

        try {

            $deck = $event->baccaratBettingWaitingResult->getDeck();
            if (is_null($deck)) {
                return;
            }

            $baccaratList = [
                "300177",
                "300178",
                "300179",
                "300180",
                "300181",
                "300188",
                "300189",
                "300190",
            ];

            //从数据库获取开奖记录
            $baccaratLotteryLog = $event->baccaratBettingWaitingResult->getLotteryLog();

            //如果当前开奖记录为空，或者这一牌靴已经投注，则不进行投注
            //根据 bacc 的文档，没一局只赢一注就可以了，所以这一牌靴已经投注并且为 true 则不进行投注
            if (is_null($baccaratLotteryLog) || $this->bettingCache->get($baccaratLotteryLog->terrace_deck_id)) {
                return;
            }

            // 将 $deck->baccaratLotterySequence 字符串中 B 替换为 0，P 替换为 1
            $baccaratLotterySequenceString = str_replace(['B', 'P'], ['1', '0'], $deck->baccaratLotterySequence);

            // 将 $baccaratLotterySequence 切割为数组
            $baccaratLotterySequence = array_map('intval', str_split($baccaratLotterySequenceString));
            if (count($baccaratLotterySequence) <= 15 || count($baccaratLotterySequence) >= 50) {
                return;
            }

            // 计算结果
            $response = $this->bacc->calculate($baccaratLotterySequence);
            $this->output->info("convert {$baccaratLotterySequenceString} message:{$response->getMessage()}");

            // 判断是否需要投注
            if ($response->getBets()) {
                // 开始投注
                $rule = new CustomizeRules(pattern: '//', bettingValue: $response->convertBets(), name: 'bacc');

                $this->bettingLogMapper->getBaccaratSimulatedBettingLogOrCreate(
                    rule: $rule,
                    attributes: [
                        'issue'      => $baccaratLotteryLog->issue,
                        'betting_id' => 6666,
                    ],
                    values: [
                        'terrace_deck_id' => $baccaratLotteryLog->terrace_deck_id,
                        'betting_value'   => $rule->getBettingValue(),
                        'created_at'      => $baccaratLotteryLog->created_at,
                        'remark'          => $response->getCredibility(),
                    ]
                );
            }

        } catch (\Exception $e) {
            $this->container->get(LoggerFactory::class)->create('bacc')->error($e->getMessage(), ['Exception' => $e]);
            $this->output->error($e->getMessage());
        }
    }
}
