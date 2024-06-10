<?php

declare(strict_types=1);

namespace App\Command;
use App\Baccarat\Service\Websocket\Channel\RedisChannel;
use App\Baccarat\Service\Websocket\Message\MessageProduct;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class WebsocketProduct extends HyperfCommand
{

    protected MessageProduct $product;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('baccarat:websocket:product');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
        $this->addOption('number', 'N', InputOption::VALUE_REQUIRED, 'connection number');
    }

    public function handle()
    {
        $numberOption = $this->input->getOption('number');
        if (!is_numeric($numberOption) || $numberOption <= 0) {
            $numberOption = 5;
        }

        $this->product = make(MessageProduct::class,['channel'=>make(RedisChannel::class), 'size'=>$numberOption]);
        $this->product->run();

        $this->line('Hello Hyperf!', 'info');
    }
}
