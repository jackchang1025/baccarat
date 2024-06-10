<?php

namespace App\Baccarat\Notifications;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Notifier\Notifier;

class NotifierFactory
{
    public function __invoke(ContainerInterface $container): Notifier
    {
        $channels = $container->get(ConfigInterface::class)->get('notifications', []);
        // 添加对配置的验证逻辑，这里简化处理，实际应用中应更详细
        if (!is_array($channels)) {
            throw new \InvalidArgumentException('Invalid notifications configuration.');
        }

        $notifierChannels = [];

        foreach ($channels as $channelName => $channelConfig) {
            if (empty($channelConfig['class'])) {
                throw new \InvalidArgumentException('Invalid notifications class configuration.');
            }
            if (empty($channelConfig['transport'])) {
                throw new \InvalidArgumentException('Invalid notifications transport configuration.');
            }
            $transport = $container->make($channelConfig['transport']);
            $notifierChannels[$channelName] = $container->make($channelConfig['class'], ['transport' => $transport, 'config' => $channelConfig]);
        }

        return new Notifier($notifierChannels);
    }
}