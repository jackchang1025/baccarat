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

use App\Baccarat\Service\BaccaratTerraceService;
use App\Baccarat\Request\BaccaratTerraceRequest;
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
 * 台，桌控制器
 * Class BaccaratTerraceController
 */
#[Controller(prefix: "baccarat/terrace"), Auth]
#[Middleware(middleware: CheckModuleMiddleware::class)]
class BaccaratTerraceController extends MineController
{
    /**
     * 业务处理服务
     * BaccaratTerraceService
     */
    #[Inject]
    protected BaccaratTerraceService $service;

    
    /**
     * 列表
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("index"), Permission("baccarat:terrace, baccarat:terrace:index")]
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
    #[GetMapping("list"), Permission("baccarat:terrace, baccarat:terrace:list")]
    public function list(): ResponseInterface
    {
        return $this->success($this->service->getList($this->request->all()));
    }

    /**
     * 新增
     * @param BaccaratTerraceRequest $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("save"), Permission("baccarat:terrace:save"), OperationLog]
    public function save(BaccaratTerraceRequest $request): ResponseInterface
    {
        return $this->success(['id' => $this->service->save($request->all())]);
    }

    /**
     * 更新
     * @param int $id
     * @param BaccaratTerraceRequest $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PutMapping("update/{id}"), Permission("baccarat:terrace:update"), OperationLog]
    public function update(int $id, BaccaratTerraceRequest $request): ResponseInterface
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
    #[GetMapping("read/{id}"), Permission("baccarat:terrace:read")]
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
    #[DeleteMapping("delete"), Permission("baccarat:terrace:delete"), OperationLog]
    public function delete(): ResponseInterface
    {
        return $this->service->delete((array) $this->request->input('ids', [])) ? $this->success() : $this->error();
    }

    /**
     * 数据导入
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("import"), Permission("baccarat:terrace:import")]
    public function import(): ResponseInterface
    {
        return $this->service->import(\App\Baccarat\Dto\BaccaratTerraceDto::class) ? $this->success() : $this->error();
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
        return (new \Mine\MineCollection)->export(\App\Baccarat\Dto\BaccaratTerraceDto::class, '模板下载', []);
    }

    /**
     * 数据导出
     * @return ResponseInterface
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("export"), Permission("baccarat:terrace:export"), OperationLog]
    public function export(): ResponseInterface
    {
        return $this->service->export($this->request->all(), \App\Baccarat\Dto\BaccaratTerraceDto::class, '导出数据列表');
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
     * 获取牌靴日期列表
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("deck-dates"), Permission("baccarat:terrace:list")]
    public function getDeckDates(): ResponseInterface
    {
        return $this->success($this->service->getDeckDates($this->request->all()));
    }

    /**
     * 获取牌靴列表
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("deck-list"), Permission("baccarat:terrace:list")]
    public function getDeckList(): ResponseInterface
    {
        return $this->success($this->service->getDeckList($this->request->all()));
    }
}