<?php

namespace App\Baccarat\Service\RoomManager;

use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Lysice\HyperfRedisLock\RedisLock;

class RoomManager
{
    private const LOCK_PREFIX = 'baccarat:room_lock:';
    private const LOCK_EXPIRE = 3;
    private const STATE_PREFIX = 'baccarat:room_state:';
    private const STATE_EXPIRE = 60; // 秒

    protected RedisProxy $redis;

    protected RedisLock $redisLock;

    public function __construct(
        private RedisFactory $redisFactory
    ) {
        $this->redis = $redisFactory->get('default');
    }


    public function statusPrefix(): string 
    {
        return self::STATE_PREFIX;
    }

    public function lockPrefix(): string 
    {
        return self::LOCK_PREFIX;
    }

    /**
     * 尝试进入房间
     * @param string $deckId 牌靴ID
     */
    public function enterRoom(int $terraceId,int $deckId): bool
    {

        // 检查是否在其他房间
        if ($current = $this->getCurrentRoom()) {
            
            // 如果当前在房间，则判断房间 id 和 牌靴 id 是否一致
            if ($current['terrace_id'] !== $terraceId && $current['deck_id'] !== $deckId) {
                return false;
            }
            // 如果当前在房间 且 房间id 一致而 牌靴id 不一致，则说明开始新的一局，则直接加入房间
            if ($current['terrace_id'] === $terraceId && $current['deck_id'] !== $deckId) {
                return $this->joinRoom($terraceId,$deckId);
            }

        }

        // 没有加入房间，则直接加入房间
        return $this->joinRoom($terraceId,$deckId);

    }

    protected function joinRoom(int $terraceId,int $deckId): bool
    {
        // 设置房间状态
        $state = [
            'terrace_id' => $terraceId,
            'deck_id' => $deckId,
            'entry_time' => time(),
            'attempts' => 0,
            'last_bet' => null
        ];

        // // 设置60秒过期
        return (bool) $this->redis->set(
            $this->statusPrefix(),
            json_encode($state),
            ['EX' => self::STATE_EXPIRE]
        );
    }

    /**
     * 获取当前房间状态
     */
    public function getCurrentRoom(): ?array
    {
        $data = $this->redis->get(
            $this->statusPrefix()
        );

        return $data ? json_decode($data, true) : null;
    }

    /**
     * 更新房间状态
     */
    public function updateState(int $terraceId,int $deckId): void
    {
        //退出房间
        $this->exitRoom();

        //进入房间
        $this->enterRoom($terraceId,$deckId);
    }

    /**
     * 退出房间
     */
    public function exitRoom(): void
    {
        $this->redis->del(
            $this->statusPrefix()
        );
    }

    /**
     * 验证房间一致性
     */
    public function checkRoom(int $terraceId,int $deckId): bool
    {
        $current = $this->getCurrentRoom();

        return $current && $current['terrace_id'] === $terraceId && $current['deck_id'] === $deckId;
    }
}
