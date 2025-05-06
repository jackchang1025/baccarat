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

    public function testGetTransformationResult2()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.4,C.1,H.13,S.2,S.12,S.1');

        $this->assertEquals(LotteryResult::TIE, $lotteryResult->getTransformationResult());
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
        $this->assertEquals(
            ['S.10', 'D.1', 'S.9', 'C.6', 'D.1', 'S.9'],
            $lotteryResult->parseResult()
        );
    }

    public function testGetPlayerHand()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.10,D.1,S.9,C.6');
        $expectedPlayerHand = ['S.10', 'S.9']; // 索引0和2
        $this->assertEquals($expectedPlayerHand, $lotteryResult->getPlayerHand());
    }

    public function testGetBankerHand()
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.10,D.1,S.9,C.6');
        $expectedBankerHand = ['D.1', 'C.6']; // 索引1和3
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

    public function testCheckLotteryResultsTie()
    {
        // Use a result that makes both hands equal: [9, 9, 9, 9] => both sums (18 % 10 = 8)
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.9,D.9,S.9,C.9');
        // For a tie, checkLotteryResults should return BETTING_TIE, regardless of provided betting value.
        $this->assertEquals(LotteryResult::BETTING_TIE, $lotteryResult->checkLotteryResults('P'));
        $this->assertEquals(LotteryResult::BETTING_TIE, $lotteryResult->checkLotteryResults('B'));
    }

    public function testCheckLotteryResultsWin()
    {
        // This instance produces transformation result "P" (PLAYER)
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.10,D.1,S.9,C.6');
        // Betting with PLAYER should win.
        $this->assertEquals(LotteryResult::BETTING_WIN, $lotteryResult->checkLotteryResults(LotteryResult::PLAYER));
    }

    public function testCheckLotteryResultsWin2()
    {
        // This instance produces transformation result "P" (PLAYER)
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'H.3,C.13,H.1,H.7,H.6,');

        // Betting with PLAYER should win.
        $this->assertEquals(LotteryResult::BETTING_LOSE, $lotteryResult->checkLotteryResults(LotteryResult::PLAYER));
        $this->assertEquals(LotteryResult::BETTING_WIN, $lotteryResult->checkLotteryResults(LotteryResult::BANKER));
    }


    public function testCheckLotteryResultsLose()
    {
        // This instance produces transformation result "P" (PLAYER)
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'S.10,D.1,S.9,C.6');
        // Betting with BANKER (which does not match PLAYER) should lose.
        $this->assertEquals(LotteryResult::BETTING_LOSE, $lotteryResult->checkLotteryResults(LotteryResult::BANKER));
    }

    public function testCheckLotteryResultsInvalidResult()
    {
        // Use an invalid result that cannot be parsed; getTransformationResult will return null
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'invalid_result');
        // Regardless of the betting value, without a valid transformation result, it should be a lose
        $this->assertEquals(LotteryResult::BETTING_LOSE, $lotteryResult->checkLotteryResults(LotteryResult::PLAYER));
    }

    public function testCheckLotteryResultsManualTransformationResult()
    {
        // Create an instance with an invalid result so that the computed transformation is null
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', 'invalid');
        // Manually set the transformation result to PLAYER
        $lotteryResult->setTransformationResult(LotteryResult::PLAYER);
        // Betting with PLAYER should win
        $this->assertEquals(LotteryResult::BETTING_WIN, $lotteryResult->checkLotteryResults(LotteryResult::PLAYER));
        // Betting with BANKER should lose
        $this->assertEquals(LotteryResult::BETTING_LOSE, $lotteryResult->checkLotteryResults(LotteryResult::BANKER));
    }

    /**
     * @dataProvider needDrawCardProvider
     */
    public function testNeedDrawCard(string $result, bool $expected, string $caseDescription)
    {
        $lotteryResult = new LotteryResult('test_terrace', 'test_issue', $result);
        $this->assertEquals($expected, $lotteryResult->needDrawCard(), $caseDescription);
    }

    public function needDrawCardProvider(): array
    {
        return [

            ['S.11,H.13,S.13,H.4,S.12,', false, '庄家4点+玩家第三张0不补'],
            ['C.2,D.1,S.1,C.3,H.13,', false, 'C.2,D.1,S.1,C.3,H.13,'],
            
            // 基础天生赢家
            ['S.8,D.9,S.0,C.0', false, '双方天牌'],
            ['S.8,D.9,S.0,,,', true, '牌型不完整'],
            ['S.8,D.9,,,,', true, '牌型不完整'],
            
            // 玩家补牌场景
            ['S.5,D.0,S.0,C.0', true, '玩家5点需补'],  // 玩家5点
            ['S.3,D.0,S.2,C.0', true, '玩家5点需补'],  // 3+2=5
            
            // 庄家特殊补牌规则
            // 庄家3点+闲家第三张8 → 不补
            ['S.3,D.0,S.0,C.3,S.8', false, '庄家3点+闲家第三张8不补'],
            // 庄家3点+闲家第三张7 → 补
            ['S.3,D.0,S.0,C.3,S.7', true, '庄家3点+闲家第三张7补'],
            
            // 庄家4点+不同第三张
            ['S.3,D.0,S.0,C.4,S.8', false, '庄家4点+闲家8不补'],
            ['S.3,D.0,S.0,C.4,S.2', true, '庄家4点+闲家2补'],
            
            // 庄家5点边界
            ['S.3,D.0,S.0,C.5,S.8', false, '庄家5点+闲家8不补'],
            
            // 庄家6点复杂情况
            ['S.3,D.0,S.0,C.6,S.5', false, '庄家6点+闲家5不补'],
            
            // 玩家不补庄家补（庄家3点+玩家初始6点）
            ['S.6,D.3,S.0,C.J', true, '闲家6点+庄家3点需补'],
            
            // 特殊牌型验证
            ['S.J,D.10,C.Q,H.K', true, '双方0点都需要补（J=0,10=0,Q=0,K=0）'],
            ['S.A,D.2,C.3,H.9', true, 'A+2+3+9=5点需补'],

            // 特殊牌型测试（A）
            ['S.A,D.3,C.5,H.Q', true, '玩家A+3=4需补，庄家5点+第三张Q(0)'],
            ['S.A,D.2,S.4,C.6,S.K', false, '庄家6点+第三张K不补'],

            // 10点测试
            ['S.10,D.1,S.10,C.4,S.J', false, '玩家双10需补但已补，庄家5点+J不补'],
            ['S.9,D.8,S.8,C.9', false, '双方天牌（玩家9点+庄家9点）'],
            ['S.4,D.6,S.Q,C.6,S.J', true, '庄家2点需补'],

            // Q/J/K测试
            ['S.Q,D.J,S.2,C.3,S.K', true, '庄家3点+第三张K需补'],

            // 边界值测试
            ['S.3,D.A,C.2,H.10', true, '庄家2点无条件补'],
            ['S.7,D.5,S.K,C.5,S.10', true, '玩家7点不补，庄家5点需补'],


            ['S.2,,S.12,,,', true, 'S.2,,S.12,,,'],
            ['S.2,S.4,S.12,C.9,,', true, 'S.2,S.4,S.12,C.9,,'],
            ['S.2,S.4,S.12,C.9,H.7,D.6', false, 'S.2,S.4,S.12,C.9,H.7,D.6'],


            ['H.11,C.2,C.13,S.11,S.11,', true, 'H.11,C.2,C.13,S.11,S.11,'],
            ['H.11,C.2,C.13,S.11,S.11,H.13', false, 'H.11,C.2,C.13,S.11,S.11,H.13'],
            
            ['S.9,D.5,H.13,D.12,,', false, 'S.9,D.5,H.13,D.12,,'],
            ['S.6,C.5,D.10,C.7,,C.13', false, 'S.6,C.5,D.10,C.7,,C.13'],
            ['C.10,D.11,D.6,D.2,,S.3', false, 'C.10,D.11,D.6,D.2,,S.3'],
            ['C.7,D.2,S.11,H.13,,H.10', false, 'C.7,D.2,S.11,H.13,,H.10'],
            
        ];
    }

    // 测试玩家第三张牌不存在的情况
    public function testBankerDrawWithoutThirdCard()
    {
        // 庄家3点，玩家只有两张牌（无第三张）
        $result = new LotteryResult('test', 'issue', 'S.3,D.3,S.0,C.3'); // 玩家3+0=3点，庄家3+3=6点
        $this->assertTrue($result->needDrawCard(), '庄家3点且玩家无第三张牌时应补牌');
    }

    // 测试各种边界值
    public function testBoundaryConditions()
    {
        // 庄家6点+玩家第三张7（正确用例）
        $case3 = new LotteryResult('t', 'i', 'S.6,D.0,S.7,C.0'); 
        $this->assertTrue($case3->needDrawCard());
    }
}