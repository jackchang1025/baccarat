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

use App\Baccarat\Service\BaccaratWaitingSequenceService;
use App\Baccarat\Request\BaccaratWaitingSequenceRequest;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Mine\Middlewares\CheckModuleMiddleware;
use Hyperf\HttpServer\Annotation\Middleware;

/**
 * 表注释控制器
 * Class BaccaratWaitingSequenceController
 */
#[Controller(prefix: "baccarat/waitingSequence"), Auth]
#[Middleware(middleware: CheckModuleMiddleware::class)]
class BaccaratWaitingSequenceController extends MineController
{
    /**
     * 业务处理服务
     * BaccaratWaitingSequenceService
     */
    #[Inject]
    protected BaccaratWaitingSequenceService $service;

    
    /**
     * 列表
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("index"), Permission("baccarat:waitingSequence, baccarat:waitingSequence:index")]
    public function index(): ResponseInterface
    {
        return $this->success($this->service->getPageList($this->request->all()));
    }

    /**
     * 回收站列表
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("recycle"), Permission("baccarat:waitingSequence:recycle")]
    public function recycle(): ResponseInterface
    {
        return $this->success($this->service->getPageListByRecycle($this->request->all()));
    }

    /**
     * 单个或批量真实删除数据 （清空回收站）
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[DeleteMapping("realDelete"), Permission("baccarat:waitingSequence:realDelete"), OperationLog]
    public function realDelete(): ResponseInterface
    {
        return $this->service->realDelete((array) $this->request->input('ids', [])) ? $this->success() : $this->error();
    }

    /**
     * 单个或批量恢复在回收站的数据
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PutMapping("recovery"), Permission("baccarat:waitingSequence:recovery"), OperationLog]
    public function recovery(): ResponseInterface
    {
        return $this->service->recovery((array) $this->request->input('ids', [])) ? $this->success() : $this->error();
    }

    /**
     * 新增
     * @param BaccaratWaitingSequenceRequest $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("save"), Permission("baccarat:waitingSequence:save"), OperationLog]
    public function save(BaccaratWaitingSequenceRequest $request): ResponseInterface
    {
        return $this->success(['id' => $this->service->save($request->all())]);
    }

    /**
     * 更新
     * @param int $id
     * @param BaccaratWaitingSequenceRequest $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PutMapping("update/{id}"), Permission("baccarat:waitingSequence:update"), OperationLog]
    public function update(int $id, BaccaratWaitingSequenceRequest $request): ResponseInterface
    {
        return $this->service->update($id, $request->all()) ? $this->success() : $this->error();
    }

    /**
     * 读取数据
     * @param int $id
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("read/{id}"), Permission("baccarat:waitingSequence:read")]
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
    #[DeleteMapping("delete"), Permission("baccarat:waitingSequence:delete"), OperationLog]
    public function delete(): ResponseInterface
    {
        return $this->service->delete((array) $this->request->input('ids', [])) ? $this->success() : $this->error();
    }

    /**
     * 更改数据状态
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PutMapping("changeStatus"), Permission("baccarat:waitingSequence:update"), OperationLog]
    public function changeStatus(): ResponseInterface
    {
        return $this->service->changeStatus(
            (int) $this->request->input('setting_generate_tables.id'),
            (string) $this->request->input('statusValue'),
            (string) $this->request->input('statusName', 'status')
        ) ? $this->success() : $this->error();
    }

    /**
     * 数字运算操作
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PutMapping("numberOperation"), Permission("baccarat:waitingSequence:update"), OperationLog]
    public function numberOperation(): ResponseInterface
    {
        return $this->service->numberOperation(
            (int) $this->request->input('setting_generate_tables.id'),
            (string) $this->request->input('numberName'),
            (int) $this->request->input('numberValue', 1),
        ) ? $this->success() : $this->error();
    }

    /**
     * 数据导入
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("import"), Permission("baccarat:waitingSequence:import")]
    public function import(): ResponseInterface
    {
        return $this->service->import(\App\Baccarat\Dto\BaccaratWaitingSequenceDto::class) ? $this->success() : $this->error();
    }

    /**
     * 下载导入模板
     * @return ResponseInterface
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("downloadTemplate")]
    public function downloadTemplate(): ResponseInterface
    {
        return (new \Mine\MineCollection)->export(\App\Baccarat\Dto\BaccaratWaitingSequenceDto::class, '模板下载', []);
    }

    /**
     * 数据导出
     * @return ResponseInterface
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("export"), Permission("baccarat:waitingSequence:export"), OperationLog]
    public function export(): ResponseInterface
    {
        return $this->service->export($this->request->all(), \App\Baccarat\Dto\BaccaratWaitingSequenceDto::class, '导出数据列表');
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

    /**
     * chart
     * @param int $id
     * @param BaccaratWaitingSequenceRequest $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[GetMapping("chart/{id}"), Permission("baccarat:waitingSequence:chart")]
    public function chart(int $id,BaccaratWaitingSequenceRequest $request): ResponseInterface
    {
        return $this->success($this->service->chart($id,$request->all()));
    }
}