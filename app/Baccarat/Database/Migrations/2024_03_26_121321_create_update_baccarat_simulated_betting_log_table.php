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

class CreateUpdateBaccaratSimulatedBettingLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('baccarat_betting_log', function (Blueprint $table) {
            // 修改 issue 字段为 bigInteger 格式

            $table->bigInteger('issue')->change();

            // 添加 issue 列的唯一索引
            $table->index('issue','index_issue');

            $table->index('betting_id','index_betting_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baccarat_betting_log', function (Blueprint $table) {
            // 修改 issue 字段为 bigInteger 格式

            $table->string('issue')->change();

            // 添加 issue 列的唯一索引
            $table->dropIndex('index_issue');

            $table->dropIndex('index_betting_id');
        });
    }
}
