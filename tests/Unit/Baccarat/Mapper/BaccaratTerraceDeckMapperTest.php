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

}