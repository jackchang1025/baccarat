<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Mapper\BaccaratSimulatedBettingLogMapper;
use App\Baccarat\Mapper\BaccaratWaitingSequenceMapper;
use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratLotteryLogBack;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\BaccaratDealer\BaccaratDealerService;
use App\Baccarat\Service\BaccaratDealer\RandomShuffleDeck;
use App\Baccarat\Service\BaccaratDealer\ShuffleDeck;
use App\Baccarat\Service\BaccaratDealer\ShuffleDeckInterface;
use App\Baccarat\Service\BaccaratDealer\SimulatedMechanicalShuffling;
use App\Baccarat\Service\BaccaratDealer\SimulateManualShuffling;
use App\Baccarat\Crontab\Database\CreateBaccaratLotteryLogTableJob;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Platform\Bacc\Bacc;
use App\Baccarat\Service\Room\Room;
use App\Baccarat\Service\Room\RoomManager;
use App\Baccarat\Service\Room\RoomMapper;
use App\Baccarat\Service\Rule\RuleEngine;
use App\Baccarat\Service\Websocket\Connection;
use App\Baccarat\Service\Websocket\MessageDecoder\MessageDecoder;
use App\Baccarat\Service\Websocket\Middleware\MiddlewareManager;
use Carbon\Carbon;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Coroutine\Parallel;
use Hyperf\Database\Model\Collection;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Container\ContainerInterface;
use Hyperf\Coroutine\Coroutine;
use Swoole\Process;
use Swoole\Runtime;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Hyperf\WebSocketClient\ClientFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notifier;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;


#[Command]
class Test extends HyperfCommand
{
    protected SymfonyStyle $style;

    protected RedisProxy $redis;

    protected Connection $connection;

    protected static array $channels = [];

    public function __construct(
        protected ContainerInterface                $container,
        protected ConfigInterface                   $config,
        protected LoggerFactory                     $loggerFactory,
        protected ClientFactory                     $clientFactory,
        protected EventDispatcherInterface          $dispatcher,
        protected Output                            $baccaratOutput,
        protected ConsoleOutput                     $consoleOutput,
        protected ArgvInput                         $argvInput,
        protected RedisFactory                      $redisFactory,
        protected Serializer                        $serializer,
        protected Notifier                          $notifier,
        protected Bacc                              $bacc,
        protected BaccaratSimulatedBettingLogMapper $bettingLogMapper,
        protected BaccaratWaitingSequenceMapper     $waitingSequenceMapper,
        protected MiddlewareManager     $middlewareManager,

    )
    {
        parent::__construct('test');

        $this->style = new SymfonyStyle($this->argvInput, $this->consoleOutput);

        $this->redis = $this->redisFactory->get('baccarat');

    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');

        $this->addOption('sleep', 'S', InputOption::VALUE_REQUIRED, 'sleep');
    }


    public function handle()
    {
        Runtime::enableCoroutine(true);

        try {
            $s = microtime(true);
            $this->bacc();

        } catch (\Exception|\Throwable $e) {

            var_dump($e);
        }

        $this->info("done use s:" . number_format(microtime(true) - $s, 8));
    }

    public function middlewareManager(): void{

        $data = $this->middlewareManager->handle('WebsocketRecvMessage','aaaaaa');

        var_dump($data);
    }

