<?php

namespace App\Baccarat\Service\Locker;


use Lysice\HyperfRedisLock\Lock;

class Locker extends Lock
{

    public function acquire():bool
    {
       return \Hyperf\Coroutine\Locker::lock($this->name);
    }

    public function release():void
    {
        \Hyperf\Coroutine\Locker::unlock($this->name);
    }

    protected function getCurrentOwner()
    {
        return $this->owner;
    }

    public function forceRelease(): void
    {
        \Hyperf\Coroutine\Locker::unlock($this->name);
    }
}