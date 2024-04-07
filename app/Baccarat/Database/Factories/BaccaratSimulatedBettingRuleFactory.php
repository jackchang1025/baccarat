<?php

namespace App\Baccarat\Database\Factories;

use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingRule;
use Hyperf\Database\Model\Factory;

use Faker\Generator as Faker;

/** @var Factory $factory */

$factory->define(BaccaratSimulatedBettingRule::class, function (Faker $faker) {

    //['id', 'created_at', 'updated_at', 'title', 'rule', 'betting_value', 'status', 'sort', 'remark'];
    return [
        // 定义模型属性的默认值
        'title' => $faker->name,
        'rule' => $faker->realText(10),
        'betting_value' => $faker->randomElement([1, 2]),
        'status' => $faker->randomElement([1, 2]),
        'sort' => $faker->numberBetween(1, 100),
        'remark' => 'caonima',
    ];
});