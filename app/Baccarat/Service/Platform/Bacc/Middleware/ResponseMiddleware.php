<?php

namespace App\Baccarat\Service\Platform\Bacc\Middleware;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[Middleware(middleware: 'response',priority: 99999)]
class ResponseMiddleware
{
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            // 请求之前的操作...

            // 调用下一个处理程序
            /** @var FulfilledPromise $promise */
            $promise = $handler($request, $options);

            // 修改 Promise 的结果
            return $promise->then(
                function (ResponseInterface $response) {
                    // 请求之后的操作...


                    return $response;
                }
            );
        };
    }
}