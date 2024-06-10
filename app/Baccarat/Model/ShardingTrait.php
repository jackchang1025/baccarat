<?php

namespace App\Baccarat\Model;

use App\Baccarat\Crontab\Database\CreateBaccaratLotteryLogTableJob;
use Carbon\Carbon;
use Hyperf\Database\Model\Model;

trait ShardingTrait
{

    // 获取分表模型
    abstract public function setTable($tableName);
    abstract public function tableName():string;

    public function getTableName(?Carbon $date = null): string
    {
        $date = $date ? $date->toDateString() : date('Y-m-d');

        $year = date('Y', strtotime($date));
        $week = date('W', strtotime($date));

        $str_pad = str_pad($week, 2, '0', STR_PAD_LEFT);

        //使用 sprintf 拼接表名
        return sprintf('%s_%s%s', $this->tableName(), $year, $str_pad);
    }

    public function getShardingModel(?Carbon $date = null, bool $isCreate = false): Model
    {
        $tableName = $this->getTableName($date);

        if ($isCreate){
            // 创建分表任务
            $job = make(CreateBaccaratLotteryLogTableJob::class);
            $job->execute($tableName);
        }

        return $this->setTable($tableName);
    }
}