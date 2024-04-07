<?php

namespace HyperfTests\Unit\Baccarat\Service;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratSimulatedBettingRule;
use App\Baccarat\Model\BaccaratTerrace;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\BaccaratLotteryLogService;
use App\Baccarat\Service\BaccaratService;
use App\Baccarat\Service\BaccaratSimulatedBettingLogService;
use App\Baccarat\Service\BaccaratSimulatedBettingService;
use App\Baccarat\Service\BaccaratTerraceDeckService;
use App\Baccarat\Service\BaccaratTerraceService;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\LotteryResult;
use Hyperf\Database\Model\Collection;
use HyperfTests\Unit\BaseTest;
use Mockery;
use Psr\Log\LoggerInterface;

class BaccaratServiceTest extends BaseTest
{
    protected BaccaratService $baccaratService;

    protected BaccaratTerraceService $baccaratTerraceService;

    protected BaccaratTerraceDeckService $baccaratTerraceDeckService;

    protected BaccaratLotteryLogService $baccaratLotteryLogService;

    protected BaccaratSimulatedBettingLogService $baccaratSimulatedBettingLogService;

    protected BaccaratSimulatedBettingService $baccaratSimulatedBettingService;

    protected LoggerFactory $loggerFactory;

    protected LoggerInterface $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->baccaratTerraceService = Mockery::mock(BaccaratTerraceService::class)->makePartial();
        $this->baccaratTerraceDeckService = Mockery::mock(BaccaratTerraceDeckService::class)->makePartial();
        $this->baccaratLotteryLogService = Mockery::mock(BaccaratLotteryLogService::class)->makePartial();
        $this->baccaratSimulatedBettingLogService = Mockery::mock(BaccaratSimulatedBettingLogService::class)->makePartial();
        $this->baccaratSimulatedBettingService = Mockery::mock(BaccaratSimulatedBettingService::class)->makePartial();
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->loggerFactory = Mockery::mock(LoggerFactory::class)->makePartial();
        $this->loggerFactory->shouldReceive('get')->with('baccarat')->once()->andReturn($this->logger);
        $this->baccaratService = new BaccaratService(
            baccaratTerraceService: $this->baccaratTerraceService,
            baccaratTerraceDeckService: $this->baccaratTerraceDeckService,
            baccaratLotteryLogService: $this->baccaratLotteryLogService,
            baccaratSimulatedBettingLogService: $this->baccaratSimulatedBettingLogService,
            baccaratSimulatedBettingService: $this->baccaratSimulatedBettingService,
            loggerFactory: $this->loggerFactory,
        );
    }

    public function testHandleBettingIsBetting()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = $this->faker->numberBetween(1, 1000);
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->shouldReceive('isBetting')->once()->andReturn(false);
        $result = $this->baccaratService->handleBetting($lotteryResult);
        $this->assertNull($result);
    }

    public function testHandleBettingDeckNumber()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = $this->faker->numberBetween(1, 1000);
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->shouldReceive('isBetting')->once()->andReturn(true);
        $lotteryResult->shouldReceive('getDeckNumber')->times(1)->andReturn(null);
        $result = $this->baccaratService->handleBetting($lotteryResult);
        $this->assertNull($result);
    }

    public function testHandleBettingLotteryLog()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = $this->faker->numberBetween(1, 1000);
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->shouldReceive('isBetting')->once()->andReturn(true);
        $lotteryResult->shouldReceive('getDeckNumber')->times(2)->andReturn('10');
        $lotteryResult->shouldReceive('isLotteryOne')->once()->andReturn(false);
        $lotteryResult->shouldReceive('getLastDeckNumber')->never()->andReturn('9');

        $baccaratTerrace = new BaccaratTerrace(['id' => $this->faker->numberBetween(1, 10000), 'title' => $this->faker->title, 'code' => $this->faker->title]);
        $this->baccaratTerraceService->shouldReceive('getBaccaratTerraceOrCreateByCode')
            ->once()
            ->andReturn($baccaratTerrace);

        $baccaratTerraceDeck = Mockery::mock(BaccaratTerraceDeck::class)->makePartial();
        $baccaratTerraceDeck->shouldReceive('getTransformationResultAttribute')
            ->times(1)
            ->andReturn('');

        $this->baccaratTerraceDeckService->shouldReceive('getBaccaratTerraceDeckWithTodayOrCreate')
            ->once()
            ->andReturn($baccaratTerraceDeck);

        $lotteryLog = new BaccaratLotteryLog([
            'terrace_deck_id' => $baccaratTerraceDeck->id,
            'issue' => $lotteryResult->issue
        ]);
        $this->baccaratLotteryLogService->shouldReceive('getLotteryLog')
            ->once()
            ->andReturn($lotteryLog);

        $this->baccaratLotteryLogService->shouldReceive('createLotteryLog')
            ->never();

        $this->baccaratTerraceDeckService->shouldReceive('updateLotterySequence')->never();

        $result = $this->baccaratService->handleBetting($lotteryResult);
        $this->assertNull($result);
    }

    public function testHandleBettingTransformationResult()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = $this->faker->numberBetween(1, 1000);
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->shouldReceive('isBetting')->once()->andReturn(true);
        $lotteryResult->shouldReceive('getDeckNumber')->times(2)->andReturn('10');
        $lotteryResult->shouldReceive('getLastDeckNumber')->never()->andReturn('9');
        $lotteryResult->shouldReceive('isLotteryOne')->once()->andReturn(false);

        $baccaratTerrace = new BaccaratTerrace(['id' => $this->faker->numberBetween(1, 10000), 'title' => $this->faker->title, 'code' => $this->faker->title]);
        $this->baccaratTerraceService->shouldReceive('getBaccaratTerraceOrCreateByCode')
            ->once()
            ->andReturn($baccaratTerrace);

        $baccaratTerraceDeck = Mockery::mock(BaccaratTerraceDeck::class)->makePartial();
        $baccaratTerraceDeck->shouldReceive('getTransformationResultAttribute')
            ->times(1)
            ->andReturn('BBBBBBBBP');

        $this->baccaratTerraceDeckService->shouldReceive('getBaccaratTerraceDeckWithTodayOrCreate')
            ->once()
            ->andReturn($baccaratTerraceDeck);

        $lotteryLog = new BaccaratLotteryLog([
            'terrace_deck_id' => $baccaratTerraceDeck->id,
            'issue' => $lotteryResult->issue
        ]);
        $this->baccaratLotteryLogService->shouldReceive('getLotteryLog')
            ->once()
            ->andReturn($lotteryLog);

        $this->baccaratLotteryLogService->shouldReceive('createLotteryLog')
            ->never();

        $this->baccaratTerraceDeckService->shouldReceive('updateLotterySequence')->never();

        $this->baccaratSimulatedBettingService->shouldReceive('getBaccaratSimulatedBettingList')
            ->once()
            ->andReturn(new Collection([]));

        $result = $this->baccaratService->handleBetting($lotteryResult);
        $this->assertNull($result);
    }

    public function testHandleBetting()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = $this->faker->numberBetween(1, 1000);
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->shouldReceive('isBetting')->once()->andReturn(true);
        $lotteryResult->shouldReceive('getDeckNumber')->times(2)->andReturn('10');
        $lotteryResult->shouldReceive('getLastDeckNumber')->once()->andReturn('9');
        $lotteryResult->shouldReceive('isLotteryOne')->once()->andReturn(true);

        $baccaratTerrace = new BaccaratTerrace(['id' => $this->faker->numberBetween(1, 10000), 'title' => $this->faker->title, 'code' => $this->faker->title]);
        $this->baccaratTerraceService->shouldReceive('getBaccaratTerraceOrCreateByCode')
            ->once()
            ->andReturn($baccaratTerrace);

        $baccaratTerraceDeck = Mockery::mock(BaccaratTerraceDeck::class)->makePartial();
        $baccaratTerraceDeck->shouldReceive('getTransformationResultAttribute')
            ->times(1)
            ->andReturn('BBBBBBBBP');
        $this->baccaratTerraceDeckService->shouldReceive('getBaccaratTerraceDeckWithTodayOrCreate')
            ->once()
            ->andReturn($baccaratTerraceDeck);

        $lotteryLog = new BaccaratLotteryLog([
            'terrace_deck_id' => $baccaratTerraceDeck->id,
            'issue' => $lotteryResult->issue
        ]);
        $this->baccaratLotteryLogService->shouldReceive('getLotteryLog')
            ->once()
            ->andReturn(null);

        $this->baccaratLotteryLogService->shouldReceive('createLotteryLog')
            ->once()
            ->andReturn($lotteryLog);

        $this->baccaratTerraceDeckService->shouldReceive('updateLotterySequence')->once();


        $simulatedBetting = $this->factory->of(BaccaratSimulatedBetting::class)->times(2)->make();

        $simulatedBetting->each(function (BaccaratSimulatedBetting $item, $key) {

            if ($key === 0) {
                $item->baccaratSimulatedBettingRule = $this->factory->of(BaccaratSimulatedBettingRule::class)->times(2)->make([
                    'rule' => '/B{6}P$/',
                    'betting_value' => 'B',
                ]);
            } else {
                $item->baccaratSimulatedBettingRule = $this->factory->of(BaccaratSimulatedBettingRule::class)->times(2)->make([
                    'rule' => '/P{6}B$/',
                    'betting_value' => 'P',
                ]);
            }
        });

        $this->baccaratSimulatedBettingService->shouldReceive('getBaccaratSimulatedBettingList')
            ->once()
            ->andReturn($simulatedBetting);

        $this->logger->shouldReceive('debug')->times(3);
        $this->logger->shouldReceive('info')->once();

        $expectedBettingValue = 'B';
        $this->baccaratSimulatedBettingLogService->shouldReceive('save')
            ->with(Mockery::on(function ($argument) use ($expectedBettingValue) {
                return $argument['betting_value'] === $expectedBettingValue;
            }))
            ->times(1)
            ->andReturnUsing(function ($argument) {
                return BaccaratSimulatedBettingLog::make($argument);
            });

        $result = $this->baccaratService->handleBetting($lotteryResult);
        $this->assertNotNull($result);
    }

    public function testHandleWaitingFalse()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = $this->faker->numberBetween(1, 1000);
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->shouldReceive('isWaiting')->once()->andReturn(false);
        $result = $this->baccaratService->handleWaiting($lotteryResult);
        $this->assertNull($result);
    }

    public function testHandleWaitingIssueNull()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = $this->faker->numberBetween(1, 1000);
        $lotteryResult->issue = null;
        $lotteryResult->shouldReceive('isWaiting')->once()->andReturn(true);
        $result = $this->baccaratService->handleWaiting($lotteryResult);
        $this->assertNull($result);
    }

    public function testHandleWaitingTransformationResultNull()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = $this->faker->numberBetween(1, 1000);
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->result = $this->faker->title;
        $lotteryResult->shouldReceive('isWaiting')->once()->andReturn(true);
        $lotteryResult->shouldReceive('getTransformationResult')->once()->andReturn(null);
        $result = $this->baccaratService->handleWaiting($lotteryResult);
        $this->assertNull($result);
    }

    public function testHandleWaiting()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = $this->faker->numberBetween(1, 1000);
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->result = $this->faker->title;
        $lotteryResult->data = [];
        $lotteryResult->shouldReceive('isWaiting')->once()->andReturn(true);
        $lotteryResult->shouldReceive('getTransformationResult')->times(3)->andReturn('B');

        $baccaratLotteryLog = Mockery::mock(BaccaratLotteryLog::class)->makePartial();
        $baccaratLotteryLog->shouldReceive('update')
            ->once()
            ->andReturn(true);
        $this->baccaratLotteryLogService->shouldReceive('getLotteryLog')->once()->andReturn($baccaratLotteryLog);


        $baccaratSimulatedBetting = $this->factory->of(BaccaratSimulatedBetting::class)->create([
            'title' => $this->faker->title,
            'status' => 1,
            'betting_sequence' => '0',
        ]);

        $baccaratSimulatedBettingLog = $this->factory->of(BaccaratSimulatedBettingLog::class)->create([
            'issue' => $lotteryResult->issue,
            'betting_value' => 'B',
            'betting_result' => '',
            'baccarat_simulated_betting_id' => $baccaratSimulatedBetting->id,
        ]);

        $this->baccaratSimulatedBettingLogService->shouldReceive('getBaccaratSimulatedBettingLog')
            ->once()
            ->andReturn($baccaratSimulatedBettingLog);

        $result = $this->baccaratService->handleWaiting($lotteryResult);
        $this->assertNotNull($result);
        $this->assertInstanceOf(BaccaratSimulatedBettingLog::class, $result);
        $this->assertEquals('01', $result->baccaratSimulatedBetting->betting_sequence);  // 添加断言
        $this->assertEquals('1', $result->betting_result);  // 添加断言
    }

    public function testPregMatchIsFalse()
    {
        $result = $this->baccaratService->pregMatch('/BBBBBB/','AAAAAA');
        $this->assertEquals(0,$result);
    }

    public function testPregMatchIsTrue()
    {
        $result = $this->baccaratService->pregMatch('/BBBBBB/','BBBBBB');
        $this->assertEquals(1,$result);
    }
}