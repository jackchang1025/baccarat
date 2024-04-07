<?php

namespace App\Baccarat\Service\Websocket;

use Hyperf\Process\ProcessManager;
use Hyperf\Server\ServerManager;
use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;

//#[Signal]
class WebSocketManageSignalHandler implements SignalHandlerInterface
{
    public function __construct(protected WebSocketManageService $webSocketManageService)
    {
    }

    public function listen(): array
    {
        return [
            [SignalHandlerInterface::WORKER, SIGINT],
            [SignalHandlerInterface::WORKER, SIGTERM],
        ];
    }

    public function handle(int $signal): void
    {
        $this->webSocketManageService->stop();

        ProcessManager::setRunning(false);

        foreach (ServerManager::list() as [$type, $server]) {
            $server->shutdown();
        }
    }
}