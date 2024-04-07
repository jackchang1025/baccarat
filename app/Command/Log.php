<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\BaccaratDealer\BaccaratDealerService;
use App\Baccarat\Service\BaccaratDealer\RandomShuffleDeck;
use App\Baccarat\Service\BaccaratDealer\ShuffleDeck;
use App\Baccarat\Service\BaccaratDealer\ShuffleDeckInterface;
use App\Baccarat\Service\BaccaratDealer\SimulatedMechanicalShuffling;
use App\Baccarat\Service\BaccaratDealer\SimulateManualShuffling;
use App\Baccarat\Service\BaccaratService;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Rule\RuleEngine;
use App\Baccarat\Service\Websocket\ConnectionPool;
use App\Baccarat\Service\Websocket\WebsocketClientFactory;
use App\Baccarat\Service\Websocket\WebSocketManageService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Coroutine\Locker;
use Hyperf\Coroutine\Parallel;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Container\ContainerInterface;
use Hyperf\Coroutine\Coroutine;
use Swoole\Process;
use Swoole\Runtime;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Hyperf\WebSocketClient\ClientFactory;
use Psr\EventDispatcher\EventDispatcherInterface;


#[Command]
class Log extends HyperfCommand
{
    protected SymfonyStyle $style;

    protected RedisProxy $redis;

    public function __construct(
        protected ContainerInterface $container,
        protected ConfigInterface $config,
        protected LoggerFactory $loggerFactory,
        protected ClientFactory $clientFactory,
        protected EventDispatcherInterface $dispatcher,
        protected Output $baccaratOutput,
        protected ConsoleOutput $consoleOutput,
        protected ArgvInput $argvInput,
        protected RedisFactory $redisFactory,
    )
    {
        parent::__construct('log');

        $this->style = new SymfonyStyle($this->argvInput, $this->consoleOutput);

        $this->redis = $this->redisFactory->get('default');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }


    public function handle()
    {
        $s = microtime(true);

         $this->dealHands();

        $this->info("done use s:".number_format(microtime(true) - $s, 8));
    }

    public function dealHands(): void
    {

        $betting = BaccaratSimulatedBetting::with(['baccaratSimulatedBettingRule'])->find(5);

        $ruleEngine = $betting->getRuleEngine();

        $this->info("随机洗牌");
        $this->dealHandsRuleEngine( 50, $ruleEngine, new RandomShuffleDeck());//随机洗牌
        $this->info("随机洗牌");
        $this->dealHandsRuleEngine(50, $ruleEngine, new ShuffleDeck(3));//随机洗牌
        $this->info("模拟机械洗牌");
        $this->dealHandsRuleEngine(50, $ruleEngine, new SimulatedMechanicalShuffling(5));//模拟机械洗牌
        $this->info("模拟手工洗牌");
        $this->dealHandsRuleEngine(50, $ruleEngine, new SimulateManualShuffling());//模拟手工洗牌
    }

