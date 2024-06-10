<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Coroutine\Concurrent;
use Psr\Container\ContainerInterface;
use Swoole\Runtime;
use App\Baccarat\Crontab\BaccaratUpdateSequence as BaccaratUpdateSequenceService;

#[Command]
class BaccaratUpdateSequence extends HyperfCommand
{
    protected Concurrent $concurrent;
    public function __construct(protected ContainerInterface $container,protected BaccaratUpdateSequenceService $updateSequence)
    {
        parent::__construct('baccarat:update:sequence');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('更新牌局序列号');
    }

    public function handle()
    {
        Runtime::enableCoroutine(true);

        $this->info("start run");

        $s = microtime(true);

        $this->updateSequence->execute();

        $this->info("done use s:".number_format(microtime(true) - $s, 8));
    }
}