    public function bacc(): void
    {
//
//        $response = $this->bacc->calculate([1, 1, 0, 1, 1, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0]);
//
//        var_dump($response->convertBets(), $response->getCredibility());
//
//        $response = $this->bettingLogMapper->getModel()
//            ->where('betting_id', 9999)
//            ->where('remark', 'high')
//            ->whereIn('betting_result', [0, 1])
//            ->chunk(500, function (Collection $baccaratSimulatedBettingLogs) {
//                $response = $baccaratSimulatedBettingLogs->pluck('betting_result')
//                    ->implode('');
//
//                $this->waitingSequenceMapper->getModel()->create([
//                    'title'    => "9999_high_" . Carbon::now()->toDateString(),
//                    'sequence' => $response
//                ]);
//                var_dump($response);
//            });

        $maxCount = 0;

        $response = $this->bettingLogMapper->getModel()
            ->where('betting_id', 6666)
            ->where('remark', 'high')
            ->selectRaw('terrace_deck_id')
            ->whereNotIn('betting_result',[2])
            ->groupBy('terrace_deck_id')
            ->get();


        $sequences =  $response->reduce(function (string $sequences,BaccaratSimulatedBettingLog $baccaratSimulatedBettingLogs) use(&$maxCount){

            $response = $this->bettingLogMapper->getModel()
                ->where('remark', 'high')
                ->where('betting_id', 6666)
                ->whereIn('betting_result', [0, 1])
                ->where('terrace_deck_id', $baccaratSimulatedBettingLogs->terrace_deck_id)
                ->get();

            $sequence = $response->pluck('betting_result')
                ->implode('');

            $this->info("{$baccaratSimulatedBettingLogs->terrace_deck_id} : $sequence");

            if (!empty(trim($sequence))){
                $consecutiveOccurrences = $this->consecutiveOccurrences($sequence, '0');

                $maxCount = max($consecutiveOccurrences, $maxCount);
                $sequences .= $sequence;
            }

            return $sequences;

//                $this->waitingSequenceMapper->getModel()->firstOrCreate([
//                    'title'    => "10000_high_100" . Carbon::now()->toDateString(),],
//                    ['sequence' => $sequences
//                    ]
//                );


            },'');

        var_dump($maxCount);
    }

    function consecutiveOccurrences(string $string, string $char): int
    {
        // 使用正则表达式查找连续出现的字符
        preg_match_all('/(' . preg_quote($char) . '+)/', $string, $matches);

        $maxCount = 0;

        // 遍历匹配结果，找到最大连续出现次数
        foreach ($matches[0] as $match) {
            $count = strlen($match);
            if ($count > $maxCount) {
                $maxCount = $count;
            }
        }
        // 遍历分割后的子串，找到最长的 '0' 子串长度
        return $maxCount;
    }

    function maxConsecutiveOccurrences(string $string, string $char,int $maxCount = 0): int
    {
        return max($this->consecutiveOccurrences($string, $char), $maxCount);
    }

    public function createBaccaratLotteryLogTable(): void
    {
        $createBaccaratLotteryLogTable = make(CreateBaccaratLotteryLogTableJob::class);
        $createBaccaratLotteryLogTable->execute();
    }

    public function baccaratLotteryLog(): void
    {
        $baccaratLotteryLog = new BaccaratLotteryLog();
        $this->info($baccaratLotteryLog->getTable());

        $baccaratLotteryLogback = BaccaratLotteryLogBack::find(456144);
        $baccaratLotteryLog = new BaccaratLotteryLog();
        $baccaratLotteryLog->getShardingModel($baccaratLotteryLogback->created_at);
        $this->info($baccaratLotteryLogback->created_at);
        $this->info($baccaratLotteryLog->getTable());
    }

    public function baccaratTerraceDeck()
    {
        var_dump(1);
        $BaccaratTerraceDeck = BaccaratTerraceDeck::find(10882);

        var_dump(2);
        var_dump($BaccaratTerraceDeck->created_at?->toDateString());

        var_dump($BaccaratTerraceDeck->baccaratLotteryLog->toArray());
        var_dump($BaccaratTerraceDeck->baccaratLotteryLog->toArray());
    }

    public function notify()
    {
        $notification = new Notification(Carbon::now() . ' :测试通知', ['wechat_work']);

        $this->notifier->send($notification);
    }

