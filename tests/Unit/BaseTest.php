<?php

namespace HyperfTests\Unit;

use Hyperf\Database\Model\Factory;
use Hyperf\DbConnection\Db;
use PHPUnit\Framework\TestCase;
use Mockery;
use Faker\Generator as Faker;


class BaseTest extends TestCase
{

    protected Factory $factory;

    protected Faker $faker;


    protected function setUp(): void
    {
        parent::setUp();

        $this->beginTransaction();
        $this->faker = \Faker\Factory::create();
        $this->factory = Factory::construct(faker: $this->faker,pathToFactories: database_path('Baccarat'));
    }

    public function beginTransaction(): void
    {
        Db::beginTransaction();
    }

    public function rollback(): void
    {
        Db::rollBack();
    }

    protected function tearDown(): void
    {
        $this->rollback();
        Mockery::close();
        parent::tearDown();
    }
}