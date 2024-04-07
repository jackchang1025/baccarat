<?php

namespace App\Baccarat\Service;

use App\Baccarat\Event\RecvMessageEvent;
use App\Baccarat\Service\Exception\WebSocketTimeOutException;
use App\Baccarat\Service\Exception\WebSocketTokenExpiredException;
use App\Baccarat\Service\Output\Output;
use Hyperf\Collection\Collection;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Client;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Concurrent;
use Hyperf\Engine\Channel;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * WebSocketClientService 类是一个用于与 WebSocket 服务器进行通信的客户端服务类。它提供了一系列方法来建立与服务器的连接、发送和接收消息、处理各种操作以及管理连接的生命周期。
 * 利用了 Hyperf 框架提供的协程和并发特性,通过协程实现异步非阻塞的消息处理
 * 当服务端发送数据非常频繁时候将会创建大量的协程，当大量的协程进行 MySQL 读写和 io 操作会导致数据连接池数量不够
 * 使用协程 Channel 进行消息缓冲: 可以引入协程 Channel 作为消息缓冲区,将接收到的消息先存储到 Channel 中,然后由一个或多个专门的消费协程从 Channel 中获取消息并进行处理。这样可以控制同时处理消息的协程数量,避免过多的协程同时进行数据库操作。
 */
class WebSocketClientService
{
    protected Client $client;
    protected Concurrent $concurrent;
    protected Channel $messageChannel;
    protected LoggerInterface $logger;
    protected array $data = [];
    protected int $maxRetries = 3;
    protected int $retryDelay = 5;

    public function __construct(
        protected Output $output,
        protected ClientFactory            $clientFactory,
        protected EventDispatcherInterface $dispatcher,
        protected LoggerFactory            $loggerFactory,
        protected string                   $host,
        protected string                   $token,
        protected int                      $concurrentSize = 100,
        protected int                      $channelSize = 10000
    )
    {
        $this->messageChannel = new Channel($this->channelSize);
        $this->concurrent = new Concurrent($this->concurrentSize);

        $this->connect();
        $this->startMessageConsumers();
    }

    /**
     * 100 个专门的消费协程从 Channel 中获取消息并进行处理。这样可以控制同时处理消息的协程数量,避免过多的协程同时进行数据库操作。
     * 当 WebSocketClientService 类接收消息发生异常时,如果 Channel 不可用,我们应该终止 startMessageConsumers 方法中当前协程的执行,以防止协程继续消费可能无效的数据。
     */
    protected function startMessageConsumers(): void
    {
        for ($i = 0; $i < $this->concurrent->getLimit(); $i++) {
            $this->concurrent->create(function () {
                //检查 Channel 是否正在关闭或已经关闭。如果 Channel 正在关闭或已经关闭,我们应该终止协程的执行。
                while (!$this->messageChannel->isClosing()) {
                    $this->handleMessageConsumers();
                }

                $this->output->error("获取消息失败,Channel 已经被关闭");
            });
        }
    }

    public function handleMessageConsumers(): void
    {

        $lotteryResult = $this->messageChannel->pop();
        if ($lotteryResult === false) {
            return;
        }

        try {

            $s = microtime(true);

            $this->dispatcher->dispatch(new RecvMessageEvent($lotteryResult));

            $this->loggerFactory->create($lotteryResult->terrace, 'baccarat')
                ->debug("use:" . number_format(microtime(true) - $s, 8) . "s channelSize:{$this->messageChannel->length()} connectSize:{$this->concurrent->length()}", $lotteryResult->toArray());

        } catch (\Exception|\Throwable $exception) {

            $this->loggerFactory->get()->error($exception, $lotteryResult?->toArray());

            //重新 push
            if (!$this->pushMessage($lotteryResult)) {
                // 记录推送失败的日志
                $this->loggerFactory->get()->warning('Failed to push message back to channel', $lotteryResult->toArray());
            }
        }
    }

    public function connect(): void
    {
        $this->output->warn("reconnect");
        $this->client = $this->clientFactory->create($this->host);
    }

    public function push(array $data, int $opcode = WEBSOCKET_OPCODE_TEXT, ?int $flags = null): bool
    {
        return $this->client->push(json_encode($data), $opcode, $flags);
    }

    public function login(): bool
    {
        return $this->push(['lang' => 'cn', 'vType' => 'wss', 'site' => '1', 'sid' => $this->token, 'action' => 'hallLogin',]);
    }

