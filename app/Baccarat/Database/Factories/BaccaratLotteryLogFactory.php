<?php

namespace App\Baccarat\Database\Factories;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use Hyperf\Database\Model\Factory;

use Faker\Generator as Faker;

/** @var Factory $factory */

$factory->define(BaccaratLotteryLog::class, function (Faker $faker) {

    //['id', 'terrace_deck_id', 'issue','result','transformationResult','RawData', 'created_at', 'updated_at', 'deleted_at', 'remark'];

    return [
        // 定义模型属性的默认值
        'terrace_deck_id' => $faker->numberBetween(1,100),
        'issue' => $faker->unixTime,
        'transformationResult' => $faker->randomElement(['B', 'P']),
        'RawData' => []
    ];
});