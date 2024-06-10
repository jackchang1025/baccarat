<?php

namespace App\Baccarat\Service\Websocket\Message;

use App\Baccarat\Service\LotteryResult;
use Hyperf\Collection\Collection;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;

class Message implements  Jsonable,Arrayable
{

    // 定义错误码常量
    const ERROR_TOKEN_EXPIRED = 'API_EC_ACC_SID_INVALID';
    const ERROR_TIMEOUT = 'IDLE_5M';
    const ERROR_CONNECT_CLOSED = 'NetConnection.Connect.Closed';

    public function __construct(protected array $message = [])
    {

    }

    public function getAction(): ?string
    {
        return $this->message['action'] ?? null;
    }

    public function isOnActivity(): bool
    {
        return $this->getAction() === 'onActivity';
    }

    public function isOnUpdateGameInfo(): bool
    {
        return $this->getAction() === 'onUpdateGameInfo';
    }

    public function isPing(): string|int|bool
    {
        return $this->message['ping'] ?? false;
    }

    public function isReady(): bool
    {
        return $this->getAction() === 'ready';
    }

    public function isOnHallLogin(): bool
    {
        return $this->getAction() === 'onHallLogin';
    }

    public function getMessage(): array
    {
        return $this->message;
    }

    public function isTokenExpiredError(): bool|string
    {
        return $this->checkErrorCode(self::ERROR_TOKEN_EXPIRED);
    }

    public function isTimeoutError(): bool|string
    {
        return $this->checkErrorCode([self::ERROR_TIMEOUT]);
    }

    public function isConnectClosedError(): bool|string
    {
        return $this->checkErrorCode(self::ERROR_CONNECT_CLOSED);
    }

    // 新增的checkErrorCode方法用于检查错误码，减少代码重复
    private function checkErrorCode(string|array $errorCode): bool|string
    {
        if (is_array($errorCode)) {
            return in_array($this->message['runEor'] ?? '', $errorCode, true) ? $this->message['runEor'] : false;
        }
        return isset($this->message['runEor']) && $this->message['runEor'] === $errorCode ? $this->message['runEor'] : false;
    }

    public function isEmpty(): bool
    {
        return empty($this->message);
    }

    public function toJson()
    {
        return json_encode($this->message, true);
    }

    public function terrace(string $terrace):array
    {
        return $this->message['sl'][$terrace] ?? [];
    }

    public function transformationLotteryResult(): Collection
    {
        $collection = new Collection();

        if (!empty($this->message['sl']) && is_array($this->message['sl'])){
            foreach ($this->message['sl'] as $terrace => $item){
                $collection->push(LotteryResult::fromArray($terrace,$item));
            }
        }

        return $collection;
    }

    public function __toString(): string
    {
        return json_encode($this->message);
    }

    public function toArray(): array
    {
        return $this->message;
    }
}