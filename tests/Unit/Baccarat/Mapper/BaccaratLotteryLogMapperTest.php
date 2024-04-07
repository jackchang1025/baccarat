<?php

namespace HyperfTests\Unit\Baccarat\Mapper;

use App\Baccarat\Mapper\BaccaratLotteryLogMapper;
use App\Baccarat\Model\BaccaratLotteryLog;
use HyperfTests\Unit\BaseTest;

/**
 * @group Baccarat
 * @group Mapper
 */
class BaccaratLotteryLogMapperTest extends BaseTest
{
    protected BaccaratLotteryLogMapper $mapper;

    public function setUp():void
    {
        parent::setUp();

        $this->mapper = new BaccaratLotteryLogMapper();
    }

    public function testGetLotteryLog()
    {
        $baccaratLotteryLog = $this->factory->of(BaccaratLotteryLog::class)->create();
        $result = $this->mapper->getLotteryLog($baccaratLotteryLog->issue);

        $this->assertNotNull($result);
        $this->assertInstanceOf(BaccaratLotteryLog::class, $result);
    }

    public function testGetLotteryLogNull()
    {
        $result = $this->mapper->getLotteryLog($this->faker->unixTime);
        $this->assertNull($result);
    }
}