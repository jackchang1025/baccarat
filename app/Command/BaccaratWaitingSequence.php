<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Service\BettingAmountStrategy\FlatNote;
use App\Baccarat\Service\BettingAmountStrategy\LayeredStrategy;
use App\Baccarat\Service\BettingAmountStrategy\MartingaleStrategy;
use App\Baccarat\Service\SimulationBettingAmount\Baccarat;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

#[Command]
class BaccaratWaitingSequence extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container,protected Baccarat $baccarat)
    {
        parent::__construct('baccarat:waiting-sequence');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        $sequence =  "0000000010001001";

        $this->baccarat->addStrategy(new FlatNote(totalBetAmount: 1000,defaultBetAmount: 20));
        $this->baccarat->addStrategy(new LayeredStrategy(totalBetAmount: 1000,defaultBetAmount: 20));
        $this->baccarat->addStrategy(new MartingaleStrategy(totalBetAmount: 1000,defaultBetAmount: 20));
        $this->baccarat->play($sequence);
        $this->line('Hello Hyperf!', 'info');
    }
}
