<?php

namespace App\Baccarat\Service\Memory;

class Memory
{
    private int|float $memory;

    protected function getMemoryUsage(): int|float
    {
        return memory_get_usage(true);
    }

    public function format(int|float $memory): string
    {
        $memoryInMB = round($memory / (1024 * 1024), 2);
        return "{$memoryInMB} MB";
    }

    public function initMemoryUsage(): int|float
    {
        return $this->memory = $this->getMemoryUsage();
    }

    public function calculateCurrentlyUsedMemory(): int|float
    {
        return bcsub($this->getMemoryUsage(), $this->memory, 2);
    }
}