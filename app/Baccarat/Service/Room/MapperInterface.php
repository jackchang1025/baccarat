<?php

namespace App\Baccarat\Service\Room;

interface MapperInterface
{
    const ROOM_NAME = 'room';

    public function getRoom(string $roomId): ?Room;

    public function joinRoom(string $roomId,Room $room): bool;

    public function extendExpiration(string $roomId,Room $room): bool;

    public function exitRoom(string $roomId): bool;
}