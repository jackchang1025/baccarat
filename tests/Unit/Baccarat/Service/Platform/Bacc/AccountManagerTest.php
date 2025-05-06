<?php

declare(strict_types=1);

namespace Tests\Unit\Baccarat\Service\Platform\Bacc;

use App\Baccarat\Service\Platform\Bacc\Account;
use App\Baccarat\Service\Platform\Bacc\AccountManager;
use Hyperf\Contract\ConfigInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class AccountManagerTest extends TestCase
{
    private array $mockAccounts = [
        'account1' => [
            'weight' => 1,
            'cookies' => [
                [
                    'Name' => 'test_cookie1',
                    'Value' => 'value1',
                    'Domain' => '.test.com',
                    'Path' => '/',
                    'Expires' => '2025-01-01',
                    'Secure' => true,
                    'Discard' => false,
                    'HttpOnly' => true
                ]
            ]
        ],
        'account2' => [
            'weight' => 1,
            'cookies' => [
                [
                    'Name' => 'test_cookie2',
                    'Value' => 'value2',
                    'Domain' => '.test.com',
                    'Path' => '/',
                    'Expires' => '2025-01-01',
                    'Secure' => true,
                    'Discard' => false,
                    'HttpOnly' => true
                ]
            ]
        ],
        'account3' => [
            'weight' => 1,
            'cookies' => [
                [
                    'Name' => 'test_cookie3',
                    'Value' => 'value3',
                    'Domain' => '.test.com',
                    'Path' => '/',
                    'Expires' => '2025-01-01',
                    'Secure' => true,
                    'Discard' => false,
                    'HttpOnly' => true
                ]
            ]
        ]
    ];

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testBasicRoundRobin()
    {
        // 1. 初始化配置
        $config = Mockery::mock(ConfigInterface::class);
        $config->shouldReceive('get')
            ->with('baccarat.platform.bacc.accounts', [])
            ->andReturn($this->mockAccounts);

        // 2. 创建实例
        $manager = new AccountManager($config);

        // 3. 验证基础轮询顺序
        $this->assertAccountName('account1', $manager->getNextAccount());
        $this->assertAccountName('account2', $manager->getNextAccount());
        $this->assertAccountName('account3', $manager->getNextAccount());
        $this->assertAccountName('account1', $manager->getNextAccount());
    }

    public function testSkipUnhealthyAccounts()
    {
        $config = Mockery::mock(ConfigInterface::class);
        $config->shouldReceive('get')
            ->with('baccarat.platform.bacc.accounts', [])
            ->andReturn($this->mockAccounts);

        $manager = new AccountManager($config);

        // 获取并标记account1失败3次
        $account1 = $manager->getNextAccount();
        for ($i = 0; $i < 3; $i++) {
            $manager->markFailure($account1);
        }

        // 验证跳过account1
        $this->assertAccountName('account2', $manager->getNextAccount());
        $this->assertAccountName('account3', $manager->getNextAccount());
        $this->assertAccountName('account2', $manager->getNextAccount());
    }

    public function testAllAccountsUnhealthy()
    {
        $config = Mockery::mock(ConfigInterface::class);
        $config->shouldReceive('get')
            ->with('baccarat.platform.bacc.accounts', [])
            ->andReturn($this->mockAccounts);

        $manager = new AccountManager($config);

        // 标记所有账号为不健康
        foreach ($this->mockAccounts as $name => $_) {
            $account = $manager->getNextAccount();
            for ($i = 0; $i < 3; $i++) {
                $manager->markFailure($account);
            }
        }

        $this->assertNull($manager->getNextAccount());
    }

    public function testEmptyAccounts()
    {
        $config = Mockery::mock(ConfigInterface::class);
        $config->shouldReceive('get')
            ->with('baccarat.platform.bacc.accounts', [])
            ->andReturn([]);

        $manager = new AccountManager($config);
        $this->assertNull($manager->getNextAccount());
    }

    private function assertAccountName(string $expected, ?Account $actual)
    {
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual->getName());
    }
} 