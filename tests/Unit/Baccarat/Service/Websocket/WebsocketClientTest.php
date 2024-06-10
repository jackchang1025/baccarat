<?php

namespace HyperfTests\Unit\Baccarat\Service\Websocket;

use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Websocket\WebsocketClient;
use App\Baccarat\Service\Websocket\WebsocketClientFactory;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Engine\Channel;
use Hyperf\Redis\RedisFactory;
use Hyperf\WebSocketClient\ClientFactory;
use Lysice\HyperfRedisLock\RedisLock;
use PHPUnit\Framework\TestCase;
use Swoole\WebSocket\Frame;
use Hyperf\WebSocketClient\Client;
use Hyperf\Engine\Coroutine as Co;


class WebsocketClientTest extends TestCase
{

    protected ClientFactory $clientFactory;
    protected WebsocketClient $client;
    protected string $host;
    protected string $token;
    protected int $connectionTimeout;


    protected function setUp(): void
    {
        $this->clientFactory = $this->createMock(ClientFactory::class);
        $this->host = 'ws://example.com';
        $this->token = 'test_token';
        $this->connectionTimeout = 600;

        $channel = new Channel(1000);

        $this->client = make(WebsocketClient::class,[
            'clientFactory' => $this->clientFactory,
            'output' => make(Output::class),
            'channel' => $channel,
            'host' => $this->host,
            'token' => $this->token,
            'connectionTimeout' => $this->connectionTimeout
        ]);
    }

    public function testIsTimeOut()
    {
        $this->assertFalse($this->client->isTimeOut());
    }

    public function testGetRemainingTimeOut()
    {
        $this->assertTrue($this->client->getRemainingTimeOut() > 30);
    }

    public function testCheck()
    {
        $this->assertFalse($this->client->check());
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(WebsocketClient::class, $this->client->getClient());
    }

    public function testGetMessage()
    {

        $this->assertIsArray($this->client->getMessage());
    }

    /**
     * @RunInSwooleCoroutine
     */
    public function testRecvMessageCoroutine()
    {
        // 获取当前协程的 ID
        $coroutineId = Coroutine::id();

        // 在协程内部执行断言
        Co::create(function () use ($coroutineId) {
            // 等待一段时间，让协程有机会执行
            usleep(100000);

            // 检查协程是否存在
            $this->assertTrue(Coroutine::exists($coroutineId));

            // 检查 WebsocketClient 的状态或行为
            $this->assertInstanceOf(Client::class, $this->client->getClient());
            // 添加更多的断言...
        });
    }
}