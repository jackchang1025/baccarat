<?php

namespace App\Baccarat\Service;

use Hyperf\Logger\Exception\InvalidConfigException;
use Hyperf\Logger\Logger;
use Hyperf\Logger\LoggerFactory as BaseLoggerFactory;
use Psr\Log\LoggerInterface;
use function Hyperf\Support\make;

class LoggerFactory extends BaseLoggerFactory
{

    public function create($name = 'debug', $group = 'baccarat'):LoggerInterface
    {
        if (isset($this->loggers[$group][$name]) && $this->loggers[$group][$name] instanceof Logger) {
            return $this->loggers[$group][$name];
        }

        $logger = $this->makeLogger($name, $group);

        return $this->loggers[$group][$name] = $logger;
    }
    public function makeLogger($name = 'debug', $group = 'baccarat'): LoggerInterface
    {
        $config = $this->config->get('logger');
        if (! isset($config[$group])) {
            throw new InvalidConfigException(sprintf('Logger config[%s] is not defined.', $group));
        }

        $config = $config[$group];

        // 如果 $path 不为空,替换 $config 中的日志文件路径
        $config = $this->replaceLogPath($config, $this->getPath("{$group}/{$name}/{$name}.log"));

        //获取 $config 日志文件路径并替换为 $path
        $handlers = $this->handlers($config);
        $processors = $this->processors($config);

        return make(Logger::class, [
            'name' => $name,
            'handlers' => $handlers,
            'processors' => $processors,
        ]);
    }

    protected function replaceLogPath(array $config, string $path): array
    {
        // 检查 $config 中是否存在 'handler' 和 'constructor' 键
        if (isset($config['handler']['constructor'])) {
            // 检查 'constructor' 是否为数组,并且包含 'stream' 键
            if (isset($config['handler']['constructor']['stream'])){
                $config['handler']['constructor']['stream'] = $path;
            }
            if (isset($config['handler']['constructor']['filename'])){
                $config['handler']['constructor']['filename'] = $path;
            }
        }

        return $config;
    }

    public function getPath(string $path): string
    {
        return BASE_PATH . "/runtime/logs/{$path}";
    }
}