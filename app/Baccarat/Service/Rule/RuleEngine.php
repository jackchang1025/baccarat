<?php

namespace App\Baccarat\Service\Rule;

use App\Baccarat\Service\LoggerFactory;
use Hyperf\Collection\Collection;
use Hyperf\Coroutine\Concurrent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RuleEngine
{
    protected Collection $rules;

    /**
     */
    public function __construct(protected LoggerFactory $loggerFactory)
    {
        $this->rules = new Collection();
    }

    public function getRules(): Collection
    {
        return $this->rules;
    }

    public function addRule(RuleInterface $rule): static
    {
        $this->rules->push($rule);
        return $this;
    }

    public function applyRules(string $string): Collection
    {
        return $this->rules->filter(function (RuleInterface $rule) use ($string) {

            return $rule->match($string);
        });
    }

    public function applyRulesOnce(string $string): ?RuleInterface
    {
        return $this->rules->first(function (RuleInterface $rule) use ($string) {

            return $rule->match($string);
        });
    }
}