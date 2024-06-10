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
use App\Baccarat\Service\SimulationBettingAmount\BaccaratFactory;
use Hyperf\Di\Container;
use Mine\Abstracts\AbstractService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * 表注释服务类
 */
class BaccaratWaitingSequenceService extends AbstractService
{
    /**
     * @var BaccaratWaitingSequenceMapper
     */
    public $mapper;

    protected LoggerInterface $logger;

    public function __construct(protected ContainerInterface $container,BaccaratWaitingSequenceMapper $mapper,protected BaccaratFactory $baccaratFactory)
    {
        $this->mapper = $mapper;

        $this->logger = $container->get(LoggerFactory::class)->get();
    }

    public function chart(int $id,array $params): array
    {
        $BaccaratWaitingSequence = $this->read($id);

        $totalBetAmount = (float) $params['betTotalAmount'];
        $defaultBetAmount = (float) $params['betDefaultAmount'];

        $BaccaratWaitingSequence->betLogList = $this->baccaratFactory->create($totalBetAmount,$defaultBetAmount)
            ->play($BaccaratWaitingSequence->sequence);

        return $BaccaratWaitingSequence->toArray();
    }
}