<?php

namespace App\Baccarat\Event;

use App\Baccarat\Model\BaccaratTerraceDeck;

class HistoricalDataSimulatedBetting
{

    public function __construct(public BaccaratTerraceDeck $baccaratTerraceDeck)
    {
    }
}