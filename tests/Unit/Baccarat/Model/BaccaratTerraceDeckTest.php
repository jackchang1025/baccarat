<?php

namespace HyperfTests\Unit\Baccarat\Model;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\LotteryResult;
use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use HyperfTests\Unit\BaseTest;

/**
 * @group Baccarat
 * @group Model
 */
class BaccaratTerraceDeckTest extends BaseTest
{
    public function setUp():void
    {
        parent::setUp();
    }

    public function testGetLabelAttribute()
    {
        $terraceDeck = new BaccaratTerraceDeck(['deck_number'=>'111']);
        $this->assertEquals('111', $terraceDeck->label);

        $terraceDeck = new BaccaratTerraceDeck();
        $this->assertEquals('', $terraceDeck->label);
    }

    public function testGetTitleAttribute()
    {
        $terraceDeck = new BaccaratTerraceDeck(['deck_number'=>'111']);
        $this->assertEquals('111', $terraceDeck->title);

        $terraceDeck = new BaccaratTerraceDeck();
        $this->assertEquals('', $terraceDeck->title);

    }

    /**
     * 测试 getBaccaratLotterySequenceAttribute 方法
     *
     * @return void
     */
    public function testGetBaccaratLotterySequenceAttribute()
    {
        $terraceDeck = new BaccaratTerraceDeck(['created_at' => '2023-06-11']);

        // 模拟关联的 BaccaratLotteryLog 数据
        $lotteryLogs = new Collection([
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::BANKER]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::PLAYER]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::TIE]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::BANKER]),
        ]);

        $terraceDeck->setRelation('baccaratLotteryLog', $lotteryLogs);

        $this->assertEquals('BPB', $terraceDeck->baccaratLotterySequence);
    }


    /**
     * 测试 getBaccaratBettingSequenceAttribute 方法
     *
     * @return void
     */
    public function testGetBaccaratBettingSequenceAttribute()
    {
        $terraceDeck = new BaccaratTerraceDeck();

        // 模拟关联的 BaccaratSimulatedBettingLog 数据
        $bettingLogs = new Collection([
            new BaccaratSimulatedBettingLog(['betting_result' => LotteryResult::BETTING_LOSE]),
            new BaccaratSimulatedBettingLog(['betting_result' => LotteryResult::BETTING_WIN]),
            new BaccaratSimulatedBettingLog(['betting_result' => LotteryResult::BETTING_TIE]),
            new BaccaratSimulatedBettingLog(['betting_result' => LotteryResult::BETTING_LOSE]),
        ]);

        $terraceDeck->setRelation('baccaratSimulatedBettingLog', $bettingLogs);

        $this->assertEquals('010', $terraceDeck->baccaratBettingSequence);
    }
    /**
     * 测试 getLotteryLogCalculateCoordinatesAttribute 方法
     *
     * @return void
     */
    public function testGetLotteryLogCalculateCoordinatesAttribute()
    {
        $terraceDeck = new BaccaratTerraceDeck();

        // 模拟关联的 BaccaratLotteryLog 数据
        $lotteryLogs = new Collection([
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::BANKER]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::PLAYER]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::TIE]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::BANKER]),
            new BaccaratLotteryLog(['transformationResult' => '']),
        ]);

        $terraceDeck->setRelation('baccaratLotteryLog', $lotteryLogs);

        $coordinates = $terraceDeck->lotteryLogCalculateCoordinates;

        $this->assertInstanceOf(Collection::class, $coordinates);
        $this->assertCount(4, $coordinates);
    }

    /**
     * 测试 getLotteryLogBankerCountAttribute 方法
     *
     * @return void
     */
    public function testGetLotteryLogBankerCountAttribute()
    {
        $terraceDeck = new BaccaratTerraceDeck();

        // 模拟关联的 BaccaratLotteryLog 数据
        $lotteryLogs = new Collection([
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::BANKER]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::PLAYER]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::BANKER]),
        ]);

        $terraceDeck->setRelation('baccaratLotteryLog', $lotteryLogs);

        $this->assertEquals(2, $terraceDeck->lotteryLogBankerCount);
    }

    /**
     * 测试 getLotteryLogTieCountAttribute 方法
     *
     * @return void
     */
    public function testGetLotteryLogTieCountAttribute()
    {
        $terraceDeck = new BaccaratTerraceDeck();

        // 模拟关联的 BaccaratLotteryLog 数据
        $lotteryLogs = new Collection([
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::BANKER]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::TIE]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::PLAYER]),
        ]);

        $terraceDeck->setRelation('baccaratLotteryLog', $lotteryLogs);

        $this->assertEquals(1, $terraceDeck->lotteryLogTieCount);
    }

    /**
     * 测试 getLotteryLogPlayerCountAttribute 方法
     *
     * @return void
     */
    public function testGetLotteryLogPlayerCountAttribute()
    {
        $terraceDeck = new BaccaratTerraceDeck();

        // 模拟关联的 BaccaratLotteryLog 数据
        $lotteryLogs = new Collection([
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::BANKER]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::PLAYER]),
            new BaccaratLotteryLog(['transformationResult' => LotteryResult::PLAYER]),
        ]);

        $terraceDeck->setRelation('baccaratLotteryLog', $lotteryLogs);

        $this->assertEquals(2, $terraceDeck->lotteryLogPlayerCount);
    }

    public function testBaccaratLotteryLogRelationship()
    {
        $terraceDeck = new BaccaratTerraceDeck(['created_at' => Carbon::now()]);
        $relation = $terraceDeck->baccaratLotteryLog();

        $this->assertInstanceOf(HasMany::class, $relation);
    }

    public function testBaccaratLotteryLogRelationshipToDay()
    {
        $baccaratLotteryLog = $this->factory->of(BaccaratTerraceDeck::class)->create();

        $issue = $this->faker->numberBetween(1,100);
        $baccaratLotteryLog->baccaratLotteryLog()->create(['issue' => $issue]);

        $lotteryLog = $baccaratLotteryLog->baccaratLotteryLog;
        $this->assertInstanceOf(Collection::class, $lotteryLog);
        $this->assertEquals(1,$lotteryLog->count());
    }

    public function testBaccaratLotteryLogRelationshipNextWeek()
    {
        $baccaratLotteryLog = $this->factory->of(BaccaratTerraceDeck::class)
            ->create([
                'created_at' => Carbon::now()->subWeek()
            ]);

        $issue = $this->faker->numberBetween(1,100);
        $baccaratLotteryLog->baccaratLotteryLog()->create(['issue' => $issue]);

        $lotteryLog = $baccaratLotteryLog->baccaratLotteryLog;
        $this->assertInstanceOf(Collection::class, $lotteryLog);
        $this->assertEquals(1,$lotteryLog->count());
    }

    public function testBaccaratSimulatedBettingLogRelationship()
    {
        $terraceDeck = new BaccaratTerraceDeck();
        $relation = $terraceDeck->baccaratSimulatedBettingLog();

        $this->assertInstanceOf(HasMany::class, $relation);
    }

    public function testBaccaratTerraceRelationship()
    {
        $terraceDeck = new BaccaratTerraceDeck();
        $relation = $terraceDeck->baccaratTerrace();

        $this->assertInstanceOf(BelongsTo::class, $relation);
    }
}