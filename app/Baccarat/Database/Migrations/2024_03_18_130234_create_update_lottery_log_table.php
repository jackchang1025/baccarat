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

class CreateUpdateLotteryLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('baccarat_lottery_log', function (Blueprint $table) {
            // 修改 RawData 字段为 JSON 格式
            $table->json('RawData')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baccarat_lottery_log', function (Blueprint $table) {
            // 回滚操作，将 RawData 字段改回 string 格式
            // 注意：回滚时需要指定长度，默认为 255
            $table->string('RawData', 255)->change();
        });
    }
}
