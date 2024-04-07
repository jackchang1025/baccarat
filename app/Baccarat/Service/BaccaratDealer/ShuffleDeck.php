<?php

namespace App\Baccarat\Service\BaccaratDealer;

class ShuffleDeck implements ShuffleDeckInterface
{

    /**
     * @param int $shuffleTimes 洗牌的次数，默认为1次。
     */
    public function __construct(protected int $shuffleTimes = 1)
    {
    }

    public function shuffleDeck(array $deck) :array
    {
        for ($i = 0; $i < $this->shuffleTimes; $i++) {
            shuffle($deck);
        }

        return $deck;
    }
}