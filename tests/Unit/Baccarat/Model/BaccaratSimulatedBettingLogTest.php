<?php

namespace HyperfTests\Unit\Baccarat\Model;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\LotteryResult;
use Hyperf\Database\Model\Relations\HasOne;
use HyperfTests\Unit\BaseTest;

/**
 * @Groups BaccaratSimulatedBettingLog
 * @Groups Model
 *
 */
class BaccaratSimulatedBettingLogTest extends BaseTest
{
    public function testBaccaratLotteryLog()
    {
        try {
            $bettingLog = BaccaratSimulatedBettingLog::findOrFail(798664);
        } catch (\Exception $e) {
            $this->fail("Failed to fetch betting log with ID 798664 {$e->getMessage()}");
        }

        $this->assertInstanceOf(HasOne::class, $bettingLog->baccaratLotteryLog());
        $this->assertInstanceOf(BaccaratLotteryLog::class,$bettingLog->baccaratLotteryLog);

        if ($bettingLog->betting_value && $bettingLog->baccaratLotteryLog->transformationResult){
            $result = $bettingLog->baccaratLotteryLog->getLotteryResult()->checkLotteryResults($bettingLog->betting_value);

            $this->assertContains($result,[LotteryResult::BETTING_WIN, LotteryResult::BETTING_LOSE,LotteryResult::BETTING_TIE]);
        }

    }

    public function testSetBettingResultAttributeException()
    {
        $bettingLog = BaccaratSimulatedBettingLog::make();

        $this->expectException(\InvalidArgumentException::class);
        $bettingLog->setBettingResultAttribute('aaa');

    }

    public function testSetBettingResultAttribute()
    {
        $bettingLog = BaccaratSimulatedBettingLog::make();

        $bettingLog->setBettingResultAttribute(LotteryResult::BETTING_LOSE);
        $this->assertEquals(LotteryResult::BETTING_LOSE,$bettingLog->betting_result);

        $bettingLog->setBettingResultAttribute(LotteryResult::BETTING_WIN);
        $this->assertEquals(LotteryResult::BETTING_WIN,$bettingLog->betting_result);

        $bettingLog->setBettingResultAttribute(LotteryResult::BETTING_TIE);
        $this->assertEquals(LotteryResult::BETTING_TIE,$bettingLog->betting_result);

    }
}