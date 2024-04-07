<?php

namespace App\Baccarat\Service\BaccaratDealer;

class RandomShuffleDeck extends ShuffleDeck implements ShuffleDeckInterface
{

    public function shuffleDeck(array $deck): array
    {
        for ($i = count($deck) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            $temp = $deck[$i];
            $deck[$i] = $deck[$j];
            $deck[$j] = $temp;
        }

        return $deck;
    }
}