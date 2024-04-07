<?php

namespace App\Baccarat\Database\Factories;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratTerrace;
use Hyperf\Database\Model\Factory;

use Faker\Generator as Faker;

/** @var Factory $factory */

$factory->define(BaccaratTerrace::class, function (Faker $faker) {

//    ['id', 'code', 'title', 'created_at', 'updated_at', 'deleted_at', 'remark'];

    return [
        // 定义模型属性的默认值
        'title' => $faker->title,
        'code' => $faker->title
    ];
});