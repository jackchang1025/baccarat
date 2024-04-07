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

class CreateBaccaratLotteryLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('baccarat_lottery_log', function (Blueprint $table) {
            $table->engine = 'Innodb';
            $table->comment('开奖日志');
            $table->bigIncrements('id')->comment('主键');
            $table->addColumn('bigInteger', 'terrace_deck_id', ['comment' => '牌靴']);
            $table->addColumn('string', 'issue', ['length' => 255, 'comment' => '期号'])->nullable();
            $table->addColumn('string', 'result', ['length' => 255, 'comment' => '开奖结果'])->nullable();
            $table->addColumn('string', 'transformationResult', ['length' => 255, 'comment' => '转换开奖结果'])->nullable();
            $table->addColumn('string', 'RawData', ['length' => 255, 'comment' => '原始数据'])->nullable();
            $table->addColumn('string', 'remark', ['length' => 255, 'comment' => '备注'])->nullable();
            $table->addColumn('timestamp', 'created_at', ['precision' => 0, 'comment' => '创建时间'])->nullable();
            $table->addColumn('timestamp', 'updated_at', ['precision' => 0, 'comment' => '更新时间'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baccarat_lottery_log');
    }
}
