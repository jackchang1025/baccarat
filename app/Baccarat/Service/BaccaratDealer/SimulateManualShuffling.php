<?php

namespace App\Baccarat\Service\BaccaratDealer;

/**
 * 模拟手工洗牌
 */
class SimulateManualShuffling implements ShuffleDeckInterface
{
    public function shuffleDeck(array $deck): array
    {
        $halfLength = ceil(count($deck) / 2);
        $leftHalf = array_slice($deck, 0, $halfLength);
        $rightHalf = array_slice($deck, $halfLength);

        $shuffledDeck = [];
        while (!empty($leftHalf) && !empty($rightHalf)) {
            $takeFromLeft = (bool)random_int(0, 1);
            if ($takeFromLeft) {
                $shuffledDeck[] = array_shift($leftHalf);
            } else {
                $shuffledDeck[] = array_shift($rightHalf);
            }
        }

        return array_merge($shuffledDeck, $leftHalf, $rightHalf);
    }
}