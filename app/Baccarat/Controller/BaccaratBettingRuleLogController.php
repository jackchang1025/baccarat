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

namespace App\Baccarat\Controller;

use App\Baccarat\Service\BaccaratBettingRuleLogService;
use App\Baccarat\Request\BaccaratBettingRuleLogRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Annotation\Auth;
use Mine\Annotation\RemoteState;
use Mine\Annotation\OperationLog;
use Mine\Annotation\Permission;
use Mine\MineController;
use Psr\Http\Message\ResponseInterface;
use Mine\Middlewares\CheckModuleMiddleware;
use Hyperf\HttpServer\Annotation\Middleware;

/**
 * 投注日志规则表控制器
 * Class BaccaratBettingRuleLogController
 */
#[Controller(prefix: "baccarat/bettingRuleLog"), Auth]
#[Middleware(middleware: CheckModuleMiddleware::class)]
class BaccaratBettingRuleLogController extends MineController
{
    /**
     * 业务处理服务
     * BaccaratBettingRuleLogService
     */
    #[Inject]
    protected BaccaratBettingRuleLogService $service;

    
    /**
     * 列表
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("index"), Permission("baccarat:bettingRuleLog, baccarat:bettingRuleLog:index")]
    public function index(): ResponseInterface
    {
        return $this->success($this->service->getPageList($this->request->all()));
    }

    /**
     * 列表
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("group"), Permission("baccarat:bettingRuleLog, baccarat:bettingRuleLog:group")]
    public function group(): ResponseInterface
    {
        return $this->success($this->service->group($this->request->all()));
    }

    /**
     * 读取数据
     * @param int $id
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("read/{id}"), Permission("baccarat:bettingRuleLog:read")]
    public function read(int $id): ResponseInterface
    {
        return $this->success($this->service->read($id));
    }

    /**
     * 单个或批量删除数据到回收站
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[DeleteMapping("delete"), Permission("baccarat:bettingRuleLog:delete"), OperationLog]
    public function delete(): ResponseInterface
    {
        return $this->service->delete((array) $this->request->input('ids', [])) ? $this->success() : $this->error();
    }

    /**
     * 更新
     * @param int $id
     * @param BaccaratBettingRuleLogRequest $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PutMapping("update/{id}"), Permission("baccarat:bettingRuleLog:update"), OperationLog]
    public function update(int $id, BaccaratBettingRuleLogRequest $request): ResponseInterface
    {
        return $this->service->update($id, $request->all()) ? $this->success() : $this->error();
    }

    /**
     * 新增
     * @param BaccaratBettingRuleLogRequest $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("save"), Permission("baccarat:bettingRuleLog:save"), OperationLog]
    public function save(BaccaratBettingRuleLogRequest $request): ResponseInterface
    {
        return $this->success(['id' => $this->service->save($request->all())]);
    }


    /**
     * 远程万能通用列表接口
     * @return ResponseInterface
     */
    #[PostMapping("remote"), RemoteState(true)]
    public function remote(): ResponseInterface
    {
        return $this->success($this->service->getRemoteList($this->request->all()));
    }
}