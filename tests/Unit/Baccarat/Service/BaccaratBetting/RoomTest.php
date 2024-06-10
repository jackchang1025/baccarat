<?php

namespace HyperfTests\Unit\Baccarat\Service\BaccaratBetting;

use App\Baccarat\Service\Room\RoomManager;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use HyperfTests\Unit\BaseTest;
use Mockery;

/**
 * @group redis
 * @group baccarat
 */
class RoomTest extends BaseTest
{
    protected RoomManager $room;
    protected RedisProxy $redis;
    protected RedisFactory $redisFactory;
    protected string $bettingId = 'bettingId';
    protected string $deckId = 'deckId';

    public function setUp(): void
    {
        $this->redisFactory = Mockery::mock(RedisFactory::class)->makePartial();
        $this->redis = Mockery::mock(RedisProxy::class)->makePartial();
        $this->redisFactory->shouldReceive('get')
            ->with('baccarat')
            ->andReturn($this->redis);
        $this->room = make(RoomManager::class, ['redisFactory' => $this->redisFactory, 'bettingId' => $this->bettingId, 'deckId' => $this->deckId]);

        parent::setUp();
    }

    public function testExitRoom()
    {
        $this->redis->shouldReceive('multi')->once();
        $this->redis->shouldReceive('hDel')
            ->with('room', $this->bettingId)
            ->once()
            ->andReturn(1);
        $this->redis->shouldReceive('del')
            ->with($this->room->getExpirationKey())
            ->once()
            ->andReturn(1);
        $this->redis->shouldReceive('exec')
            ->once()
            ->andReturn([1, 1]);

        $this->assertTrue($this->room->exitRoom());
    }

    public function testIsCurrentRoomConsistent()
    {
        // Test when room ID exists and matches the deck ID
        $this->redis->shouldReceive('hGet')
            ->with('room', $this->bettingId)
            ->once()
            ->andReturn($this->deckId);
        $this->redis->shouldReceive('exists')
            ->with($this->room->getExpirationKey())
            ->once()
            ->andReturn(true);
        $this->assertTrue($this->room->isCurrentRoomConsistent());

        // Test when room ID exists but does not match the deck ID
        $this->redis->shouldReceive('hGet')
            ->with('room', $this->bettingId)
            ->once()
            ->andReturn($this->faker->text);
        $this->redis->shouldReceive('exists')
            ->with($this->room->getExpirationKey())
            ->once()
            ->andReturn(true);
        $this->assertFalse($this->room->isCurrentRoomConsistent());

        // Test when room ID does not exist
        $this->redis->shouldReceive('hGet')
            ->with('room', $this->bettingId)
            ->once()
            ->andReturn(null);
        $this->assertFalse($this->room->isCurrentRoomConsistent());

        // Test when room is expired
        $this->redis->shouldReceive('hGet')
            ->with('room', $this->bettingId)
            ->once()
            ->andReturn($this->deckId);
        $this->redis->shouldReceive('exists')
            ->with($this->room->getExpirationKey())
            ->once()
            ->andReturn(false);
        $this->redis->shouldReceive('multi')->once();
        $this->redis->shouldReceive('hDel')
            ->with('room', $this->bettingId)
            ->once()
            ->andReturn(1);
        $this->redis->shouldReceive('del')
            ->with($this->room->getExpirationKey())
            ->once()
            ->andReturn(1);
        $this->redis->shouldReceive('exec')
            ->once()
            ->andReturn([1, 1]);
        $this->assertFalse($this->room->isCurrentRoomConsistent());
    }

