<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Service\BettingCalculator\BaccaratTiePairs;
use App\Baccarat\Service\BettingCalculator\BettingCalculator;
use App\Baccarat\Service\BettingCalculator\BettingCalculatorPair;
use App\Baccarat\Service\BettingCalculator\BettingCalculatorTie;
use Hyperf\Collection\Collection;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class BaccaratTiePair extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container,protected ValidatorFactoryInterface $validationFactory)
    {
        parent::__construct('baccarat:tiePair');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');

        $this->addOption('type', 'T', InputOption::VALUE_REQUIRED, 'tie or pair','tie');
        $this->addArgument('amount',InputArgument::OPTIONAL ,'投注金额',2);
        $this->addArgument('rounds',InputArgument::OPTIONAL ,'投注次数',60);
        $this->addArgument('increaseRates',InputArgument::IS_ARRAY ,'公比数',[1,1.1,1.2,1.3,1.4,1.5]);
    }

    public function handle()
    {

        try {

            $this->bettingCalculator();

        } catch (\Exception|\Throwable $e) {

            var_dump($e);
        }
    }

    public function bettingCalculator()
    {
        $bettingCalculator = new BettingCalculatorTie();
        $this->table(['issue','increaseRate','betAmount','totalAmount','winAmount','winTotalAmount'],$bettingCalculator->handle()->toArray());

        $bettingCalculator = new BettingCalculatorPair();
        $this->table(['issue','increaseRate','betAmount','totalAmount','winAmount','winTotalAmount'],$bettingCalculator->handle()->toArray());
    }

    public function calculatorAmount()
    {
        $type = $this->input->getOption('type');
        $amount = (int) $this->input->getArgument('amount');
        $rounds = (int) $this->input->getArgument('rounds');
        $increaseRates = (array) $this->input->getArgument('increaseRates');

        $validator = $this->validationFactory->make(
            [
                'type'=>$type,
                'amount' => $amount,
                'rounds' => $rounds,
                'increaseRates' => $increaseRates,
            ],
            [
                'type' => 'required|in:tie,pair',
                'amount' => 'required|integer|between:2,999',
                'rounds' => 'required|integer|between:1,60',
                'increaseRates' => 'required|array',
            ]
        );

        if ($validator->fails()){
            foreach ($validator->errors()->all() as $error){
                $this->error($error);
            }
            return ;
        }

        $BaccaratTiePairs = new BaccaratTiePairs(
            type: $type,
            firstBet: $amount,
            rounds: $rounds,
        );

        $this->table(['issue','increaseRate','betAmount','totalAmount','winAmount','winTotalAmount'],$BaccaratTiePairs->run()->toArray());

        return;

        $baccaratTiePair = new \App\Baccarat\Service\BettingCalculator\BaccaratTiePair(
            type: $type,
            firstBet: $amount,
            rounds: $rounds,
            increaseRates: $increaseRates
        );


        $baccaratTiePair->run()
            ->each(function (Collection $bettingLog){

            $this->table(['issue','increaseRate','betAmount','totalAmount','winAmount','winTotalAmount'],$bettingLog->toArray());
        });

        $baccaratTiePair->run();
    }
}
