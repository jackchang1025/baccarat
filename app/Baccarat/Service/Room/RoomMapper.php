<?php

namespace App\Baccarat\Service\Room;

use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Symfony\Component\Serializer\SerializerInterface;

class RoomMapper implements MapperInterface
{
    protected RedisProxy $redis;

    public function __construct(protected RedisFactory $redisFactory,protected SerializerInterface $serializer)
    {
        $this->redis = $this->redisFactory->get('baccarat');
    }

    /**
     * @param string $roomId
     * @return Room|null
     * @throws \RedisException
     */
    public function getRoom(string $roomId): ?Room
    {
        if (!$data = $this->redis->hGet('room', $roomId)){
            return null;
        }

        $room = $this->serializer->deserialize($data, Room::class,'json');
        if (!$room instanceof Room){
            throw new \UnexpectedValueException('Deserialized data is not a Room instance.');
        }
        return $room;
    }

    /**
     * @param string $roomId
     * @param Room $room
     * @return bool
     * @throws \RedisException
     */
    public function joinRoom(string $roomId,Room $room): bool
    {
        return (bool) $this->redis->hSetNx(self::ROOM_NAME, $roomId, $this->serializer->serialize($room,'json'));
    }

    /**
     * @param string $roomId
     * @param Room $room
     * @return bool
     * @throws \RedisException
     */
    public function extendExpiration(string $roomId,Room $room): bool
    {
        return $this->updateRoomInRedis($roomId, $room);
    }

    /**
     * @param string $roomId
     * @param Room $room
     * @return bool
     * @throws \RedisException
     */
    protected function updateRoomInRedis(string $roomId,Room $room): bool
    {
        return (bool) $this->redis->hSet(self::ROOM_NAME, $roomId, $this->serializer->serialize($room,'json'));
    }

    /**
     * @param string $roomId
     * @return bool
     * @throws \RedisException
     */
    public function exitRoom(string $roomId): bool
    {
        return (bool) $this->redis->hDel(self::ROOM_NAME, $roomId);
    }
}