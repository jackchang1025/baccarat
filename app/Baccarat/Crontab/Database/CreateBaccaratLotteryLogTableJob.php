<?php

namespace App\Baccarat\Crontab\Database;

use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

#[Crontab(name: "createBaccaratLotteryLogTable", rule: "0 0 * * 0", callback: "execute", memo: "每周日的 00:00 执行创建 BaccaratLotteryLog 表")]
class CreateBaccaratLotteryLogTableJob
{

    protected function getTableName(): string
    {
        // 获取下一周的年份和周数
        $nextWeekDate = date('Y-m-d', strtotime('next week'));
        $year = date('Y', strtotime($nextWeekDate));
        $week = date('W', strtotime($nextWeekDate));

        // 拼接表名
        return 'baccarat_lottery_log_' . $year . str_pad($week, 2, '0', STR_PAD_LEFT);
    }
    public function execute(string $tableName = null): void
    {
        $tableName ??= $this->getTableName();

        // 检查表是否已经存在
        if (!Schema::hasTable($tableName)) {
            // 创建新的分表
            Schema::create($tableName, function (Blueprint $table) {
                $table->bigIncrements('id')->comment('主键');
                $table->bigInteger('terrace_deck_id')->comment('牌靴');
                $table->bigInteger('issue')->comment('期号');
                $table->string('result')->nullable()->comment('开奖结果');
                $table->string('transformationResult')->nullable()->comment('转换开奖结果');
                $table->json('RawData')->nullable()->comment('原始数据');
                $table->timestamp('created_at')->nullable()->comment('创建时间');
                $table->timestamp('updated_at')->nullable()->comment('更新时间');

                $table->unique('issue');
                $table->index('created_at');
                $table->index('terrace_deck_id');
            });
        }
    }
}