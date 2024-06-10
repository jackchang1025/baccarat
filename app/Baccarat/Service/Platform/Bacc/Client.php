<?php

namespace App\Baccarat\Service\Platform\Bacc;

use App\Baccarat\Service\LoggerFactory;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Client
{
    protected ?\GuzzleHttp\Client $client = null;

    protected CookieJar $cookieJar;

    protected LoggerInterface $logger;

    public function __construct(protected ClientFactory $clientFactory, CookieJarFactory $cookieJarFactory, LoggerFactory $loggerFactory)
    {
        $this->cookieJar = $cookieJarFactory->getCookie();
        $this->logger = $loggerFactory->create();
    }

    public function getClient(): \GuzzleHttp\Client
    {
        return $this->client ?? $this->clientFactory->create([
            'base_uri'        => 'https://www.bacc.bot',
            'timeout'         => 30,
            'connect_timeout' => 30,
            'verify'          => false,
            'headers'         => [
                'Content-Type' => 'application/json',
            ],
            //设置 cookie
            'cookies'         => $this->cookieJar,
            //设置代理
            'proxy'           => 'http://192.168.31.204:10809',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Origin' => 'https://www.bacc.bot',
            'Priority' => 'u=1, i',
            'Referer' => 'https://www.bacc.bot/dashboard',
            'Sec-Ch-Ua' => "Chromium;v=124Google Chrome;v=124",
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => 'Windows',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
        ]);
    }


    public function getCookieJar(): CookieJar
    {
        return $this->cookieJar;
    }

    public function dashboard()
    {
        return $this->getClient()->request('/dashboard');
    }

    private function sendRequest(string $method = 'get', $uri = '', array $options = []): ResponseInterface
    {
        // 发送请求并处理可能的异常
        try {

            return $this->getClient()->request($method, $uri, $options);

        } catch (GuzzleException $e) {

            $this->logger->error($e);
            throw $e;
        }
    }

    public function calculate(array $data): Response
    {
        //使用 Guzzle HTTP 客户端发送post请求并发送json 格式数据
        $response = $this->sendRequest('post', '/api/ai2', ['json' => $data]);

        $response = json_decode($response->getBody()->getContents(), true);
        if (empty($response['message'])) {
            throw new InvalidArgumentException('message is empty ' . json_encode($response));
        }

        return new Response($response['message']);
    }

}