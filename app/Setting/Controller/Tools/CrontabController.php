<?php

declare(strict_types=1);
namespace App\Setting\Controller\Tools;

use App\Setting\Request\Tool\SettingCrontabCreateRequest;
use App\Setting\Service\SettingCrontabLogService;
use App\Setting\Service\SettingCrontabService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Annotation\Auth;
use Mine\Annotation\OperationLog;
use Mine\Annotation\Permission;
use Mine\MineController;
use Psr\Http\Message\ResponseInterface;

/**
 * 定时任务控制器
 * Class CrontabController
 * @package App\Setting\Controller\Tools
 */
#[Controller(prefix: "setting/crontab"), Auth]
class CrontabController extends MineController
{
    /**
     * 计划任务服务
     */
    #[Inject]
    protected SettingCrontabService $service;

    /**
     * 计划任务日志服务
     */
    #[Inject]
    protected SettingCrontabLogService $logService;

    /**
     * 获取列表分页数据
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("index"), Permission("setting:crontab:index")]
    public function index(): ResponseInterface
    {
        return $this->success($this->service->getList($this->request->all()));
    }

    /**
     * 获取日志列表分页数据
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("logPageList")]
    public function logPageList(): ResponseInterface
    {
        return $this->success($this->logService->getPageList($this->request->all()));
    }

    /**
     * 保存数据
     * @param SettingCrontabCreateRequest $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("save"), Permission("setting:crontab:save"), OperationLog]
    public function save(SettingCrontabCreateRequest $request): ResponseInterface
    {
        return $this->success(['id' => $this->service->save($request->all())]);
    }

    /**
     * 立即执行定时任务
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("run"), Permission("setting:crontab:run"), OperationLog]
    public function run(): ResponseInterface
    {
        $id = $this->request->input('id', null);
        if (is_null($id)) {
            return $this->error();
        } else {
            return $this->service->run($id) ? $this->success() : $this->error();
        }
    }

    /**
     * 获取一条数据信息
     * @param int $id
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("read/{id}"), Permission("setting:crontab:read")]
    public function read(int $id): ResponseInterface
    {
        return $this->success($this->service->read($id));
    }

    /**
     * 更新数据
     * @param int $id
     * @param SettingCrontabCreateRequest $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PutMapping("update/{id}"), Permission("setting:crontab:update"), OperationLog]
    public function update(int $id, SettingCrontabCreateRequest $request): ResponseInterface
    {
        return $this->service->update($id, $request->all()) ? $this->success() : $this->error();
    }

    /**
     * 单个或批量删除
     * @param String $ids
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[DeleteMapping("delete/{ids}"), Permission("setting:crontab:delete")]
    public function delete(String $ids): ResponseInterface
    {
        return $this->service->delete($ids) ? $this->success() : $this->error();
    }

    /**
     * 删除定时任务日志
     * @param String $ids
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[DeleteMapping("deleteCrontabLog/{ids}"), Permission("setting:crontab:deleteCrontabLog"), OperationLog]
    public function deleteCrontabLog(String $ids): \Psr\Http\Message\ResponseInterface
    {
        return $this->logService->delete($ids) ? $this->success() : $this->error();
    }
}