<?php

namespace App\Baccarat\Service\Platform\Bacc\Middleware;

use App\Baccarat\Service\LoggerFactory;
use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

#[Middleware(middleware: 'log', priority: 99999)]
class LogMiddleware
{

    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->create('bacc');
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {

            // 调用下一个处理程序
            /** @var FulfilledPromise $promise */
            $promise = $handler($request, $options);

            // 修改 Promise 的结果
            return $promise->then(
                function (ResponseInterface $response) use ($request) {
                    // 请求之后的操作...

                    $this->logger->info(sprintf('Request: %s %s', $request->getMethod(), $request->getUri()), [
                        'headers' => $request->getHeaders(),
                        'body' => (string) $request->getBody(),
                    ]);

                    $this->logger->info(sprintf('Response: %s %s', $response->getStatusCode(), $response->getReasonPhrase()), [
                        'headers' => $response->getHeaders(),
                        'body' => (string) $response->getBody(),
                    ]);

                    // 将响应体的指针重置到开头,以便后续的代码可以再次读取响应体
                    $response->getBody()->rewind();

                    return $response;
                },
                function (\Exception $e) use ($request) {
                    // 对于 Promise 的拒绝情况，也可以记录日志
                    $this->logger->error('Promise rejection: ' . $e->getMessage(), ['exception' => $e]);
                    // 传递错误
                    throw $e;
                }
            );
        };
    }
}