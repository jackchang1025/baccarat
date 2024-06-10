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

    public function testGetTransformationResults()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.13,D.13,S.8,C.6');
        $this->assertEquals(LotteryResult::PLAYER, $lotteryResult->getTransformationResult());

        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'C.11,S.10,D.12,D.8,,');
        $this->assertEquals(LotteryResult::BANKER, $lotteryResult->getTransformationResult());

        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'C.3,D.11,S.11,H.2,S.9,D.13');
        $this->assertEquals(LotteryResult::TIE, $lotteryResult->getTransformationResult());
    }

    public function testCalculatePoints()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'H.11,D.13,C.2,H.1,D.10,C.13');

        $this->assertEquals(2, $lotteryResult->calculatePoints([11,2,10]));
        $this->assertEquals(1, $lotteryResult->calculatePoints([13,1,13]));
    }

    public function testGetTerraceName()
    {
        $lotteryResult = new LotteryResult('3001-80', 'test_issue', 'H.11,D.13,C.2,H.1,D.10,C.13');
        $this->assertEquals('百家乐mx4', $lotteryResult->getTerraceName());

        $lotteryResult = new LotteryResult('999-999', 'test_issue', 'H.11,D.13,C.2,H.1,D.10,C.13');
        $this->assertNull($lotteryResult->getTerraceName() );
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

    public function testCheckLotteryResults()
    {
        $lotteryResult = new LotteryResult(terrace: 'test_terrace', issue: 'test_issue', result: 'D.9,C.1,S.6,D.9,C.2,S.1', status: 'waiting', rn: 'bb10-25');
        $this->assertEquals(LotteryResult::BETTING_WIN, $lotteryResult->checkLotteryResults(LotteryResult::PLAYER));
        $this->assertEquals(LotteryResult::BETTING_LOSE, $lotteryResult->checkLotteryResults(LotteryResult::BANKER));

        $lotteryResult = new LotteryResult(terrace: 'test_terrace', issue: 'test_issue', result: 'H.5,H.7,S.11,S.8,H.8,', status: 'waiting', rn: 'bb10-25');
        $this->assertEquals(LotteryResult::BETTING_WIN, $lotteryResult->checkLotteryResults(LotteryResult::BANKER));
        $this->assertEquals(LotteryResult::BETTING_LOSE, $lotteryResult->checkLotteryResults(LotteryResult::PLAYER));

        $lotteryResult = new LotteryResult(terrace: 'test_terrace', issue: 'test_issue', result: 'H.9,C.7,S.5,H.10,C.3,', status: 'waiting', rn: 'bb10-25');
        $this->assertEquals(LotteryResult::BETTING_TIE, $lotteryResult->checkLotteryResults(LotteryResult::BANKER));
        $this->assertEquals(LotteryResult::BETTING_TIE, $lotteryResult->checkLotteryResults(LotteryResult::PLAYER));
    }
}