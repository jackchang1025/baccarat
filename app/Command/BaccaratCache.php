<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Model\BaccaratTerrace;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\Redis\LotteryResultService;
use Hyperf\Collection\Collection;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Coroutine\Parallel;
use Hyperf\Database\Model\Relations\HasMany;
use Psr\Container\ContainerInterface;
use Swoole\Runtime;

#[Command]
class BaccaratCache extends HyperfCommand
{
    protected Parallel $parallel;

    public function __construct(protected ContainerInterface $container,protected LotteryResultService $lotteryResultService)
    {
        parent::__construct('baccarat:cache');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        $s = microtime(true);

        if(Runtime::enableCoroutine() === false){
            $this->error("一键协程化失败");
            return;
        }

        try {
            $baccaratTerraceList = BaccaratTerrace::with(['baccaratTerraceDeck' => function (HasMany $query) {
                $query->selectRaw('id, terrace_id, deck_number, created_at,DATE(created_at) as date')
                    ->whereDate('created_at', '<', date('Y-m-d'));
            }])
                ->get()
                ->map(function (BaccaratTerrace $baccaratTerrace) {
                    $baccaratTerrace->grouped_date = $baccaratTerrace->baccaratTerraceDeck->groupBy('date');
                    unset($baccaratTerrace->baccaratTerraceDeck);
                    return $baccaratTerrace;
                });
        } catch (\Exception|\Throwable $e) {
            var_dump($e);
        }

        $this->info('get baccaratTerraceList use '.number_format(microtime(true) - $s, 8).'s',);

        if ($baccaratTerraceList->isEmpty()) {
            return null;
        }

        $this->parallel = new Parallel(50);

        try {
            foreach ($baccaratTerraceList as $baccaratTerrace) {

                foreach ($baccaratTerrace->grouped_date as $baccaratTerraceDeckList) {

                    foreach ($baccaratTerraceDeckList as $baccaratTerraceDeck){
                        $this->info("get data redis key: {$this->lotteryResultService->getFormat($baccaratTerrace->title,$baccaratTerraceDeck->date)} field: {$baccaratTerraceDeck->deck_number}");

                        $result = $this->lotteryResultService->hGet($baccaratTerrace->title, $baccaratTerraceDeck->date, (string) $baccaratTerraceDeck->deck_number);
                        if (!$result) {

                            $this->parallel->add(function () use ($baccaratTerrace, $baccaratTerraceDeck) {
                                $this->processDateData($baccaratTerrace, $baccaratTerraceDeck);
                            });
                        }
                    }
                }
            }
        } catch (\Exception|\Throwable $e) {
            var_dump($e);
        }

        $this->parallel->wait();
        $this->info('done in '.number_format(microtime(true) - $s, 8).'s',);
    }

    protected function processDateData(BaccaratTerrace $baccaratTerrace, BaccaratTerraceDeck $baccaratTerraceDeck): void
    {
        $baccaratTerraceDeck = $baccaratTerraceDeck->with(['baccaratLotteryLog' => function ($query) {

            $query->select('id', 'terrace_deck_id', 'issue', 'transformationResult', 'RawData', 'created_at');
        }])->selectRaw('id, terrace_id, deck_number,created_at, DATE(created_at) as date')
            ->find($baccaratTerraceDeck->id);

        $this->lotteryResultService->hSetnx(
            terraceId: $baccaratTerrace->title,
            date: $baccaratTerraceDeck->date,
            shoesId: (string)$baccaratTerraceDeck->deck_number,
            lotteryData: $baccaratTerraceDeck->toArray()
        );

        $this->info("save data redis key: {$this->lotteryResultService->getFormat($baccaratTerrace->title,$baccaratTerraceDeck->date)} field: {$baccaratTerraceDeck->deck_number}");
    }
}