    public function testGetCurrentRoomId()
    {
        $this->redis->shouldReceive('hGet')
            ->with('room', $this->bettingId)
            ->once()
            ->andReturn($this->deckId);
        $this->redis->shouldReceive('exists')
            ->with($this->room->getExpirationKey())
            ->once()
            ->andReturn(true);
        $this->assertEquals($this->deckId, $this->room->getRoom());

        $this->redis->shouldReceive('hGet')
            ->with('room', $this->bettingId)
            ->once()
            ->andReturn($this->deckId);
        $this->redis->shouldReceive('exists')
            ->with($this->room->getExpirationKey())
            ->once()
            ->andReturn(false);
        $this->redis->shouldReceive('multi')->once();
        $this->redis->shouldReceive('hDel')
            ->with('room', $this->bettingId)
            ->once()
            ->andReturn(1);
        $this->redis->shouldReceive('del')
            ->with($this->room->getExpirationKey())
            ->once()
            ->andReturn(1);
        $this->redis->shouldReceive('exec')
            ->once()
            ->andReturn([1, 1]);
        $this->assertNull($this->room->getRoom());
    }

    public function testCheckRoom()
    {
        $this->assertTrue($this->room->checkRoom($this->deckId));
        $this->assertFalse($this->room->checkRoom('other_deck_id'));
    }

    public function testJoinRoom()
    {
        $this->redis->shouldReceive('multi')->once();
        $this->redis->shouldReceive('hSetNx')
            ->with('room', $this->bettingId, $this->deckId)
            ->once()
            ->andReturn(true);
        $this->redis->shouldReceive('set')
            ->with($this->room->getExpirationKey(), 1, ['EX' => RoomManager::DEFAULT_EXPIRATION_SECONDS, 'NX'])
            ->once()
            ->andReturn(true);
        $this->redis->shouldReceive('exec')
            ->once()
            ->andReturn([true, true]);
        $this->assertTrue($this->room->joinRoom());

        $this->redis->shouldReceive('multi')->once();
        $this->redis->shouldReceive('hSetNx')
            ->with('room', $this->bettingId, $this->deckId)
            ->once()
            ->andReturn(false);
        $this->redis->shouldReceive('set')
            ->with($this->room->getExpirationKey(), 1, ['EX' => RoomManager::DEFAULT_EXPIRATION_SECONDS, 'NX'])
            ->once()
            ->andReturn(false);
        $this->redis->shouldReceive('exec')
            ->once()
            ->andReturn([false, false]);
        $this->assertFalse($this->room->joinRoom());
    }

    public function testSetExpiration()
    {
        $this->redis->shouldReceive('set')
            ->with($this->room->getExpirationKey(), 1, ['EX' => RoomManager::DEFAULT_EXPIRATION_SECONDS, 'NX'])
            ->once()
            ->andReturn(true);
        $this->assertTrue($this->room->setExpiration());

        $seconds = 120;
        $this->redis->shouldReceive('set')
            ->with($this->room->getExpirationKey(), 1, ['EX' => $seconds, 'NX'])
            ->once()
            ->andReturn(true);
        $this->assertTrue($this->room->setExpiration($seconds));
    }

    public function testExtendExpiration()
    {
        $this->redis->shouldReceive('expire')
            ->with($this->room->getExpirationKey(), RoomManager::DEFAULT_EXPIRATION_SECONDS)
            ->once()
            ->andReturn(true);
        $this->assertTrue($this->room->extendExpiration());

        $seconds = 120;
        $this->redis->shouldReceive('expire')
            ->with($this->room->getExpirationKey(), $seconds)
            ->once()
            ->andReturn(true);
        $this->assertTrue($this->room->extendExpiration($seconds));
    }

    public function testIsRoomExpired()
    {
        $this->redis->shouldReceive('exists')
            ->with($this->room->getExpirationKey())
            ->once()
            ->andReturn(true);
        $this->assertFalse($this->room->isRoomExpired());

        $this->redis->shouldReceive('exists')
            ->with($this->room->getExpirationKey())
            ->once()
            ->andReturn(false);
        $this->assertTrue($this->room->isRoomExpired());
    }

    public function testGetExpirationKey()
    {
        $this->assertEquals("room_expiration:{$this->bettingId}", $this->room->getExpirationKey());
    }
}