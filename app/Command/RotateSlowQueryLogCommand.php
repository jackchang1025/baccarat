<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Crontab\RotateSlowQueryLog;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

#[Command]
class RotateSlowQueryLogCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container,protected RotateSlowQueryLog $log)
    {
        parent::__construct('rotate:slow-query-log');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('按日期切割 MySQL 慢日志文件');
    }

    public function handle()
    {
        $this->log->execute();

        $this->info('MySQL slow query log file reopened.');
    }
}
