<?php

namespace App\Baccarat\Database\Factories;

use App\Baccarat\Model\BaccaratSimulatedBetting;
use Hyperf\Database\Model\Factory;

use Faker\Generator as Faker;

/** @var Factory $factory */

$factory->define(BaccaratSimulatedBetting::class, function (Faker $faker) {
    return [
        // 定义模型属性的默认值
        'title' => $faker->name,
        'betting_sequence' => $faker->randomElement(['B', 'P']),
        'status' => $faker->randomElement([1, 2]),
        'sort' => $faker->numberBetween(1, 100),
        'remark' => 'caonima',
    ];
});