<?php

namespace HyperfTests\Unit\Baccarat\Mapper;

use App\Baccarat\Mapper\BaccaratTerraceDeckMapper;
use App\Baccarat\Mapper\BaccaratTerraceMapper;
use App\Baccarat\Model\BaccaratTerrace;
use App\Baccarat\Model\BaccaratTerraceDeck;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\DbConnection\Db;
use HyperfTests\Unit\BaseTest;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @group Baccarat
 * @group Mapper
 */
class BaccaratTerraceDeckMapperTest extends BaseTest
{
    protected BaccaratTerraceDeckMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new BaccaratTerraceDeckMapper();
    }

    public function testAssignModel()
    {
        $this->mapper->assignModel();
        $this->assertInstanceOf(BaccaratTerraceDeck::class, $this->mapper->getModel());
    }

    public function testGetLastBaccaratTerraceDeck()
    {
        $baccaratTerrace = $this->factory->of(BaccaratTerrace::class)->create([
            'title' => 'test_title',
            'code' => 'test_code'
        ]);

        $this->factory->of(BaccaratTerraceDeck::class)->create([
            'terrace_id' => $baccaratTerrace->id
        ]);

        $result = $this->mapper->getLastBaccaratTerraceDeck($baccaratTerrace->id);

        $this->assertInstanceOf(BaccaratTerraceDeck::class, $result);
        $this->assertNotNull($result);
        $this->assertEquals($baccaratTerrace->id,$result->terrace_id);
    }

    public function testGetLastBaccaratTerraceDeckOrCreate()
    {
        $terraceId = $this->faker->numberBetween(1,100);
        $result = $this->mapper->getLastBaccaratTerraceDeckOrCreate($terraceId);

        $this->assertInstanceOf(BaccaratTerraceDeck::class, $result);
        $this->assertNotNull($result);
        $this->assertEquals($terraceId,$result->terrace_id);
    }
}