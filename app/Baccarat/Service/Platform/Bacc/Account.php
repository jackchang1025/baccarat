<?php

namespace App\Baccarat\Service\Platform\Bacc;

use GuzzleHttp\Cookie\CookieJar;

/**
 * 平台账号实体类
 * 封装账号的认证信息和使用状态
 */
class Account
{
    private string $name;
    private CookieJar $cookieJar;
    private int $weight;
    private int $failureCount = 0;
    private bool $isHealthy = true;

    /**
     * @param string $name 账号名称
     * @param array $config 账号配置
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->weight = $config['weight'] ?? 1;
        
        // 初始化 CookieJar
        $this->cookieJar = new CookieJar(false, $this->parseCookies($config['cookies']));
    }

    /**
     * 解析原始 Cookie 数组为 Guzzle 需要的格式
     * @param array $cookies 原始 Cookie 配置
     * @return array 格式化后的 Cookie 数组
     */
    private function parseCookies(array $cookies): array
    {
        return array_map(function (array $cookie) {
            return [
                'Name' => $cookie['Name'],
                'Value' => $cookie['Value'],
                'Domain' => $cookie['Domain'],
                'Path' => $cookie['Path'],
                'Expires' => strtotime($cookie['Expires']),
                'Secure' => $cookie['Secure'],
                'Discard' => $cookie['Discard'],
                'HttpOnly' => $cookie['HttpOnly']
            ];
        }, $cookies);
    }

    /**
     * 获取账号的 CookieJar
     * @return CookieJar Guzzle 的 Cookie 管理对象
     */
    public function getCookieJar(): CookieJar
    {
        return $this->cookieJar;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function markFailure(): void
    {
        $this->failureCount++;
        if ($this->failureCount >= 3) {
            $this->isHealthy = false;
        }
    }

    public function isHealthy(): bool
    {
        return $this->isHealthy;
    }

    public function resetStatus(): void
    {
        $this->failureCount = 0;
        $this->isHealthy = true;
    }

    public function getName(): string
    {
        return $this->name;
    }
} 