    public function gameStrategy()
    {
        $first_bet = 20;
        $total_bet = 0;
        $bet = $first_bet;

        $this->info("和");
        for ($i = 1; $i <= 30; $i++) {
            $total_bet += $bet;
            $winAmount = $bet * 8;
            $this->info("第{$i}局投注金额: {$bet} 元  总金额:{$total_bet} 元 盈利{$winAmount} 元");
            $bet = round($bet * 1.5);
        }

        $this->info("对");
        $first_bet = 20;
        $total_bet = 0;
        $bet = $first_bet;

        for ($i = 1; $i <= 30; $i++) {
            $total_bet += $bet;
            $winAmount = $bet * 11;
            $this->info("第{$i}局投注金额: {$bet} 元 总金额:{$total_bet} 元 盈利{$winAmount} 元");
            $bet = round($bet * 1.1);
        }

        $this->info("对");
        $first_bet = 20;
        $total_bet = 0;
        $bet = $first_bet;

        for ($i = 1; $i <= 30; $i++) {
            $total_bet += $bet;
            $winAmount = $bet * 11;
            $this->info("第{$i}局投注金额: {$bet} 元 总金额:{$total_bet} 元 盈利{$winAmount} 元");
            $bet = round($bet * 1.2);
        }


        $this->info("对");
        $first_bet = 20;
        $total_bet = 0;
        $bet = $first_bet;

        for ($i = 1; $i <= 30; $i++) {
            $total_bet += $bet;
            $winAmount = $bet * 11;
            $this->info("第{$i}局投注金额: {$bet} 元 总金额:{$total_bet} 元 盈利{$winAmount} 元");
            $bet = round($bet * 1.3);
        }


        $this->info("对");
        $first_bet = 20;
        $total_bet = 0;
        $bet = $first_bet;

        for ($i = 1; $i <= 30; $i++) {
            $total_bet += $bet;
            $winAmount = $bet * 11;
            $this->info("第{$i}局投注金额: {$bet} 元 总金额:{$total_bet} 元 盈利{$winAmount} 元");
            $bet = round($bet * 1.4);
        }

        $this->info("对");
        $first_bet = 20;
        $total_bet = 0;
        $bet = $first_bet;

        for ($i = 1; $i <= 30; $i++) {
            $total_bet += $bet;
            $winAmount = $bet * 11;
            $this->info("第{$i}局投注金额: {$bet} 元 总金额:{$total_bet} 元 盈利{$winAmount} 元");
            $bet = round($bet * 1.5);
        }
    }

    public function redis()
    {
    }

    public function serializer()
    {


        $data = $this->redis->hGet('room', '4');
        var_dump($data);

        $serializer = make(SerializerInterface::class);

        /**
         * @var Room $room
         */
        $room = $serializer->deserialize($data, Room::class, 'json');

        var_dump($room->isRoomExpired(), time() - $room->getCreateTime() >= $room->getSeconds(),);


        $RoomManager = make(RoomManager::class, [
            'mapper'    => make(RoomMapper::class),
            'bettingId' => "5",
            'terraceId' => '3001-5888',
            'deckId'    => '1234565'
        ]);

        $room = $RoomManager->getRoom();
        var_dump($room, $room->isRoomExpired());

        return;


        $serializer = make(SerializerInterface::class);

        $data = new Room(bettingId: 'bettingId', terraceId: 'terraceId', deckId: 'deckId', createTime: time());

        var_dump($json = $serializer->serialize($data, 'json'));
        var_dump($xml = $serializer->serialize($data, 'xml'));

        sleep(3);

        var_dump(
            $serializer->deserialize($json, Room::class, 'json'),
        );
    }


    public function Connection()
    {

        $connection = make(Connection::class, [
            'messageDecoder'    => make(MessageDecoder::class),
            'host'              => $this->config->get('websocket.host'),
            'connectionTimeout' => $this->config->get('websocket.connectionTimeout'),
            'remainingTimeOut'  => $this->config->get('websocket.remainingTimeOut'),
        ]);

        $connection->login();

        $sleep = (float)$this->input->getOption('sleep');


        if ($sleep) {
            Coroutine::sleep($sleep);
        }


        while (true) {


            $message = $connection->retryRecvMessage();

            if ($message->isOnUpdateGameInfo()) {

                $message->transformationLotteryResult()->each(function (LotteryResult $lotteryResult) use ($connection) {

                    if ($lotteryResult->terrace == '3001-80') {
                        $this->output->info(Carbon::now() . " class " . spl_object_id($connection) . " {$lotteryResult}");
                    }
                });
            }


        }
    }

    public function websocketLogin()
    {
        $this->connection = make(Connection::class, [
            'messageDecoder'    => make(MessageDecoder::class),
            'host'              => $this->config->get('websocket.host'),
            'connectionTimeout' => $this->config->get('websocket.connectionTimeout'),
            'remainingTimeOut'  => $this->config->get('websocket.remainingTimeOut'),
        ]);

        var_dump($this->connection->isLoggedIn());

        $this->connection->login();

        var_dump($this->connection->isLoggedIn());

        $message = $this->connection->retryRecvMessage();

        $this->info($message->toJson());
    }

