<?php

namespace App\Baccarat\Database\Factories;

use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use Hyperf\Database\Model\Factory;

use Faker\Generator as Faker;

/** @var Factory $factory */

$factory->define(BaccaratSimulatedBettingLog::class, function (Faker $faker) {

    //['id', 'baccarat_simulated_betting_id', 'issue', 'betting_value', 'betting_result', 'status', 'remark', 'created_at', 'updated_at'];
    return [
        // 定义模型属性的默认值
        'baccarat_simulated_betting_id' => $faker->numberBetween(1, 100),
        'issue' => $faker->unixTime,
        'betting_value' => $faker->randomElement(['B', 'P']),
        'status' => $faker->randomElement([1, 2]),
        'remark' => 'caonima',
    ];
});