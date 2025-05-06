<?php

namespace App\Baccarat\Service\Platform\Bacc;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Hyperf\Coroutine\Coroutine;
use App\Baccarat\Service\Platform\Bacc\AccountManager;
use App\Baccarat\Service\Platform\Bacc\ClientPool;

/**
 * 平台客户端核心类
 * 处理所有与 Bacc 平台的 HTTP 通信
 */
class Client
{
    private const MAX_RETRIES = 10;         // 最大重试次数
    private const RETRY_DELAY = 1;      // 重试延迟
    private const MAX_CONCURRENT = 100; // 最大并发数

    /**
     * @param ClientPool $clientPool HTTP 客户端连接池
     * @param AccountManager $accountManager 账号管理对象
     * @param LoggerInterface $logger 日志记录器
     */
    public function __construct(
        private ClientPool $clientPool,
        private AccountManager $accountManager,
        private LoggerInterface $logger
    ) {}

    public function dashboard()
    {
        return $this->sendRequest('get', '/dashboard');
    }

    /**
     * 发送 HTTP 请求（带重试和账号切换机制）
     * @param string $method HTTP 方法
     * @param string $uri 请求路径
     * @param array $options 请求选项
     * @return ResponseInterface HTTP 响应对象
     * @throws \RuntimeException 无可用账号时抛出
     * @throws GuzzleException 请求失败时抛出
     */
    private function sendRequest(string $method, string $uri, array $options = []): ResponseInterface
    {
        $attempts = 0;

        while ($attempts < self::MAX_RETRIES) {

        
            try {

                $account = $this->accountManager->getNextAccount();
                if (!$account) {
                    $this->logger->emergency('No available accounts');
                    throw new \RuntimeException('No available accounts');
                }


                $connection = $this->clientPool->getConnection();
                $response = $connection->getConnection()->request($method, $uri, $this->buildOptions($account, $options));
                return $response;

            } catch (GuzzleException $e) {
                

                if ($e->getCode() === 429) {
                    $this->logger->warning('触发平台限流策略，切换账号', [
                        'account' => $account->getName(),
                        'error' => $e->getMessage()
                    ]);
                    
                    // var_dump($account->getName().json_encode($options['json']).$e->getMessage());
                    Coroutine::sleep(self::RETRY_DELAY);

                    continue; // 立即重试不增加尝试次数
                }
        
                $attempts++;
        
                $this->logger->warning("请求失败（尝试 {$attempts}/".self::MAX_RETRIES."）", [
                    'account' => $account->getName(),
                    'error' => $e->getMessage()
                ]);
        
            }finally{
                $connection && $connection->release();
            }
        }

        throw new \RuntimeException('请求失败');
    }

    /**
     * 构建请求选项
     * @param Account $account 当前使用的账号
     * @param array $options 原始请求选项
     * @return array 合并后的请求选项
     */
    private function buildOptions(Account $account, array $options): array
    {
        return array_merge_recursive($options, [
            'cookies' => $account->getCookieJar(),
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Referer' => 'https://www.bacc.bot/dashboard'
            ]
        ]);
    }

    public function calculate(array $data): Response
    {

        try {

            $response = $this->sendRequest('post', '/api/ai7', [
                'json' => $data,
                'timeout' => 45,
            ]);
            
            $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            
            if (empty($responseData['message'])) {
                throw new InvalidArgumentException('message is empty ' . json_encode($responseData, JSON_THROW_ON_ERROR));
            }

            return new Response($responseData);
            
        } catch (GuzzleException $e) {
            $this->logger->error('Calculate request failed', [
                'exception' => $e,
                'data' => $data,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

}