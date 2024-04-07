<?php

namespace HyperfTests\Unit\Baccarat\Service;

use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\BaccaratSimulatedBettingLogService;
use HyperfTests\Unit\BaseTest;

class BaccaratSimulatedBettingLogServiceTest extends BaseTest
{
    private BaccaratSimulatedBettingLogService $baccaratSimulatedBettingLogService;

    public function setUp(): void
    {
        parent::setUp();
        $this->baccaratSimulatedBettingLogService = make(BaccaratSimulatedBettingLogService::class);
    }

    public function testGetBaccaratSimulatedBettingLog()
    {
        $result = $this->baccaratSimulatedBettingLogService->getBaccaratSimulatedBettingLog($this->faker->unixTime);
        $this->assertNull($result);

        $BaccaratSimulatedBettingLog = $this->factory->of(BaccaratSimulatedBettingLog::class)->create();
        $result = $this->baccaratSimulatedBettingLogService->getBaccaratSimulatedBettingLog($BaccaratSimulatedBettingLog->issue);
        $this->assertNotNull($result);
        $this->assertInstanceOf(BaccaratSimulatedBettingLog::class, $result);
    }
}