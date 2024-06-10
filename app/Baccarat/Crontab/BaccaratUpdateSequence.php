<?php

namespace App\Baccarat\Crontab;

use App\Baccarat\Mapper\BaccaratTerraceDeckMapper;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\Locker\LockerFactory;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use Hyperf\Coroutine\Concurrent;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Database\Model\Collection;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Hyperf\Stringable\Str;
use Lysice\HyperfRedisLock\Lock;
use Psr\Log\LoggerInterface;

#[Crontab(name: "baccaratUpdateSequence", rule: "0 0 * * *", callback: "execute", memo: "每天凌晨 0 点执行更新牌局序列号")]
//#[Crontab(name: "baccaratUpdateSequence", rule: "* * * * *", callback: "execute", memo: "每分钟执行更新牌局序列号")]
class BaccaratUpdateSequence
{
    protected Concurrent $concurrent;
    protected LoggerInterface $logger;
    protected Lock $lock;
    protected RedisProxy $redisProxy;

    const TableName = 'baccarat_update_sequence';

    public function __construct(
        protected readonly BaccaratTerraceDeckMapper $deckMapper,
        protected readonly Output                    $output,
        protected readonly LockerFactory             $lockerFactory,
        RedisFactory             $redisFactory,
        LoggerFactory                                $loggerFactory,
        int                                          $concurrent = 100
    )
    {
        $this->concurrent = new Concurrent(min(1, $concurrent));
        $this->logger = $loggerFactory->create();
        $this->redisProxy = $redisFactory->get('baccarat');
    }

    public function execute(): void
    {
        // 加锁 保证任务唯一
        // 86400 秒保证锁的过期时间 今日零点过期
        $this->lockerFactory->get('baccaratUpdateSequence', 'redis', 86400)
            ->get(function () {

                $this->output->info("baccaratUpdateSequence start");

                $this->process();

                $this->output->info("baccaratUpdateSequence success");
            });
    }

    protected function exists(string|int $baccaratTerraceDeckId): bool|\Redis
    {
        return $this->redisProxy->hExists(self::TableName,(string) $baccaratTerraceDeckId);
    }

    public function set(string|int $baccaratTerraceDeckId): bool|int|\Redis
    {
        return $this->redisProxy->hSet(self::TableName,(string) $baccaratTerraceDeckId,1);
    }

    protected function process(): void
    {
        $this->deckMapper->getModel()
            ->whereDate('created_at', '<', date('Y-m-d'))
            ->selectRaw('id, lottery_sequence, created_at')
            ->chunk(1000, function (Collection $decks) {

                //使用 Chunk 方法实现流式查询和分批处理数据,以避免一次性加载过多数据导致内存溢出的问题。Chunk 方法可以将查询结果分块处理,每次只处理一部分数据,减少内存的占用。
                //这里注意 当 BaccaratTerraceDeck 模型数据量过大时,使用 with 方法预加载关联关系可能会导致生成的 WHERE IN 查询语句性能较差。这是因为 IN 子句中的值过多,会导致查询效率下降。
                $decks->each(function (BaccaratTerraceDeck $baccaratTerraceDeck) {

                    if (
                        !$this->exists($baccaratTerraceDeck->id)
                        && $baccaratTerraceDeck->baccaratLotteryLog->isNotEmpty()
                        && Str::length($baccaratTerraceDeck->lottery_sequence ?? '') != $baccaratTerraceDeck->baccaratLotteryLog->count()
                        && $baccaratTerraceDeck->baccaratLotterySequence
                    ){
                        $this->concurrent->create(function () use ($baccaratTerraceDeck) {
                            $this->updateSequence($baccaratTerraceDeck);
                        });
                    }
                });

                unset($decks);
                return true;
            });
    }

    /**
     * @param BaccaratTerraceDeck $baccaratTerraceDeck
     * @return void
     */
    protected function updateSequence(BaccaratTerraceDeck $baccaratTerraceDeck): void
    {
        try {

            $baccaratTerraceDeck->lottery_sequence = $baccaratTerraceDeck->baccaratLotterySequence;
            $baccaratTerraceDeck->save();

            $this->set($baccaratTerraceDeck->id);

            $this->output->info("{$baccaratTerraceDeck->id} update lottery sequence success");

        } catch (\Exception|\Throwable $e) {
            $this->output->error("{$baccaratTerraceDeck->id} update lottery sequence faild {$e->getMessage()}");
            $this->logger->error($e);
        }
    }
}