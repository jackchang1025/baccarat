<?php

namespace App\Baccarat\Listener\RuleSimulatedBetting;

use App\Baccarat\Event\HistoricalDataSimulatedBetting;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class RuleSimulatedBetting implements ListenerInterface
{

    public function listen(): array
    {
        return [
            HistoricalDataSimulatedBetting::class,
        ];
    }

    public function process(object $event): void
    {

    }
}