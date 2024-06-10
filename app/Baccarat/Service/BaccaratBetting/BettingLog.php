<?php

namespace App\Baccarat\Service\BaccaratBetting;

use App\Baccarat\Service\Platform\Bacc\Response;
use App\Baccarat\Service\Rule\RuleInterface;

class BettingLog
{

    public function __construct(
        public RuleInterface $rule,
        public bool          $isBetting = false,
        public string        $lotterySequence = '',
        public ?Response     $response = null,
    )
    {
    }
}