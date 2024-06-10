<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Service\Websocket\Channel\RedisChannel;
use App\Baccarat\Service\Websocket\Message\MessageConsumer;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Engine\Contract\ChannelInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class WebsocketConsumer extends HyperfCommand
{
    protected ChannelInterface $channel;

    protected MessageConsumer $consumer;

    public function __construct(
        protected ContainerInterface $container,
    )
    {
        parent::__construct('baccarat:websocket:consumer');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
        $this->addOption('number', 'N', InputOption::VALUE_REQUIRED, 'consumer number');
    }

    public function handle()
    {
        // 验证输入的 'number' 选项是否存在且为正整数
        $numberOption = $this->input->getOption('number');
        if (!is_numeric($numberOption) || $numberOption <= 0) {
            $numberOption = 20;
        }

        $this->consumer = make(MessageConsumer::class, ['channel' => make(RedisChannel::class), 'concurrency' => $numberOption]);

        $this->consumer->run();

        $this->line('Hello Hyperf!', 'info');
    }
}
