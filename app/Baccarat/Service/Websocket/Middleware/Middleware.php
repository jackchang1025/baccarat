<?php

namespace App\Baccarat\Service\Websocket\Middleware;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Hyperf\HttpServer\PriorityMiddleware;

#[Attribute(Attribute::TARGET_CLASS)]
class Middleware extends AbstractAnnotation
{
    public function __construct(public string $middleware = '', public string $group = '',public int $priority = PriorityMiddleware::DEFAULT_PRIORITY)
    {
    }
}