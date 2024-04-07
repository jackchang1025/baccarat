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

    protected LoggerInterface $logger;


    /**
     */
    public function __construct(protected LoggerFactory $loggerFactory)
    {
        $this->rules = new Collection();

        $this->logger = $this->loggerFactory->create('match', 'baccarat');
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

            $match = $rule->match($string);

            $this->logger->info("string:{$string} title:{$rule->getName()} rule:{$rule->getRule()} match:{$match}");

            return $match;
        });
    }

    public function applyRulesOnce(string $string): ?RuleInterface
    {
        return $this->rules->first(function (RuleInterface $rule) use ($string) {

            $match = $rule->match($string);

            $this->logger->info("string:{$string} title:{$rule->getName()} rule:{$rule->getRule()} match:{$match}");

            return $match;
        });
    }

    public function applyRulesConcurrent(string $transformationResults): ?RuleInterface
    {

    }
}