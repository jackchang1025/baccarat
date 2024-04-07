<?php

namespace HyperfTests\Unit\Baccarat;

use App\Baccarat\Service\LotteryResult;
use PHPUnit\Framework\TestCase;

/**
 * @group baccarat
 */
class LotteryResultTest extends TestCase
{
    public function testToArray()
    {
        $lotteryResult = new LotteryResult(terrace: 'test_terrace', issue: 'test_issue', result: 'test_result', status: 'test_status', rn:'test_rn',data:['test_data']);

        $expectedArray = [
            'terrace' => 'test_terrace',
            'issue' => 'test_issue',
            'result' => 'test_result',
            'status' => 'test_status',
            'rn' => 'test_rn',
        ];

        $this->assertEquals($expectedArray, $lotteryResult->toArray());
    }

    public function testGetTransformationResult()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.10,D.1,S.9,C.6');

        $this->assertEquals(LotteryResult::PLAYER, $lotteryResult->getTransformationResult());
    }

    public function testGetTransformationResultWithInvalidResult()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'invalid_result');

        $this->assertNull($lotteryResult->getTransformationResult());
    }

    public function testParseResultWithNull()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', ',,,,,,');
        $this->assertNull($lotteryResult->parseResult());
    }

    public function testParseResultCountLessThan3()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.10,D.1,S.9,,,');
        $this->assertNull($lotteryResult->parseResult());
    }

    public function testParseResult()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.10,D.1,S.9,C.6,D.1,S.9');
        $this->assertEquals([
            10, 1, 9, 6, 1, 9
        ],$lotteryResult->parseResult());
    }

    public function testGetPlayerHand()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.10,D.1,S.9,C.6');

        $expectedPlayerHand = [10, 9];

        $this->assertEquals($expectedPlayerHand, $lotteryResult->getPlayerHand());
    }

    public function testGetBankerHand()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.10,D.1,S.9,C.6');

        $expectedBankerHand = [1, 6];

        $this->assertEquals($expectedBankerHand, $lotteryResult->getBankerHand());
    }

    public function testIsBaccarat()
    {
        $lotteryResult = new LotteryResult('30471');

        $this->assertTrue($lotteryResult->isBaccarat());
    }

    public function testIsNotBaccarat()
    {
        $lotteryResult = new LotteryResult('invalid_terrace');

        $this->assertFalse($lotteryResult->isBaccarat());
    }

    public function testIsBetting()
    {
        $lotteryResult = new LotteryResult(terrace:'test_terrace',status: 'aaaaa');
        $this->assertFalse($lotteryResult->isBetting());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',status: 'betting');
        $this->assertTrue($lotteryResult->isBetting());
    }

    public function testIsWaiting()
    {
        $lotteryResult = new LotteryResult(terrace:'test_terrace',status: 'aaaaa');
        $this->assertFalse($lotteryResult->isWaiting());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',status: 'waiting');
        $this->assertTrue($lotteryResult->isWaiting());
    }

    public function testGetDeckNumber()
    {
        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: '');
        $this->assertNull($lotteryResult->getDeckNumber());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: 'aaaa-bb');
        $this->assertNull($lotteryResult->getDeckNumber());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: 'bb10-25');
        $this->assertEquals('10',$lotteryResult->getDeckNumber());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: '25-25');
        $this->assertEquals('25',$lotteryResult->getDeckNumber());
    }

    public function testGetLastDeckNumber()
    {
        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: null);
        $this->assertNull($lotteryResult->getLastDeckNumber());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: 'bb10-25');
        $this->assertEquals('9',$lotteryResult->getLastDeckNumber());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: '50-25');
        $this->assertEquals('49',$lotteryResult->getLastDeckNumber());
    }

    public function testIsLotteryOne()
    {
        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: '');
        $this->assertFalse($lotteryResult->isLotteryOne());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: 'aaaa-bb');
        $this->assertFalse($lotteryResult->isLotteryOne());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: 'bb10-1');
        $this->assertTrue($lotteryResult->isLotteryOne());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: '1-1');
        $this->assertTrue($lotteryResult->isLotteryOne());
    }

    public function testGetNumber()
    {
        $lotteryResult = new LotteryResult(terrace:'test_terrace');
        $this->assertNull($lotteryResult->getNumber());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: 'aaaa-aa');
        $this->assertNull($lotteryResult->getNumber());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: 'bb10-25');
        $this->assertEquals('25',$lotteryResult->getNumber());

        $lotteryResult = new LotteryResult(terrace:'test_terrace',rn: '56-55');
        $this->assertEquals('55',$lotteryResult->getNumber());
    }
}