    public function handLogin(): bool
    {
        return $this->push(["dev" =>
            ["rd" => "fx",
                "ua" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
                "os" => "Windows 10",
                "srs" => "2560x1440",
                "wrs" => "1440x900",
                "dpr" => 1,
                "pl" => "H5",
                "pf" => "Chrome 122.0.0.0",
                "wv" => "false",
                "aio" => false,
                "vga" => "ANGLE (AMD, AMD Radeon R5 340 (0x00006611) Direct3D11 vs_5_0 ps_5_0, D3D11)",
                "tablet" => false,
                "cts" => 1710668957844,
                "mua" => "",
                "dtp" => "",
                "newaio" => "",
                "ub" => "",
                "pwa" => false,
                "ui" => 16
            ],
            "lang" => "cn",
            "vType" => "wss",
            "vtMode" => true,
            "subscription" => ["shit"],
            "action" => "login",
            "sid" => $this->token]);
    }

    public function handleMessage(string $message): array
    {
        try {
            return json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new WebSocketTimeOutException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 接收消息
     * @param int $timeout
     * @param int $retry
     * @return array
     * @throws WebSocketTimeOutException|WebSocketTokenExpiredException
     */
    public function recv(int $timeout = 30, int $retry = 0): array
    {
        try {

            $msg = $this->client->recv($timeout);
            if (!$msg) {
                throw new WebSocketTimeOutException('接收消息失败或超时');
            }

            $data = $this->handleMessage($msg->data);

            if (isset($data['NetStatusEvent']) && $data['NetStatusEvent'] === 'NetConnection.Connect.Closed') {
                throw new WebSocketTimeOutException('NetConnection Connect Closed');
            }

            if (isset($data['runEor']) && $data['runEor'] === 'API_EC_ACC_SID_INVALID') {
                throw new WebSocketTokenExpiredException("runEor {$data['runEor']}" . json_encode($data));
            }

            return $data;

        } catch (WebSocketTimeOutException $e) {

            $this->output->error($e->getMessage());
            return $this->handleRecvException($e, $timeout, $retry);
        }
    }

    /**
     * 处理接收消息异常
     * @param WebSocketTimeOutException $e
     * @param int $timeout
     * @param int $retry
     * @return array
     * @throws WebSocketTimeOutException|WebSocketTokenExpiredException
     */
    protected function handleRecvException(WebSocketTimeOutException $e, int $timeout, int $retry): array
    {
        if ($retry < $this->maxRetries) {

            $this->output->error("{$e->getMessage()}, 尝试重连第 " . ($retry + 1) . " 次...");

            $this->connect();

            return $this->recv($timeout, $retry + 1);
        }
        throw $e;
    }

    public function run(): void
    {
        while (true) {

            $data = $this->recv();

            if (isset($data['ping'])) {
                $this->push($data);
                continue;
            }

            if (isset($data['action'])) {
                $this->handleAction($data);
                continue;
            }

            echo json_encode($data) . PHP_EOL;
        }
    }

    protected function handleAction(array $data): void
    {
        switch ($data['action']) {
            case 'ready':
                $this->login();
                break;
            case 'onHallLogin':
                $this->handLogin();
                break;
            case 'onActivity':
                break;
            case 'onUpdateGameInfo':

                foreach ($data['sl'] as $terrace => $item) {

                    $lotteryResult = new LotteryResult(
                        terrace: $terrace,
                        issue: $item['rs'] ?? null,
                        result: $item['pk'] ?? null,
                        status: $item['st'] ?? null,
                        rn: $item['rn'] ?? null,
                        data: $item
                    );

                    if ($lotteryResult->terrace == '3001-80') {
                        $this->output->info(":{$lotteryResult}");
                    }

                    if ($lotteryResult->isBaccarat() && ($lotteryResult->isWaiting() || $lotteryResult->isBetting())) {
                        $this->pushMessage($lotteryResult);
                    }
                }

                break;
            case 'onLAS':
                break;
            default:
                break;
        }
    }

    /**
     * 推送 $lotteryResult 到 Channel 时,我们需要确保 Channel 没有被关闭
     * @param LotteryResult $lotteryResult
     * @return bool
     */
    public function pushMessage(LotteryResult $lotteryResult): bool
    {
        if (!$this->messageChannel->isClosing()){
            return $this->messageChannel->push($lotteryResult);
        }
        $this->output->error("推送消息失败,Channel 已经被关闭");
        return false;
    }
}