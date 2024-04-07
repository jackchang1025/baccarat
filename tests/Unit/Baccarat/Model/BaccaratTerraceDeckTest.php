<?php

namespace HyperfTests\Unit\Baccarat\Model;

use App\Baccarat\Model\BaccaratTerraceDeck;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\HasMany;
use HyperfTests\Unit\BaseTest;
use Mockery;

/**
 * @group Baccarat
 * @group Model
 */
class BaccaratTerraceDeckTest extends BaseTest
{

    protected BaccaratTerraceDeck $model;

    public function setUp():void
    {
        parent::setUp();
        $this->model = new BaccaratTerraceDeck();
    }

    public function testGetTransformationResultAttribute()
    {
        $baccaratLotteryLog = new Collection([
            ['transformationResult' => 'B'],
            ['transformationResult' => 'B'],
            ['transformationResult' => 'T'],
            ['transformationResult' => 'P'],
            ['transformationResult' => 'T'],
            ['transformationResult' => 'P'],
        ]);

        $this->model->setRelation('baccaratLotteryLog', $baccaratLotteryLog);

        $this->assertEquals('BBPP', $this->model->getTransformationResultAttribute());
    }

    public function testGetTransformationResultAttributeNull()
    {
        $this->model->setRelation('baccaratLotteryLog', new Collection());

        $this->assertEquals('', $this->model->getTransformationResultAttribute());
    }

    public function testBaccaratLotteryLogRelationship()
    {
        $relation = $this->model->baccaratLotteryLog();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('terrace_deck_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getLocalKeyName());
    }
}