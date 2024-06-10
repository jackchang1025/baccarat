<?php

namespace HyperfTests\Unit\Baccarat\Service\Websocket;

use App\Baccarat\Service\Exception\WebSocketTimeOutException;
use App\Baccarat\Service\Websocket\Connection;
use App\Baccarat\Service\Websocket\Message;
use App\Baccarat\Service\Websocket\MessageDecoder\MessageDecoder;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Coroutine\Coroutine;
use HyperfTests\Unit\BaseTest;
use function Swoole\Coroutine\run;
class ConnectionTest extends BaseTest
{
    protected Connection $connection;
    protected ConfigInterface $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = make(ConfigInterface::class);
    }

    public function testIsLoggedIn()
    {
        run(function (){

            $this->connection = make(Connection::class,[
                'messageDecoder' => make(MessageDecoder::class),
                'host'=>$this->config->get('websocket.host'),
                'connectionTimeout'=>$this->config->get('websocket.connectionTimeout'),
                'remainingTimeOut'=>$this->config->get('websocket.remainingTimeOut'),
            ]);

            $this->assertFalse($this->connection->isLoggedIn());

            $this->connection->setIsLoggedIn(true);
            $this->assertTrue($this->connection->isLoggedIn());

        });

    }

    public function testRetryRecvMessageExceptionMessage()
    {

        run(function (){
            $this->connection = make(Connection::class,[
                'messageDecoder' => make(MessageDecoder::class),
                'host'=>'124.0.0.1:9501',
                'connectionTimeout'=> 10 ,
                'remainingTimeOut'=>30 ,
            ]);

            $this->expectExceptionMessage('Failed to receive message or timed out');
            $this->expectException(WebSocketTimeOutException::class);
            $this->connection->retryRecvMessage();

        });

    }

    public function testRetryRecvMessage()
    {
        run(function (){

            $this->connection = make(Connection::class,[
                'messageDecoder' => make(MessageDecoder::class),
                'host'=>$this->config->get('websocket.host'),
                'connectionTimeout'=>$this->config->get('websocket.connectionTimeout'),
                'remainingTimeOut'=>$this->config->get('websocket.remainingTimeOut'),
            ]);

            $message = $this->connection->retryRecvMessage();

            $this->assertInstanceOf(Message::class,$message);
        });

    }

    public function testLogin()
    {
        run(function (){
            $this->connection = make(Connection::class,[
                'messageDecoder' => make(MessageDecoder::class),
                'host'=>$this->config->get('websocket.host'),
                'connectionTimeout'=>$this->config->get('websocket.connectionTimeout'),
                'remainingTimeOut'=>$this->config->get('websocket.remainingTimeOut'),
            ]);

            $this->connection->setIsLoggedIn(false);

            $this->assertTrue($this->connection->login());
            $this->assertTrue($this->connection->isLoggedIn());

        });
    }
}