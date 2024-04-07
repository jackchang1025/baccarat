<?php

namespace HyperfTests\Unit\Baccarat\Mapper;

use App\Baccarat\Mapper\BaccaratLotteryLogMapper;
use App\Baccarat\Mapper\BaccaratSimulatedBettingMapper;
use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use Hyperf\Database\Model\Collection;
use HyperfTests\Unit\BaseTest;

/**
 * @group Baccarat
 * @group Mapper
 */
class BaccaratSimulatedBettingMapperTest extends BaseTest
{
    protected BaccaratSimulatedBettingMapper $mapper;

    public function setUp():void
    {
        parent::setUp();

        $this->mapper = new BaccaratSimulatedBettingMapper();
    }

    public function testGetBaccaratSimulatedBettingList()
    {
        $this->factory->of(BaccaratSimulatedBetting::class)->times(2)->create([
            'status' => 1,
        ]);

        $result = $this->mapper->getBaccaratSimulatedBettingList();

        $this->assertNotNull($result);
        $this->assertInstanceOf(Collection::class, $result);
    }

    public function testGetBaccaratSimulatedBettingListNull()
    {
        $this->factory->of(BaccaratSimulatedBetting::class)->times(2)->create([
            'status' => 2,
        ]);

        $result = $this->mapper->getBaccaratSimulatedBettingList(['status'=>2]);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Collection::class, $result);
    }
}