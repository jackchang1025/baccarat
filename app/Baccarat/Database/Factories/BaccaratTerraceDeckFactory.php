<?php

namespace App\Baccarat\Database\Factories;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratTerrace;
use App\Baccarat\Model\BaccaratTerraceDeck;
use Carbon\Carbon;
use Hyperf\Database\Model\Factory;

use Faker\Generator as Faker;

/** @var Factory $factory */

$factory->define(BaccaratTerraceDeck::class, function (Faker $faker) {

//    ['id', 'terrace_id', 'deck_number', 'lottery_sequence', 'created_at', 'updated_at', 'remark'];

    return [
        // 定义模型属性的默认值
        'terrace_id' => $faker->numberBetween(1,100),
        'deck_number' => $faker->unixTime,
        'lottery_sequence' => $faker->randomElement(['B','P']),
        'created_at' => Carbon::now(),
    ];
});