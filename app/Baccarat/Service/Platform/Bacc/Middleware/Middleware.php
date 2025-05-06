<?php

namespace App\Baccarat\Service\Platform\Bacc\Middleware;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Hyperf\HttpServer\PriorityMiddleware;

#[Attribute(Attribute::TARGET_CLASS)]
class Middleware extends AbstractAnnotation
{
    public function __construct(public string $middleware = '', public int $priority = PriorityMiddleware::DEFAULT_PRIORITY)
    {
    }
}