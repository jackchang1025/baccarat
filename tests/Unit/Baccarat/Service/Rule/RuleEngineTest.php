<?php

namespace HyperfTests\Unit\Baccarat\Service\Rule;

use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Rule\CustomizeRules;
use App\Baccarat\Service\Rule\RuleEngine;
use App\Baccarat\Service\Rule\RuleInterface;
use Hyperf\Collection\Collection;
use HyperfTests\Unit\BaseTest;

class RuleEngineTest extends BaseTest
{

    protected RuleEngine $engine;

    public function setUp(): void
    {
        parent::setUp();
        $this->engine = new RuleEngine(make(LoggerFactory::class));
    }

    public function testApplyRules()
    {
        $this->engine->addRule(new  CustomizeRules(pattern: '/B{6}P{5}$/', bettingValue: 'B', name: '反规律') );

        $this->engine->addRule(new  CustomizeRules(pattern: '/P{6}B{5}$/', bettingValue: 'P', name: '反规律'));

        $rule = $this->engine->applyRules('BBBBBBPPPPP');
        $this->assertInstanceOf(Collection::class, $rule);
        $this->assertCount(1, $rule);
    }

    public function testAddRule()
    {
        $rule = $this->engine->addRule(new  CustomizeRules(pattern: '/B{6}P{5}$/', bettingValue: 'B', name: '反规律') );

        $this->assertInstanceOf(RuleEngine::class, $rule);
    }

    public function testApplyRulesOnce()
    {
        $this->engine->addRule(new  CustomizeRules(pattern: '/B{6}P{5}$/', bettingValue: 'B', name: '反规律+B') );

        $this->engine->addRule(new  CustomizeRules(pattern: '/P{6}B{5}$/', bettingValue: 'P', name: '反规律+P'));


        $rule = $this->engine->applyRulesOnce('BBBBBBPPPPP');
        $this->assertInstanceOf(RuleInterface::class, $rule);
        $this->assertEquals('B', $rule->getBettingValue());
        $this->assertEquals('反规律+B', $rule->getName());

        $rule = $this->engine->applyRulesOnce('PPPPPPBBBBB');
        $this->assertInstanceOf(RuleInterface::class, $rule);
        $this->assertEquals('P', $rule->getBettingValue());
        $this->assertEquals('反规律+P', $rule->getName());

        $rule = $this->engine->applyRulesOnce('BBBBBBBPPPPPP');
        $this->assertNull($rule);
    }

    public function testApplyRule()
    {
        //反规律+6+反+B
        ///P{1}B{6}P{5}$/
        //B
        $this->engine->addRule(new  CustomizeRules(pattern: '/P{1}B{6}P{5}$/', bettingValue: 'B', name: '反规律+6+反+B') );
        $rule = $this->engine->applyRulesOnce('PBBBBBBPPPPP');
        $this->assertInstanceOf(RuleInterface::class, $rule);
        $this->assertEquals('B', $rule->getBettingValue());

        $this->engine->addRule(new  CustomizeRules(pattern: '/P{1}B{6}P{5}$/', bettingValue: 'B', name: '反规律+6+反+B') );
        $rule = $this->engine->applyRulesOnce('PBBBBBBPPPPP');
        $this->assertInstanceOf(RuleInterface::class, $rule);
        $this->assertEquals('B', $rule->getBettingValue());


    }
}
