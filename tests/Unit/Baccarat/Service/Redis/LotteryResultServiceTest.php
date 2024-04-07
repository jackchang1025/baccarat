<?php

namespace HyperfTests\Unit\Baccarat\Service\Redis;

use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\Redis\LotteryResultService;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use HyperfTests\Unit\BaseTest;
use PHPUnit\Framework\TestCase;

class LotteryResultServiceTest extends BaseTest
{

    protected LotteryResultService $lotteryResultService;
    protected RedisProxy $redis;

    protected function setUp(): void
    {
        parent::setUp();

        // 在测试开始前获取 Redis 连接
        $redisFactory = make(RedisFactory::class);
        $this->redis = $redisFactory->get('default');

        // 初始化 LotteryResultService
        $this->lotteryResultService = make(LotteryResultService::class);
    }


    public function testRedisConnection()
    {
        // 测试 Redis 连接是否可用
        $ping = $this->redis->ping();
        $this->assertEquals('PONG', $ping);
    }

    public function testGetFormat()
    {
        $this->assertEquals('test_terrace:2023-06-10', $this->lotteryResultService->getFormat('test_terrace', '2023-06-10'));
    }


    public function testHsetnx()
    {
        // 准备测试数据
        $terraceId = $this->faker->text(10);
        $date = '2023-06-10';
        $shoesId = '1';

        // 调用 hsetnx 方法保存数据到 Redis
        $result = $this->lotteryResultService->hSetnx($terraceId, $date, $shoesId, BaccaratTerraceDeck::make(['id'=>1]));

        $this->assertTrue($result);

        // 验证数据是否成功保存到 Redis
        $savedData = $this->lotteryResultService->hGet($terraceId, $date, $shoesId);
        $this->assertNotNull($savedData);
        $this->assertInstanceOf(BaccaratTerraceDeck::class,$savedData);

        //清除 redis 测试数据
        $this->redis->del($this->lotteryResultService->getFormat($terraceId, $date));
    }


    public function testHsetnxSaveArray()
    {
        // 准备测试数据
        $terraceId =  $this->faker->text(10);
        $date = '2023-06-10';
        $shoesId = '1';

        // 调用 hsetnx 方法保存数据到 Redis
        $result = $this->lotteryResultService->hSetnx($terraceId, $date, $shoesId, ['id'=>1]);
        $this->assertTrue($result);

        // 验证数据是否成功保存到 Redis
        $savedData = $this->lotteryResultService->hGet($terraceId, $date, $shoesId);
        $this->assertNotNull($savedData);
        $this->assertIsArray($savedData);

        // 尝试获取不存在的数据
        $nonExistingData = $this->lotteryResultService->hGet($terraceId, $date,'non_existing_shoes_id');
        $this->assertNull($nonExistingData);

        //清除 redis 测试数据
        $this->redis->del($this->lotteryResultService->getFormat($terraceId, $date));
    }


    public function testHasLotteryResults()
    {
        // 准备测试数据
        $terraceId =  $this->faker->text(10);
        $date = '2023-06-10';
        $shoesId = '1';

        // 保存测试数据到 Redis
        $this->lotteryResultService->hSetnx($terraceId, $date, $shoesId, ['id'=>1]);

        // 调用 has 方法检查数据是否存在
        $exists = $this->lotteryResultService->hExists($terraceId, $date, $shoesId);
        $this->assertTrue($exists);

        // 检查不存在的数据
        $notExists = $this->lotteryResultService->hExists($terraceId, $date, 'non_existing_shoes_id');
        $this->assertFalse($notExists);

        //清除 redis 测试数据
        $this->redis->del($this->lotteryResultService->getFormat($terraceId, $date));
    }


    public function testIterateLotteryResults()
    {
        // 准备测试数据
        $terraceId = '225882183';
        $date = '2023-06-10';
        $lotteryData = [
            '1' => ['result' => 'foo'],
            '2' => ['result' => 'bar'],
            '3' => ['result' => 'baz'],
        ];

        // 保存测试数据到 Redis
        foreach ($lotteryData as $shoesId => $data) {
            $this->lotteryResultService->hSetnx($terraceId, $date, $shoesId, $data);
        }

        $result = $this->lotteryResultService->hGet($terraceId, $date,'1');
        $this->assertNotNull($result);


        // 调用 iterateLotteryResults 方法迭代数据
        $iteratedData = [];
        $this->lotteryResultService->iterateLotteryResults($terraceId, $date, function ($tId, $d, $sId, $data) use (&$iteratedData) {
            $iteratedData[$sId] = $data;
        });

        // 验证迭代结果是否与原始数据一致
        $this->assertEquals($lotteryData, $iteratedData);
        //清除 redis 测试数据
        $this->redis->del($this->lotteryResultService->getFormat($terraceId, $date));
    }

    public function testDeleteExpiredLotteryResults()
    {
        // 准备测试数据
        $terraceId =  (string) $this->faker->unixTime();
        $date = '2023-06-10';
        $lotteryData = [
            '1' => ['result' => 'foo'],
            '2' => ['result' => 'bar'],
            '3' => ['result' => 'baz'],
        ];

        // 保存测试数据到 Redis
        foreach ($lotteryData as $shoesId => $data) {
            $this->lotteryResultService->hSetnx($terraceId, $date, $shoesId, $data);
        }

        // 调用 deleteExpiredLotteryResults 方法删除过期数据
        $this->lotteryResultService->deleteExpiredLotteryResults($terraceId, $date);

        // 验证数据是否被成功删除
        foreach ($lotteryData as $shoesId => $data) {
            $exists = $this->lotteryResultService->hExists($terraceId, $date, $shoesId);
            $this->assertFalse($exists);
        }
    }
}
