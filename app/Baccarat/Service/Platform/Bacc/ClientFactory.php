<?php

namespace App\Baccarat\Service\Platform\Bacc;

use App\Baccarat\Service\Platform\Bacc\Middleware\Middleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Guzzle\ClientFactory as BaseClientFactory;
use Hyperf\Guzzle\CoroutineHandler;
use Psr\Container\ContainerInterface;
use Swoole\Runtime;

class ClientFactory extends BaseClientFactory
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct($container);
    }

    public function create(array $options = []): \GuzzleHttp\Client
    {
        // 判断是否在 Swoole 协程环境中，如果不在则使用 GuzzleHttp\Client
        $stack = $this->runInSwoole && Coroutine::inCoroutine() && (Runtime::getHookFlags() & $this->nativeCurlHook) == 0 ? HandlerStack::create(new CoroutineHandler()) : HandlerStack::create();

        // 获取所有的中间件并添加到 HandlerStack
        $this->addMiddlewareToStack($stack);

        // 合并配置
        $config = array_replace(['handler' => $stack], $options);

        if (method_exists($this->container, 'make')) {
            // Create by DI for AOP.
            return $this->container->make(Client::class, ['config' => $config]);
        }

        return new Client($config);
    }

    protected function addMiddlewareToStack(HandlerStack $stack): void
    {
        $middlewares = [];

        // 获取所有带有 Middleware 注解的类
        $annotatedClasses = AnnotationCollector::getClassesByAnnotation(Middleware::class);

        foreach ($annotatedClasses as $className => $middleware) {
            if(! $middleware instanceof Middleware){
                continue;
            }
            $middlewares[$middleware->priority][] = [$className, $middleware->middleware];
        }

        // 按照优先级排序中间件
        ksort($middlewares);

        foreach ($middlewares as $priorityMiddlewares) {
            foreach ($priorityMiddlewares as $middlewares) {
                if (! is_array($middlewares)){
                    continue;
                }
                list($className, $middlewareName) = $middlewares;
                $stack->push($this->createMiddleware($className), $middlewareName);
            }
        }
    }

    protected function createMiddleware(string $middlewareClass): callable
    {
        return function (callable $handler) use ($middlewareClass) {
            if (!is_callable($middleware = $this->container->get($middlewareClass))) {
                throw new \InvalidArgumentException("Middleware '$middlewareClass' must be callable.");
            }
            return $middleware($handler);
        };
    }
}