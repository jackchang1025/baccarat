<?php

namespace App\Baccarat\Service\Room;


use App\Baccarat\Service\Exception\RoomException;

class RoomManager
{
    protected ?Room $room = null;

    public function __construct(protected MapperInterface $mapper, protected string $bettingId, protected string $terraceId, protected string $deckId)
    {
        $this->getRoom();

        $this->checkRoomExpiredOrExitRoom();
    }


    /**
     * 退出房间
     * @return bool
     * @throws RoomException
     */
    public function exitRoom(): bool
    {
        if (!$this->mapper->exitRoom($this->room->getBettingId())) {
            throw new RoomException('Failed to exit room.');
        }
        $this->room = null;
        return true;
    }

    /**
     * 获取房间
     * @return Room
     * @throws RoomException
     */
    public function getRoom(): Room
    {
        if ($this->room !== null) {
            return $this->room;
        }

        $this->room = $this->mapper->getRoom($this->bettingId);

        // 如果成功获取到房间，且房间未过期或未退出，则直接返回这个房间
        if ($this->room && !$this->checkRoomExpiredOrExitRoom()) {
            return $this->room;
        }

        return $this->room = $this->joinRoom();
    }

    /**
     * @return bool
     * @throws RoomException
     */
    public function checkRoomExpiredOrExitRoom(): bool
    {
        return $this->room->isRoomExpired() && $this->exitRoom();
    }

    /**
     * 判断当前房间数据是否与给定结果一致
     * @return bool
     */
    public function checkRoom(): bool
    {
        return $this->room->checkRoom($this->deckId);
    }

    /**
     * 加入房间
     * @return Room
     * @throws RoomException
     */
    public function joinRoom(): Room
    {
        $room = new Room(bettingId: $this->bettingId, terraceId: $this->terraceId, deckId: $this->deckId,createTime: time());
        if (!$this->mapper->joinRoom($this->bettingId, $room)) {
            throw new RoomException('Failed to join room.');
        }
        return $room;
    }

    /**
     * 延长房间过期时间
     * @return bool
     * @throws RoomException
     */

    public function extendExpiration(): bool
    {
        $room = $this->room->touch();

        if ($room->getCreateTime() === $this->room->getCreateTime()){
            return true;
        }

        $this->mapper->extendExpiration($room->getBettingId(), $room);

        $this->room = $room;
        return true;
    }
}