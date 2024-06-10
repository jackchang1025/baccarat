<?php

namespace HyperfTests\Unit\Baccarat\Service\BaccaratBetting;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\BaccaratBetting\BaccaratBetting;
use App\Baccarat\Service\BaccaratBetting\BaccaratBettingWaitingResult;
use App\Baccarat\Service\Room\RoomManager;
use App\Baccarat\Service\BaccaratSimulatedBettingLogService;
use App\Baccarat\Service\Exception\RoomException;
use App\Baccarat\Service\Exception\RuleMatchingException;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Rule\CustomizeRules;
use Faker\Provider\Base;
use HyperfTests\Unit\BaseTest;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \App\Baccarat\Service\Room\BaccaratBetting
 * @group BaccaratBetting
 */
class BaccaratBettingTest extends BaseTest
{

    protected BaccaratSimulatedBetting $betting;
    protected BaccaratBetting $baccaratBetting;

    protected ContainerInterface $container;
    protected BaccaratSimulatedBettingLogService $bettingLogService;
    protected RoomManager $room;

    protected BaccaratBettingWaitingResult $baccaratBettingWaitingResult;
    public function setUp(): void
    {
        parent::setUp();

        $logger = Mockery::mock(LoggerInterface::class)->makePartial();
        $logger->shouldReceive('info')->andReturn(true);

        $loggerFactory = Mockery::mock(LoggerFactory::class);
        $loggerFactory->shouldReceive('create')->andReturn($logger);

        $this->room = Mockery::mock(RoomManager::class);
        $this->container = Mockery::mock(ContainerInterface::class);
        $this->container->shouldReceive('make')->andReturn($this->room);
        $this->container->shouldReceive('get')->with(LoggerFactory::class)->andReturn($loggerFactory);

        $this->bettingLogService = Mockery::mock(BaccaratSimulatedBettingLogService::class);
        $output = Mockery::mock(Output::class);
        $this->betting = Mockery::mock(BaccaratSimulatedBetting::class)->makePartial();
        $this->betting->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $this->baccaratBetting = make(BaccaratBetting::class, [
            'container' => $this->container,
            'bettingLogService' =>  $this->bettingLogService,
            'betting' => $this->betting,
            'output' => $output,
        ]);

        $lotteryResult = new LotteryResult($this->faker->unixTime,$this->faker->randomNumber());
        $baccaratLotteryLog = new BaccaratLotteryLog();
        $deck = new BaccaratTerraceDeck(['id' => $this->faker->uuid,'baccaratLotterySequence' => 'BBBBPPPPBBBB']);
        $deck->id = 1;

        $this->baccaratBettingWaitingResult = new BaccaratBettingWaitingResult($lotteryResult, $baccaratLotteryLog,$deck);

    }

    public function testPlaceBetWithMatchingRule()
    {
        $rule = Mockery::mock(CustomizeRules::class);

        $bettingLog = new BaccaratSimulatedBettingLog();

        $this->betting->shouldReceive('getRuleEngine->applyRulesOnce')
            ->with($this->baccaratBettingWaitingResult->getDeck()->baccaratBettingSequence)
            ->andReturn($rule);

        $this->room->shouldReceive('isCurrentRoomConsistent')->andReturn(true);
        $this->room->shouldReceive('exitRoom')->never();
        $this->room->shouldReceive('getRoom')->andReturn('1');
        $this->room->shouldReceive('checkRoom')->with('1')->andReturn(true);
        $this->room->shouldReceive('joinRoom')->never();
        $this->room->shouldReceive('extendExpiration')->once()->andReturn(true);
        $this->bettingLogService->shouldReceive('getBaccaratSimulatedBettingLogOrCreate')
            ->with($this->baccaratBettingWaitingResult->getLotteryResult()->issue, $this->betting->id,$this->baccaratBettingWaitingResult->getDeck()->id, $rule)
            ->andReturn($bettingLog);

        $result = $this->baccaratBetting->placeBet($this->baccaratBettingWaitingResult );

        $this->assertSame($bettingLog, $result);
    }

    public function testPlaceBetWithNoMatchingRule()
    {

        $this->betting->shouldReceive('getRuleEngine->applyRulesOnce')
            ->with($this->baccaratBettingWaitingResult->getDeck()->baccaratBettingSequence)
            ->andReturn(null);

        $this->room->shouldReceive('isCurrentRoomConsistent')->once()->andReturn(true);
        $this->room->shouldReceive('exitRoom')->once();

        $this->expectException(RuleMatchingException::class);
        $this->expectExceptionMessage('No matching rule found for the given lottery sequence.');

        $this->baccaratBetting->placeBet($this->baccaratBettingWaitingResult );
    }

    public function testPlaceBetWithRoomDataNotMatching()
    {
        $rule = Mockery::mock(CustomizeRules::class);

        $this->betting->shouldReceive('getRuleEngine->applyRulesOnce')
            ->with($this->baccaratBettingWaitingResult->getDeck()->baccaratBettingSequence)
            ->andReturn($rule);
        $this->room->shouldReceive('getRoom')->andReturn('2');
        $this->room->shouldReceive('checkRoom')->with('2')->andReturn(false);


        $this->expectException(RoomException::class);
        $this->expectExceptionMessage('Room data does not match the current lottery result.');

        $this->baccaratBetting->placeBet($this->baccaratBettingWaitingResult );
    }

    public function testPlaceBetWithRoomJoinFailed()
    {

        $rule = Mockery::mock(CustomizeRules::class);

        $this->betting->shouldReceive('getRuleEngine->applyRulesOnce')
            ->with($this->baccaratBettingWaitingResult->getDeck()->baccaratBettingSequence)
            ->andReturn($rule);
        $this->room->shouldReceive('checkRoom')->andReturn(false);
        $this->room->shouldReceive('getRoom')->andReturn(null);
        $this->room->shouldReceive('joinRoom')->andReturn(false);

        $this->expectException(RoomException::class);
        $this->expectExceptionMessage('Room Join Failed.');

        $this->baccaratBetting->placeBet($this->baccaratBettingWaitingResult );
    }
}