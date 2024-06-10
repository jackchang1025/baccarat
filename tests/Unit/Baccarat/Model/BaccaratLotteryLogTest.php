<?php

namespace HyperfTests\Unit\Baccarat\Model;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Service\LotteryResult;
use HyperfTests\Unit\BaseTest;

class BaccaratLotteryLogTest extends BaseTest
{
    public function testGetLotteryResult()
    {
        $data = [
            'id' => 6,
            'terrace_deck_id' => 51716,
            'issue' => 523107436,
            'result' => 'H.9,C.7,S.5,H.10,C.3,',
            'transformationResult' => '',
            'RawData' => [
                'pk' => 'H.9,C.7,S.5,H.10,C.3,',
                'rs' => 523107436,
                'st' => 'waiting',
                'map' => "25-7,77-6,9-5,85-8,53-7,53-7,73-6,85-8,73-4,29-4,89-8,77-6,73-6,91-8,91-8,5-9,53-7,73-4,41-8,61-8,77-6,85-8,89-8,53-5,5-7,89-8,49-8,17-9,85-8,41-8,29-6,9-9,77-4,49-6,37-9,9-7,65-9,85-8,29-6,9-7",
                'banker_card' => []
                ]
        ];
        $lotteryLog = BaccaratLotteryLog::make($data);

        $lotteryResult = $lotteryLog->getLotteryResult();

        $this->assertInstanceOf(LotteryResult::class,$lotteryResult);
        $this->assertNotNull($lotteryResult);
        $this->assertEquals(LotteryResult::TIE,$lotteryResult->getTransformationResult());
    }

    public function testSetTransformationResultAttributeException()
    {
        $lotteryLog = BaccaratLotteryLog::make();
        $this->expectException(\InvalidArgumentException::class);
        $lotteryLog->setTransformationResultAttribute('test');
    }

    public function testSetTransformationResultAttribute()
    {
        $lotteryLog = BaccaratLotteryLog::make();
        $lotteryLog->setTransformationResultAttribute(LotteryResult::BANKER);
        $this->assertEquals(LotteryResult::BANKER,$lotteryLog->transformationResult);

        $lotteryLog->setTransformationResultAttribute(LotteryResult::PLAYER);
        $this->assertEquals(LotteryResult::PLAYER,$lotteryLog->transformationResult);

        $lotteryLog->setTransformationResultAttribute(LotteryResult::TIE);
        $this->assertEquals(LotteryResult::TIE,$lotteryLog->transformationResult);

    }

}