    public function dealHands(): void
    {

        $betting = BaccaratSimulatedBetting::with(['baccaratSimulatedBettingRule'])->find(5);

        $ruleEngine = $betting->getRuleEngine();

        $this->info("随机洗牌");
        $this->dealHandsRuleEngine(50, $ruleEngine, new RandomShuffleDeck());//随机洗牌
        $this->info("随机洗牌");
        $this->dealHandsRuleEngine(50, $ruleEngine, new ShuffleDeck(3));//随机洗牌
        $this->info("模拟机械洗牌");
        $this->dealHandsRuleEngine(50, $ruleEngine, new SimulatedMechanicalShuffling(5));//模拟机械洗牌
        $this->info("模拟手工洗牌");
        $this->dealHandsRuleEngine(50, $ruleEngine, new SimulateManualShuffling());//模拟手工洗牌
    }

    protected function dealHandsRuleEngine(int $decksCount, RuleEngine $ruleEngine, ShuffleDeckInterface $shuffleDeck): void
    {
        $coroutineParallel = new Parallel($decksCount);

        for ($i = 0; $i < $decksCount; $i++) {

            $coroutineParallel->add(function () use ($ruleEngine, $shuffleDeck) {

                $baccaratDealerService = new BaccaratDealerService(decksCount: 8, sleep: 0.005, cutCardPosition: 20, shuffleDeck: $shuffleDeck);
                $baccaratDealerService->run();

                $transformationResults = str_replace(LotteryResult::TIE, '', $baccaratDealerService->getTransformationResults());

                array_reduce(str_split($transformationResults), function (string $carry, string $transformationResults) use ($ruleEngine) {
                    $carry .= $transformationResults;

                    if ($result = $ruleEngine->applyRulesOnce($carry)) {
                        $this->info($carry);
                        $this->info($result);
                    }

                    return $carry;
                }, '');
            });
        }
        $coroutineParallel->wait();
    }

    public function process()
    {
        for ($n = 1; $n <= 3; $n++) {
            $process = new Process(function () use ($n) {
                echo 'Child #' . getmypid() . " start and sleep {$n}s" . PHP_EOL;
                sleep($n);
                echo 'Child #' . getmypid() . ' exit' . PHP_EOL;
            });
            $process->start();
        }
        for ($n = 3; $n--;) {
            $status = Process::wait(true);
            echo "Recycled #{$status['pid']}, code={$status['code']}, signal={$status['signal']}" . PHP_EOL;
        }
        echo 'Parent #' . getmypid() . ' exit' . PHP_EOL;

    }

    public function coroutine()
    {
        $s = microtime(true);
        $coroutineParallel = new Parallel(3);
        $coroutineParallel->add(function () use ($s) {

            for ($i = 0; $i < 5000000; $i++) {

            }

            $this->info(Coroutine::id() . "Coroutine com 5000000 use s:" . number_format(microtime(true) - $s, 8));
        });

        $coroutineParallel->add(function () use ($s) {

            $this->info(Coroutine::id() . "Coroutine s:" . number_format(microtime(true) - $s, 8));
        });

        $coroutineParallel->wait();
    }

    public function match()
    {
        $preg = '/B{6,}(?=P{0,5}$)P{0,5}/';

        $str = 'BBBBBBBBBBPPPPPPPPP';

        var_dump(preg_match($preg, $str));


        $str = 'BBBBBBPPPPPP';

        var_dump(preg_match($preg, $str));
    }

    public function BaccaratService()
    {
        $BaccaratService1 = make(BaccaratService::class);
        $BaccaratService2 = make(BaccaratService::class);

        var_dump($BaccaratService1 === $BaccaratService2);
    }

    public function database()
    {
        $coroutineParallel = new Parallel(10000);
        $coroutineParallel->add(function () {

            $BaccaratSimulatedBettingLog = BaccaratSimulatedBettingLog::query()->where('id', 1)->first();
            if (!$BaccaratSimulatedBettingLog) {
                $BaccaratSimulatedBettingLog->create([]);
            }
        });

    }

    public static function locker(string $key, mixed $value = true): bool
    {
        if (!isset(static::$channels[$key])) {
            static::$channels[$key] = $value;
            return true;
        }
        return false;
    }

    public static function unlock(string $key): void
    {
        if (isset(static::$channels[$key])) {
            unset(static::$channels[$key]);
        }
    }

    public function lock(): void
    {
        $coroutineParallel = new Parallel(10000);


        for ($i = 0; $i < 10000; $i++) {
            $coroutineParallel->add(function () {

                if (self::locker("test_locker")) {
                    $this->info("get Locker Coroutine id " . Coroutine::id());

                    Coroutine::sleep(0.1);

                    self::unlock("test_locker");
                }
            });
        }

        $coroutineParallel->wait();
    }

