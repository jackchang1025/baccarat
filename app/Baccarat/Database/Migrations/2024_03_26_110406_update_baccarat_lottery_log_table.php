<?php
/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://gitee.com/xmo/MineAdmin
 */

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class UpdateBaccaratLotteryLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('baccarat_lottery_log', function (Blueprint $table) {
            // 修改 issue 字段为 bigInteger 格式

            $table->bigInteger('issue')->change();

            // 添加 issue 列的唯一索引
            $table->unique('issue','index_issue');

            // 添加 terrace_deck_id 列的单列索引
            $table->index('terrace_deck_id','index_terrace_deck_id');

            // 添加 created_at 列的单列索引,用于时间范围查询或分区
            $table->index('created_at','index_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baccarat_lottery_log', function (Blueprint $table) {

            // 修改 issue 字段为 string 格式
            $table->string('issue')->change();

             // 删除 issue 列的单列索引
             $table->dropUnique('index_issue');

             // 删除 terrace_deck_id 列的单列索引
             $table->dropIndex('index_terrace_deck_id');
 
             // 删除 created_at 列的单列索引
             $table->dropIndex('index_created_at');
        });
    }
}
