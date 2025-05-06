<?php

namespace App\Baccarat\Notifications;

use Symfony\Component\Notifier\Channel\AbstractChannel;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Symfony\Component\Notifier\Transport\TransportInterface;

class WeChatWorkChannel extends AbstractChannel
{

    public function __construct(TransportInterface $transport,protected readonly array $config)
    {
        parent::__construct($transport);
    }

    public function notify(Notification $notification, RecipientInterface $recipient, string $transportName = null): void
    {
        $message = ChatMessage::fromNotification($notification);

        if (empty($this->config['webhook_url'])){
            throw new \InvalidArgumentException('channels webhook_url config is empty');
        }
        $message->transport($this->config['webhook_url']);

        $this->transport->send($message);
    }

    public function supports(Notification $notification, RecipientInterface $recipient): bool
    {
        return true;
    }
}