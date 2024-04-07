<?php

namespace HyperfTests\Unit\Baccarat\Mapper;

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
class BaccaratTerraceMapperTest extends BaseTest
{
    protected BaccaratTerraceMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new BaccaratTerraceMapper();
    }

    public function testAssignModel()
    {
        $this->mapper->assignModel();
        $this->assertInstanceOf(BaccaratTerrace::class, $this->mapper->getModel());
    }

    public function testGetBaccaratTerraceOrCreateByCode()
    {
        $terraceCode = 'test_code';

        $result = $this->mapper->getBaccaratTerraceOrCreateByCode($terraceCode);

        $this->assertInstanceOf(BaccaratTerrace::class, $result);
        $this->assertNotNull($result);
        $this->assertEquals($terraceCode,$result->title);
        $this->assertEquals($terraceCode,$result->code);
    }
}