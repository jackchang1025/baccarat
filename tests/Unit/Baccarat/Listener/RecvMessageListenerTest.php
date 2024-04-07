<?php

namespace HyperfTests\Unit\Baccarat\Listener;

use App\Baccarat\Listener\RecvMessageListener;
use Hyperf\Contract\ConfigInterface;
use HyperfTests\Unit\BaseTest;

use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Event\RecvMessageEvent;
use App\Baccarat\Event\WaitingEvent;
use App\Baccarat\Service\LotteryResult;
use Hyperf\Logger\LoggerFactory;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use function Hyperf\Collection\collect;

/**
 * @group Baccarat
 * @group Listener
 */
class RecvMessageListenerTest extends BaseTest
{
    /**
     * @var RecvMessageListener
     */
    protected $listener;

    /**
     * @var ContainerInterface|Mockery\MockInterface
     */
    protected $container;


    /**
     * @var EventDispatcherInterface|Mockery\MockInterface
     */
    protected $eventDispatcher;


    protected function setUp(): void
    {
        parent::setUp();

        $this->container = Mockery::mock(ContainerInterface::class);
        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $this->container->shouldReceive('get')->with(EventDispatcherInterface::class)->andReturn($this->eventDispatcher);

        $this->listener = new RecvMessageListener($this->container, make(LoggerFactory::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testProcessWaitingEvent()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = '3001-80';
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->result = 'test_result';
        $lotteryResult->status = 'waiting';
        $lotteryResult->data = [];
        $lotteryResult->shouldReceive('isBaccarat')->once()->andReturn(true);
        $lotteryResult->shouldReceive('getTransformationResult')->times(2)->andReturn('B');

        $event = new RecvMessageEvent(collect([$lotteryResult]));

        $this->eventDispatcher->shouldReceive('dispatch')->with(Mockery::type(WaitingEvent::class))->once();

        $this->listener->process($event);

        // 添加断言
        $this->eventDispatcher->shouldHaveReceived('dispatch')->with(Mockery::type(WaitingEvent::class))->once();
        $this->assertTrue(true);
    }

    public function testProcessBettingEvent()
    {

        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = '3001-80';
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->result = 'test_result';
        $lotteryResult->status = 'betting';
        $lotteryResult->data = [];
        $lotteryResult->shouldReceive('isBaccarat')->once()->andReturn(true);
        $lotteryResult->shouldReceive('getTransformationResult')->times(2)->andReturn('B');

        $event = new RecvMessageEvent(collect([$lotteryResult]));

        $this->eventDispatcher->shouldReceive('dispatch')->with(Mockery::type(BettingEvent::class))->once();

        $this->listener->process($event);

        // 添加断言
        $this->eventDispatcher->shouldHaveReceived('dispatch')->with(Mockery::type(BettingEvent::class))->once();
        $this->assertTrue(true);
    }

    public function testProcessNonBaccaratEvent()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->shouldReceive('isBaccarat')->andReturn(false);

        $event = new RecvMessageEvent(collect([$lotteryResult]));

        $this->eventDispatcher->shouldNotReceive('dispatch')->times(0);

        $this->listener->process($event);

        // 添加断言
        $this->eventDispatcher->shouldNotHaveReceived('dispatch');
        $this->assertTrue(true);
    }

    public function testProcessNonBaccaratEventNot()
    {
        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();
        $lotteryResult->terrace = '3001-80';
        $lotteryResult->issue = $this->faker->unixTime;
        $lotteryResult->result = 'test_result';
        $lotteryResult->status = '未知';
        $lotteryResult->data = [];
        $lotteryResult->shouldReceive('isBaccarat')->andReturn(true);
        $lotteryResult->shouldReceive('getTransformationResult')->andReturn('B');

        $event = new RecvMessageEvent(collect([$lotteryResult]));

        $this->eventDispatcher->shouldReceive('dispatch')->with(Mockery::type(BettingEvent::class))->never();
        $this->eventDispatcher->shouldReceive('dispatch')->with(Mockery::type(WaitingEvent::class))->never();

        $this->listener->process($event);

        // 添加断言
        $this->eventDispatcher->shouldNotHaveReceived('dispatch');
        $this->assertTrue(true);
    }
}