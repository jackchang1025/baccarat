<?php

namespace App\Baccarat\Service\BaccaratDealer;

/**
 * 模拟机械洗牌
 */
class SimulatedMechanicalShuffling implements ShuffleDeckInterface
{
    /**
     * @param int $shuffleTimes 洗牌的次数，默认为1次。
     */
    public function __construct(protected int $shuffleTimes = 3)
    {
    }
    public function shuffleDeck(array $deck): array
    {
        $shuffledDeck = [];
        $deckLength = count($deck);

        for ($i = 0; $i < $this->shuffleTimes; $i++) {
            $cutPosition = random_int(1, $deckLength - 1);
            $leftHalf = array_slice($deck, 0, $cutPosition);
            $rightHalf = array_slice($deck, $cutPosition);
            $deck = array_merge($rightHalf, $leftHalf);
        }

        return $deck;
    }
}