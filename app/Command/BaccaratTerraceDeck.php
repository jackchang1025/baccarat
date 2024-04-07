<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Service\BaccaratSimulatedBettingService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Swoole\Runtime;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class BaccaratTerraceDeck extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container,protected BaccaratSimulatedBettingService $bettingService)
    {
        parent::__construct('baccarat:betting');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');

        $this->addOption('betting', 'B', InputOption::VALUE_REQUIRED, 'betting id');
    }

    public function handle()
    {
        if(Runtime::enableCoroutine() === false){
            $this->error("一键协程化失败");
            return;
        }

        $s = microtime(true);

        $bettingId = $this->input->getOption('betting');
        if (!$bettingId){
             $this->error("betting id is null");
            return;
        }

        $this->bettingService->betting((int) $bettingId);

        $this->info("{$bettingId} betting success Use s:".number_format(microtime(true) - $s, 8));
    }
}
