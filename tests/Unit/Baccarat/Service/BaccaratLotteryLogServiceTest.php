<?php

namespace HyperfTests\Unit\Baccarat\Service;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Service\BaccaratLotteryLogService;
use App\Baccarat\Model\BaccaratTerraceDeck;
use HyperfTests\Unit\BaseTest;

class BaccaratLotteryLogServiceTest extends BaseTest
{
    protected BaccaratLotteryLogService $baccaratLotteryLogService;


    public function setUp(): void
    {
        parent::setUp();
        $this->baccaratLotteryLogService = make(BaccaratLotteryLogService::class);
    }


    public function testGetBaccaratTerraceDeck()
    {
        $BaccaratLotteryLog = $this->factory->of(BaccaratLotteryLog::class)->create();

        $result = $this->baccaratLotteryLogService->getLotteryLog($BaccaratLotteryLog->issue);

        $this->assertNotNull($result);
        $this->assertEquals($BaccaratLotteryLog->terrace_deck_id, $result->terrace_deck_id);
    }
}