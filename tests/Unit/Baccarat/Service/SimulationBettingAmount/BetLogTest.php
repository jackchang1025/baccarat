<?php

namespace HyperfTests\Unit\Baccarat\Service\SimulationBettingAmount;

use App\Baccarat\Service\BaccaratLotteryLogService;
use App\Baccarat\Service\SimulationBettingAmount\BetLog;
use Hyperf\Collection\Collection;
use HyperfTests\Unit\BaseTest;

class BetLogTest extends BaseTest
{

    protected BetLog $betLog;
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testToArray()
    {
        $collection = new Collection();

        $betLog = new BetLog(
            issue: $this->faker->unixTime,
            sequence: $this->faker->randomElement([0,1]),
            betAmount: $this->faker->randomNumber(),
            totalAmount: $this->faker->randomNumber()
        );

        $collection->push($betLog);

        $this->assertEquals($betLog->toArray(),$collection->pop()->toArray());
    }
}