    protected function dealHandsRuleEngine(int $decksCount, RuleEngine $ruleEngine,ShuffleDeckInterface $shuffleDeck): void
    {
        $coroutineParallel = new Parallel($decksCount);

        for ($i = 0; $i < $decksCount; $i++) {

            $coroutineParallel->add(function () use ($ruleEngine,$shuffleDeck) {

                $baccaratDealerService = new BaccaratDealerService(decksCount: 8, sleep: 0.005, cutCardPosition: 20, shuffleDeck: $shuffleDeck);
                $baccaratDealerService->run();

                $transformationResults = str_replace(LotteryResult::TIE, '', $baccaratDealerService->getTransformationResults());

                array_reduce(str_split($transformationResults),function (string $carry,string $transformationResults) use ($ruleEngine){
                    $carry .= $transformationResults;

                    if ($result = $ruleEngine->applyRulesOnce($carry)){
                        $this->info($carry);
                        $this->info($result);
                    }

                    return $carry;
                },'');
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
        $coroutineParallel->add(function () use ($s){

            for ($i = 0; $i < 5000000; $i++) {

            }

            $this->info(Coroutine::id()."Coroutine com 5000000 use s:".number_format(microtime(true) - $s, 8));
        });

        $coroutineParallel->add(function () use ($s){

            $this->info(Coroutine::id()."Coroutine s:".number_format(microtime(true) - $s, 8));
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
            if (!$BaccaratSimulatedBettingLog){
                $BaccaratSimulatedBettingLog->create([]);
            }
        });

    }

    public function lock(): void
    {
        $coroutineParallel = new Parallel(10000);

        for ($i = 0; $i < 10000; $i++) {
            $coroutineParallel->add(function (){

                if (Locker::lock("test_locker")){
                    $this->info("get Locker Coroutine id ".Coroutine::id());

                    Coroutine::sleep(0.05);

                    Locker::unlock("test_locker");
                }
            });
        }

        $coroutineParallel->wait();
    }

    public function deleteBettingLog(): void
    {
        $BaccaratSimulatedBetting =  BaccaratSimulatedBetting::with([
            'baccaratSimulatedBettingRule',
            'baccaratSimulatedBettingLog.baccaratBettingRuleLog',
        ])->where('id',5)->first();

        if ($BaccaratSimulatedBetting){

            $bettingRuleTitle = $BaccaratSimulatedBetting->baccaratSimulatedBettingRule->pluck('title')->toArray();

            var_dump($bettingRuleTitle);

            $BaccaratSimulatedBettingNntList = $BaccaratSimulatedBetting->baccaratSimulatedBettingLog->filter(fn (BaccaratSimulatedBettingLog $bettingLog) => $bettingLog->baccaratBettingRuleLog?->title && !in_array($bettingLog->baccaratBettingRuleLog->title, $bettingRuleTitle));

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
            ->whereIn('betting_result',[LotteryResult::BETTING_LOSE,LotteryResult::BETTING_WIN])
            ->selectRaw("id,issue,betting_result,betting_value")
            ->get()
            ->filter(fn(BaccaratSimulatedBettingLog $baccaratSimulatedBettingLog) =>
                $baccaratSimulatedBettingLog->baccaratLotteryLog
                && $baccaratSimulatedBettingLog->baccaratLotteryLog->transformationResult
                && $baccaratSimulatedBettingLog->baccaratLotteryLog->transformationResult === LotteryResult::TIE
            );

        $pall = new Parallel(100);
        foreach ($baccaratSimulatedBettingLogList as $baccaratSimulatedBettingLog){

            $pall->add(function () use ($baccaratSimulatedBettingLog){
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

    public function websocks(){

        // 此行代码后，文件操作，sleep，Mysqli，PDO，streams等都变成异步IO，见'一键协程化'章节。
        Runtime::enableCoroutine();

    
        $token = "bgfc90ebcc02723b72bac331f43088f574bb845772";

        $url = "wss://fx8ec8.3l3b0um9.com/fxLive/fxLB?gameType=h5multi3";

        $websocketClientFactory = new WebsocketClientFactory(
            host:$url,
            token:$token,
            connectionTimeout: 60
        );

        $ConnectionPool = new ConnectionPool($this->container,$websocketClientFactory,[
            'min_connections' => 1,
            'max_connections' => 2,
        ]);

        $baccarat = new WebSocketManageService(
            connectionPool: $ConnectionPool,
            dispatcher: $this->dispatcher,
            loggerFactory: $this->loggerFactory,
            concurrentSize: 10,
            channelSize: 100,
        );

        echo 'start'.PHP_EOL;

        $baccarat->run();

    }

    public function tongBuWriteLog()
    {
        $s = microtime(true);
        
        for ($c = 100; $c--;) {
            $this->log();
            $this->mysql();
        }

        echo 'use ' . (microtime(true) - $s) . ' s'.PHP_EOL;
    }

    public function yibuWriteLog(){

        // 此行代码后，文件操作，sleep，Mysqli，PDO，streams等都变成异步IO，见'一键协程化'章节。
        Runtime::enableCoroutine();


        $s = microtime(true);

        for ($c = 100; $c--;) {
            Coroutine::create(function () use ($c) {

            $this->log();

            $this->mysql();
            });
        }
        
        echo 'use ' . (microtime(true) - $s) . ' s'.PHP_EOL;
    }

    public function log():void
    {
        $lotteryResult = new LotteryResult(
            terrace: '3001-80',
            issue: '2023-01-01 00:00:00',
            result: 'S.10,D.1,S.9,C.6,,',
            status: 'betting',
            rn: '1-1',
            data: ['map' => '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18']
        );

        $logger = $this->loggerFactory->create($lotteryResult->terrace,'baccarat');

        $logger->info("$lotteryResult",$lotteryResult->toArray());
    }

    public function mysql()
    {
        return BaccaratLotteryLog::first();
    }
}
