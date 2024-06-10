<?php

namespace App\Baccarat\Service\BaccaratBetting;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\LotteryResult;

class BaccaratBettingWaitingResult
{

    public function __construct(
        protected readonly LotteryResult $lotteryResult,
        protected readonly ?BaccaratLotteryLog $lotteryLog = null,
        protected readonly ?BaccaratTerraceDeck $deck = null,
    )
    {
    }

    public static function fromLotteryResult(LotteryResult $lotteryResult,BaccaratLotteryLog $lotteryLog = null,BaccaratTerraceDeck $deck = null): self
    {
        return new self($lotteryResult, $lotteryLog,$deck);
    }

    public function getLotteryResult(): LotteryResult
    {
        return $this->lotteryResult;
    }

    public function getLotteryLog(): ?BaccaratLotteryLog
    {
        return $this->lotteryLog;
    }

    public function getDeck(): ?BaccaratTerraceDeck
    {
        return $this->deck;
    }


}