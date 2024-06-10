<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Mapper\BaccaratSimulatedBettingMapper;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Service\BaccaratBetting\BaccaratBettingCache;
use App\Baccarat\Service\BaccaratTerraceDeckService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Swoole\Runtime;
use App\Baccarat\Service\BaccaratBetting\BaccaratBetting as BaccaratBettingService;

#[Command]
class BaccaratBetting extends HyperfCommand
{
    protected BaccaratBettingService $bettingService;

    public function __construct(
        protected readonly ContainerInterface             $container,
        protected readonly BaccaratTerraceDeckService     $terraceDeckService,
        protected readonly BaccaratSimulatedBettingMapper $bettingMapper,
        protected readonly BaccaratBettingCache           $baccaratBettingCache,
    )
    {
        parent::__construct('baccarat:betting');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        $s = microtime(true);

        $betting = $this->bettingMapper->getBaccaratSimulatedBettingList();
        if ($betting->isEmpty()) {
            $this->error("get betting failed");
            return;
        }

        $betting->each(function (BaccaratSimulatedBetting $simulatedBetting) {

            if ($simulatedBetting->baccaratSimulatedBettingRule->isNotEmpty()) {
                $this->container->make(BaccaratBettingService::class, ['betting' => $simulatedBetting])->betting();
            }
        });

        $this->info("betting success Use s:" . number_format(microtime(true) - $s, 8));
    }
}
