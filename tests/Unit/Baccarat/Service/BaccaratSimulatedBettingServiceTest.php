<?php

namespace HyperfTests\Unit\Baccarat\Service;

use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Service\LotteryResult;
use Hyperf\Database\Model\Collection;
use Hyperf\Logger\LoggerFactory;
use HyperfTests\Unit\BaseTest;
use Mockery;
use App\Baccarat\Service\BaccaratSimulatedBettingService;
use App\Baccarat\Mapper\BaccaratSimulatedBettingMapper;
use App\Baccarat\Service\BaccaratSimulatedBettingLogService;
use App\Baccarat\Service\BaccaratLotteryLogService;

/**
 * @group Baccarat
 * @group Service
 */
class BaccaratSimulatedBettingServiceTest extends BaseTest
{
    /**
     * @var BaccaratSimulatedBettingService
     */
    protected $service;

    /**
     * @var BaccaratSimulatedBettingMapper|Mockery\MockInterface
     */
    protected $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new BaccaratSimulatedBettingMapper();
        $this->service = new BaccaratSimulatedBettingService($this->mapper);

    }

    public function testGetBaccaratSimulatedBettingList()
    {
        $this->factory->of(BaccaratSimulatedBetting::class)->times(3)->create([
            'status' => 3
        ]);

        $result = $this->service->getBaccaratSimulatedBettingList(['status' => 3]);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(Collection::class, $result);
    }

//    public function testHandleLotteryBetting()
//    {
//        // 模拟 LotteryResult 对象
//        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
//        $lotteryResult->shouldReceive('isBetting')->once()->andReturn(true);
//        $lotteryResult->issue = 'test_issue';
//
//        // 生成一个 BaccaratSimulatedBetting 实例
//        $simulatedBetting = $this->factory->of(BaccaratSimulatedBetting::class)->times(2)->make();
//
//        $simulatedBetting->each(function (BaccaratSimulatedBetting $item,$key){
//
//            if ($key === 0){
//                $item->baccaratSimulatedBettingRule = $this->factory->of(BaccaratSimulatedBettingRule::class)->times(2)->make([
//                    'rule' => '/B{6}P$/',
//                    'betting_value' => 'B',
//                ]);
//            }else{
//                $item->baccaratSimulatedBettingRule = $this->factory->of(BaccaratSimulatedBettingRule::class)->times(2)->make([
//                    'rule' => '/P{6}B$/',
//                    'betting_value' => 'P',
//                ]);
//            }
//        });
//
//        $this->mapper->shouldReceive('getBaccaratSimulatedBettingList')
//            ->once()
//            ->andReturn($simulatedBetting);
//
//        $baccaratTerraceDeck = Mockery::mock(BaccaratTerraceDeck::class)->makePartial();
//        $baccaratTerraceDeck->shouldReceive('getTransformationResultAttribute')
//            ->times(1)
//            ->andReturn('BBBBBBBBP');
//
//        $this->baccaratLotteryLogService->shouldReceive('getBaccaratTerraceDeck')
//            ->times(1)
//            ->andReturn($baccaratTerraceDeck);
//
//        $expectedBettingValue = 'B';
//
//        $this->baccaratSimulatedBettingLogService->shouldReceive('save')
//            ->with(Mockery::on(function ($argument) use ($expectedBettingValue) {
//                return $argument['betting_value'] === $expectedBettingValue;
//            }))
//            ->times(1)
//            ->andReturnUsing(function ($argument) {
//                return BaccaratSimulatedBettingLog::make($argument);
//            });
//
//        $this->service->handleLotteryBetting($lotteryResult);
//        // 添加断言验证结果
//
//        // 添加断言验证结果
//        $this->mapper->shouldHaveReceived('getBaccaratSimulatedBettingList')->once();
//        $this->baccaratLotteryLogService->shouldHaveReceived('getBaccaratTerraceDeck')->times(1);
//        $this->baccaratSimulatedBettingLogService->shouldHaveReceived('save')
//            ->with(Mockery::on(function ($argument) use ($expectedBettingValue) {
//                return $argument['betting_value'] === $expectedBettingValue;
//            }))
//            ->once();
//
//        $this->assertTrue(true);
//    }
//
//    public function testHandleLotteryWaiting()
//    {
//        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
//        $lotteryResult->shouldReceive('getTransformationResult')
//            ->once()
//            ->andReturn('B');
//        $lotteryResult->issue = 'test_issue';
//
//        $simulatedBettingLog = Mockery::mock(BaccaratSimulatedBettingLog::class)->makePartial();
//        $simulatedBettingLog->issue = $lotteryResult->issue;
//        $simulatedBettingLog->betting_value = 'B';
//        $simulatedBettingLog->shouldReceive('save')
//            ->once()
//            ->andReturnTrue();
//
//
//        $simulatedBetting = Mockery::mock(BaccaratSimulatedBetting::class)->makePartial();
//        $simulatedBetting->betting_sequence = '';
//        $simulatedBetting->shouldReceive('save')
//            ->once()
//            ->andReturnTrue();
//
//        $simulatedBettingLog->setRelation('baccaratSimulatedBetting',$simulatedBetting);
//
//        $this->baccaratSimulatedBettingLogService->shouldReceive('getBaccaratSimulatedBettingLog')
//            ->with('test_issue')
//            ->andReturn($simulatedBettingLog);
//
//        $result = $this->service->handleLotteryWaiting($lotteryResult);
//
//        $this->assertEquals('1', $simulatedBettingLog->betting_result);
//        $this->assertEquals('1', $simulatedBetting->betting_sequence);
//        $this->assertEquals('1', $result->betting_result);
//    }


}
