<?php

namespace HyperfTests\Unit\Baccarat\Service;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\BaccaratTerraceDeckService;
use Carbon\Carbon;
use HyperfTests\Unit\BaseTest;
use function Hyperf\Support\today;

class BaccaratTerraceDeckServiceTest extends BaseTest
{
    protected BaccaratTerraceDeckService $baccaratTerraceDeckService;


    public function setUp(): void
    {
        parent::setUp();
        $this->baccaratLotteryLogService = make(BaccaratTerraceDeckService::class);
    }

    public function testGetBaccaratTerraceDeckWithToday()
    {
        $baccaratTerraceDeck = $this->factory->of(BaccaratTerraceDeck::class)->create();
        $result = $this->baccaratLotteryLogService->getBaccaratTerraceDeckWithToday($baccaratTerraceDeck->terrace_id,$baccaratTerraceDeck->deck_number);
        $this->assertNotNull($result);
        $this->assertEquals($baccaratTerraceDeck->deck_number,$result->deck_number);
        $this->assertEquals($baccaratTerraceDeck->terrace_id,$result->terrace_id);

        $baccaratTerraceDeck = $this->factory->of(BaccaratTerraceDeck::class)->create([
            'created_at' => Carbon::yesterday(),
        ]);
        $result = $this->baccaratLotteryLogService->getBaccaratTerraceDeckWithToday($baccaratTerraceDeck->terrace_id,$baccaratTerraceDeck->deck_number);
        $this->assertNull($result);
    }

    public function testGetBaccaratTerraceDeckWithTodayOrCreate()
    {
        $terraceId = $this->faker->numberBetween(1,100);
        $deckNumber = $this->faker->unixTime;

        $result = $this->baccaratLotteryLogService->getBaccaratTerraceDeckWithTodayOrCreate($terraceId,$deckNumber);
        $this->assertNotNull($result);
        $this->assertTrue($result->wasRecentlyCreated);


        $result = $this->baccaratLotteryLogService->getBaccaratTerraceDeckWithTodayOrCreate($terraceId,$deckNumber);
        $this->assertNotNull($result);
        $this->assertFalse($result->wasRecentlyCreated);
    }

    public function testUpdateLotterySequenceIsNull()
    {
        $baccaratTerraceDeck = $this->baccaratLotteryLogService->updateLotterySequence($this->faker->numberBetween(1,100),$this->faker->unixTime);
        $this->assertNull($baccaratTerraceDeck);
    }

    public function testUpdateLotterySequenceTransformationResultIsNull()
    {
        $baccaratTerraceDeck = $this->factory->of(BaccaratTerraceDeck::class)->create([
            'terrace_id' => $this->faker->numberBetween(1,100),
            'deck_number' => $this->faker->unixTime,
            'lottery_sequence' => '',
        ]);

        $result = $this->baccaratLotteryLogService->updateLotterySequence($baccaratTerraceDeck->terrace_id,$baccaratTerraceDeck->deck_number);
        $this->assertNotNull($result);
        $this->assertEquals($baccaratTerraceDeck->terrace_id,$result->terrace_id);
        $this->assertEquals('',$result->lottery_sequence);
    }

    public function testUpdateLotterySequence()
    {
        $baccaratTerraceDeck = $this->factory->of(BaccaratTerraceDeck::class)->create([
            'terrace_id' => $this->faker->numberBetween(1,100),
            'deck_number' => $this->faker->unixTime,
        ]);

        $this->factory->of(BaccaratLotteryLog::class)->times(3)->create([
            'terrace_deck_id' => $baccaratTerraceDeck->id,
            'transformationResult' => 'B',
        ]);

        $result = $this->baccaratLotteryLogService->updateLotterySequence($baccaratTerraceDeck->terrace_id,$baccaratTerraceDeck->deck_number);
        $this->assertNotNull($result);
        $this->assertEquals('BBB',$result->lottery_sequence);
    }
}