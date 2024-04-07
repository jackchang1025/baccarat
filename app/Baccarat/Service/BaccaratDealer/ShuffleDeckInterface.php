<?php

namespace App\Baccarat\Service\BaccaratDealer;

interface ShuffleDeckInterface
{
    public function shuffleDeck(array $deck) :array;
}