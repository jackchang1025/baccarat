<?php

namespace HyperfTests\Unit\Baccarat\Service\Platform\Bacc;

use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Platform\Bacc\Response;
use HyperfTests\Unit\BaseTest;

class ResponseTest extends BaseTest
{
    public function testConstructorWithValidMessage(): void
    {
        $message = 'BANKER CONFIDENCE: almost';
        $response = new Response($message);
        $this->assertEquals($message, $response->getMessage());
        $this->assertEquals(Response::BANKER, $response->getBets());
        $this->assertEquals(Response::ALMOST, $response->getCredibility());

        $message = 'PLAYER CONFIDENCE: medium';
        $response = new Response($message);
        $this->assertEquals($message, $response->getMessage());
        $this->assertEquals(Response::PLAYER, $response->getBets());
        $this->assertEquals(Response::MIDDLE, $response->getCredibility());


        $message = 'PLAYER CONFIDENCE: almost';
        $response = new Response($message);
        $this->assertEquals($message, $response->getMessage());
        $this->assertEquals(Response::PLAYER, $response->getBets());
        $this->assertEquals(Response::ALMOST, $response->getCredibility());
    }

    public function testConstructorWithInvalidMessage(): void
    {
        $message = 'BANKER';
        $this->expectException(\InvalidArgumentException::class);
        new Response($message);
    }

    public function testConstructorWithInvalidBets(): void
    {
        $message = 'a  aaa cc';
        $this->expectException(\InvalidArgumentException::class);
        new Response($message);
    }

    public function testConstructorWithInvalidCredibility(): void
    {
        $message = 'bbb gg gg';
        $this->expectException(\InvalidArgumentException::class);
        new Response($message);
    }

    public function testConvertBets(): void
    {
        $response = new Response('BANKER CONFIDENCE: almost');
        $this->assertEquals(LotteryResult::BANKER, $response->convertBets());

        $response = new Response('PLAYER CONFIDENCE: medium');
        $this->assertEquals(LotteryResult::PLAYER, $response->convertBets());
    }

    public function testIsBanker(): void
    {
        $response = new Response('BANKER CONFIDENCE: almost');
        $this->assertTrue($response->isBanker());

        $response = new Response('PLAYER CONFIDENCE: medium');
        $this->assertFalse($response->isBanker());
    }

    public function testIsPlayer(): void
    {
        $response = new Response('PLAYER CONFIDENCE: medium');
        $this->assertTrue($response->isPlayer());

        $response = new Response('BANKER CONFIDENCE: almost');
        $this->assertFalse($response->isPlayer());
    }

    public function testIsAlmost(): void
    {
        $response = new Response('BANKER CONFIDENCE: almost');
        $this->assertTrue($response->isAlmost());

        $response = new Response('BANKER CONFIDENCE: high');
        $this->assertFalse($response->isAlmost());
    }

    public function testIsHigh(): void
    {
        $response = new Response('BANKER CONFIDENCE: high');
        $this->assertTrue($response->isHigh());

        $response = new Response('BANKER CONFIDENCE: almost');
        $this->assertFalse($response->isHigh());
    }

    public function testIsMiddle(): void
    {
        $response = new Response('BANKER CONFIDENCE: medium');
        $this->assertTrue($response->isMiddle());

        $response = new Response('BANKER CONFIDENCE: almost');
        $this->assertFalse($response->isMiddle());
    }
}