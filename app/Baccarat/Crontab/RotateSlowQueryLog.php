<?php

namespace App\Baccarat\Crontab;


use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\DbConnection\Db;

#[Crontab(name: "rotateSlowQueryLog", rule: "0 0 * * *", callback: "execute", memo: "每天凌晨 0 点执行")]
class RotateSlowQueryLog
{
    /**
     * 按日期切割 MySQL 慢日志文件
     * @return void
     * @throws \Exception
     */
    public function execute(): void
    {
        $path = BASE_PATH . '/docker/mysql/logs';

        $logFile = "$path/slow_query.log";
        $rotatedLogFile = "$path/slow_query_" . date('Ymd') . '.log';

        if (!file_exists($path)){
            throw new \Exception("No slow query log file found: $logFile");
        }

        if (file_exists($rotatedLogFile)){
            unlink($rotatedLogFile);
        }

        rename($logFile, $rotatedLogFile);

        // 通知 MySQL 重新打开慢查询日志文件
        Db::statement("SET GLOBAL slow_query_log = 'OFF'");
        Db::statement("SET GLOBAL slow_query_log = 'ON'");
    }
}