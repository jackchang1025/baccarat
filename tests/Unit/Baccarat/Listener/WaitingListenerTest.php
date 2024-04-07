<?php

namespace HyperfTests\Unit\Baccarat\Listener;

use App\Baccarat\Event\WaitingEvent;
use App\Baccarat\Listener\WaitingListener;
use App\Baccarat\Model\BaccaratTerrace;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\BaccaratLotteryLogService;
use App\Baccarat\Service\BaccaratService;
use App\Baccarat\Service\BaccaratSimulatedBettingService;
use App\Baccarat\Service\BaccaratTerraceDeckService;
use App\Baccarat\Service\BaccaratTerraceService;
use App\Baccarat\Service\LotteryResult;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Model\Relations\HasMany;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @group Baccarat
 * @group Listener
 */
class WaitingListenerTest extends TestCase
{
    /**
     * @var WaitingListener
     */
    protected $listener;

    /**
     * @var BaccaratSimulatedBettingService|Mockery\MockInterface
     */
    protected $baccaratSimulatedBettingService;

    /**
     * @var BaccaratTerraceService|Mockery\MockInterface
     */
    protected $baccaratTerraceService;

    /**
     * @var BaccaratTerraceDeckService|Mockery\MockInterface
     */
    protected $baccaratTerraceDeckService;

    /**
     * @var BaccaratLotteryLogService|Mockery\MockInterface
     */
    protected $baccaratLotteryLogService;

    /**
     * @var LotteryResult|Mockery\MockInterface
     */
    protected $lotteryResult;

    protected $event;

    protected BaccaratService $baccaratService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baccaratService = Mockery::mock(BaccaratService::class)->makePartial();

        $this->listener = new WaitingListener($this->baccaratService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testProcess()
    {

        $this->baccaratService->shouldReceive('handleWaiting')
            ->once();

        $lotteryResult = Mockery::mock(LotteryResult::class)->makePartial();

        $event = new WaitingEvent($lotteryResult);

        $this->listener->process($event);
    }
}