    public function deleteBettingLog(): void
    {
        $BaccaratSimulatedBetting = BaccaratSimulatedBetting::with([
            'baccaratSimulatedBettingRule',
            'baccaratSimulatedBettingLog.baccaratBettingRuleLog',
        ])->where('id', 5)->first();

        if ($BaccaratSimulatedBetting) {

            $bettingRuleTitle = $BaccaratSimulatedBetting->baccaratSimulatedBettingRule->pluck('title')->toArray();

            var_dump($bettingRuleTitle);

            $BaccaratSimulatedBettingNntList = $BaccaratSimulatedBetting->baccaratSimulatedBettingLog->filter(fn(BaccaratSimulatedBettingLog $bettingLog) => $bettingLog->baccaratBettingRuleLog?->title && !in_array($bettingLog->baccaratBettingRuleLog->title, $bettingRuleTitle));

            $pall = new Parallel(50);

            $BaccaratSimulatedBettingNntList->each(function (BaccaratSimulatedBettingLog $bettingLog) use ($pall) {
                $pall->add(function () use ($bettingLog) {
                    $bettingLog->delete();
                });
            });

            $pall->wait();

            var_dump($BaccaratSimulatedBettingNntList->count());
        }

    }

    public function updateBettingResult(): void
    {
        $baccaratSimulatedBettingLogList = BaccaratSimulatedBettingLog::with(['baccaratLotteryLog:id,issue,transformationResult'])
            ->whereNotNull('betting_result')
            ->whereNotNull('betting_value')
            ->whereIn('betting_result', [LotteryResult::BETTING_LOSE, LotteryResult::BETTING_WIN])
            ->selectRaw("id,issue,betting_result,betting_value")
            ->get()
            ->filter(fn(BaccaratSimulatedBettingLog $baccaratSimulatedBettingLog) => $baccaratSimulatedBettingLog->baccaratLotteryLog
                && $baccaratSimulatedBettingLog->baccaratLotteryLog->transformationResult
                && $baccaratSimulatedBettingLog->baccaratLotteryLog->transformationResult === LotteryResult::TIE
            );

        $pall = new Parallel(100);
        foreach ($baccaratSimulatedBettingLogList as $baccaratSimulatedBettingLog) {

            $pall->add(function () use ($baccaratSimulatedBettingLog) {
                $baccaratSimulatedBettingLog->betting_result = LotteryResult::BETTING_TIE;
                $baccaratSimulatedBettingLog->save();

                $this->info("update betting result for issue:{$baccaratSimulatedBettingLog->issue}");
            });
        }
        $pall->wait();
        $this->info("update betting result count:{$baccaratSimulatedBettingLogList->count()}");

    }


    public function symfonyStyle(): void
    {
        $this->style->writeln('Hyperf Demo Command');
//
        $this->style->note('done');
    }

    public function baccaratOutput(): void
    {
        $this->baccaratOutput->info('done');
    }

    public function tongBuWriteLog()
    {
        $s = microtime(true);

        for ($c = 100; $c--;) {
            $this->log();
            $this->mysql();
        }

        echo 'use ' . (microtime(true) - $s) . ' s' . PHP_EOL;
    }

    public function yibuWriteLog()
    {

        // 此行代码后，文件操作，sleep，Mysqli，PDO，streams等都变成异步IO，见'一键协程化'章节。
        Runtime::enableCoroutine();


        $s = microtime(true);

        for ($c = 100; $c--;) {
            Coroutine::create(function () use ($c) {

                $this->log();

                $this->mysql();
            });
        }

        echo 'use ' . (microtime(true) - $s) . ' s' . PHP_EOL;
    }

    public function log(): void
    {
        $lotteryResult = new LotteryResult(
            terrace: '99999',
            issue: '2023-01-01 00:00:00',
            result: 'S.10,D.1,S.9,C.6,,',
            status: 'betting',
            rn: '1-1',
            data: ['map' => '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18']
        );

        try {

            throw new \Exception((string)$lotteryResult);
        } catch (\Exception $e) {

            $this->container->get(LoggerFactory::class)->create()->error($e->getMessage());
            $this->container->get(LoggerFactory::class)->create()->error($e);
            $this->container->get(LoggerFactory::class)->create()->error('aaaa', ['exception' => $e]);
        }
    }

    public function mysql()
    {
        return BaccaratLotteryLog::first();
    }
}
