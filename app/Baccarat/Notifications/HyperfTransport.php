<?php

namespace App\Baccarat\Notifications;

use App\Baccarat\Notifications\Exception\MessageNotificationException;
use Hyperf\Guzzle\ClientFactory;
use Symfony\Component\Notifier\Event\FailedMessageEvent;
use Symfony\Component\Notifier\Event\MessageEvent;
use Symfony\Component\Notifier\Event\SentMessageEvent;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class HyperfTransport implements TransportInterface
{
    public function __construct(protected ClientFactory $clientFactory, protected ?EventDispatcherInterface $dispatcher = null)
    {

    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        $httpClient = $this->clientFactory->create();

        $data = [
            'msgtype' => 'text',
            'text' => [
                'content' => $message->getSubject(),
            ],
        ];

        $response = $httpClient->request('POST', $message->getTransport(), ['json' => $data]);

        if ($response->getStatusCode() !== 200) {
            throw new \HttpException('Failed to send message using Hyperf transport.');
        }

        $result = json_decode($response->getBody()->getContents(), true);

        if ($result['errcode'] !== 0) {
            throw new MessageNotificationException($result['errmsg']);
        }

        return new SentMessage($message, (string) $this);
    }

    public function __toString()
    {
        return 'hyperf';
    }

    public function supports(MessageInterface $message): bool
    {
        return true;
    }

    public function send(MessageInterface $message): ?SentMessage
    {
        if (null === $this->dispatcher) {
            return $this->doSend($message);
        }

        $this->dispatcher->dispatch(new MessageEvent($message));

        try {
            $sentMessage = $this->doSend($message);
        } catch (\Throwable $error) {
            $this->dispatcher->dispatch(new FailedMessageEvent($message, $error));

            throw $error;
        }

        $this->dispatcher->dispatch(new SentMessageEvent($sentMessage));

        return $sentMessage;
    }
}