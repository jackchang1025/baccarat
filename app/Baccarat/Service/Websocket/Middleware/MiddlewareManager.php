<?php

namespace App\Baccarat\Service\Websocket\Middleware;

use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Pipeline\Pipeline;
use Hyperf\Stringable\Str;
use Psr\Container\ContainerInterface;

class MiddlewareManager
{
    protected array $middlewares = [];

    public function __construct(
        private readonly ContainerInterface $container,
    )
    {
        $this->middlewares = $this->loadMiddlewares(Middleware::class);
    }

    protected function loadMiddlewares(string $annotation): array
    {
        $middlewares = AnnotationCollector::getClassesByAnnotation($annotation);

        foreach ($middlewares as $className => $middleware) {
            if (!$middleware instanceof Middleware) {
                continue;
            }

            // 检查类名是否已存在，以避免重复
            if (!in_array($className, $this->normalizeGroupInternal($middleware->group), true)) {

                $data[$middleware->priority][] = $className;

                $this->setMiddlewares($middleware->group,$data);
            }
        }

        // 对每个组内的优先级进行排序
        foreach ($this->middlewares as &$groupMiddlewares) {
            ksort($groupMiddlewares);
        }

        return $this->middlewares;
    }

    public function getMiddlewares(string $group): array{
        return $this->middlewares[$group] ?? [];
    }

    public function getMiddleware(string $group): array
    {
        return $this->middlewares[$this->ucfirst($group)] ?? [];
    }

    public function setMiddlewares(string $group,array $middlewares): void
    {
        $this->middlewares[$this->ucfirst($group)] = $middlewares;
    }

    public function normalizeGroupInternal(string $group): array
    {
        $middlewares = $this->getMiddleware($group);
        if (!empty($middlewares)) {
            return Arr::flatten($middlewares);
        }

        return [];
    }

    protected function ucfirst(string $group): string
    {
        //将字符串首字母转换为大写
        return Str::ucfirst($group);
    }

    public function handle(string $group, mixed $message)
    {
        $groupMiddlewares = $this->normalizeGroupInternal($group);

        if (empty($groupMiddlewares)) {
            // 如果中间件为空，直接返回原消息，增加日志记录可选
            // log warning here
            return $message;
        }

        return (new Pipeline($this->container))
            ->through($groupMiddlewares)
            ->send($message)
            ->then(function ($message) {
                return $message;
            });
    }
}