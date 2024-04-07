<?php
declare(strict_types=1);
/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://gitee.com/xmo/MineAdmin
 */

namespace App\Baccarat\Service;

use App\Baccarat\Mapper\BaccaratWaitingSequenceMapper;
use App\Baccarat\Model\BaccaratWaitingSequence;
use App\Baccarat\Service\BettingAmountStrategy\FlatNote;
use App\Baccarat\Service\BettingAmountStrategy\LayeredStrategy;
use App\Baccarat\Service\BettingAmountStrategy\MartingaleStrategy;
use App\Baccarat\Service\SimulationBettingAmount\Baccarat;
use Mine\Abstracts\AbstractService;

/**
 * 表注释服务类
 */
class BaccaratWaitingSequenceService extends AbstractService
{
    /**
     * @var BaccaratWaitingSequenceMapper
     */
    public $mapper;

    public function __construct(BaccaratWaitingSequenceMapper $mapper,protected Baccarat $baccarat)
    {
        $this->mapper = $mapper;
    }

    public function chart(int $id,array $params): array
    {
        $BaccaratWaitingSequence = $this->read($id);

        $totalBetAmount = (float) $params['betTotalAmount'];
        $defaultBetAmount = (float) $params['betDefaultAmount'];

        $BaccaratWaitingSequence->betLogList = $this->play($BaccaratWaitingSequence->sequence,$totalBetAmount,$defaultBetAmount);

        return $BaccaratWaitingSequence->toArray();
    }

    public function play(string $sequence,float $totalBetAmount,float $defaultBetAmount):array
    {
        $baccarat = make(Baccarat::class);

        $baccarat->addStrategy(new FlatNote(totalBetAmount: $totalBetAmount,defaultBetAmount: $defaultBetAmount));
        $baccarat->addStrategy(new LayeredStrategy(totalBetAmount: $totalBetAmount,defaultBetAmount: $defaultBetAmount));
        $baccarat->addStrategy(new MartingaleStrategy(totalBetAmount: $totalBetAmount,defaultBetAmount: $defaultBetAmount));

        return $baccarat->play($sequence);
    }
}