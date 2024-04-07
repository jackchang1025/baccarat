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

class CreateBaccaratSimulatedBettingLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('baccarat_betting_log', function (Blueprint $table) {
            $table->engine = 'Innodb';
            $table->comment('投注日志表');
            $table->bigIncrements('id')->comment('主键');

            $table->addColumn('bigInteger', 'baccarat_simulated_betting_id', ['comment' => '投注id'])->nullable();
            $table->addColumn('string', 'issue', ['length' => 255, 'comment' => '期号'])->nullable();
            $table->addColumn('string', 'betting_value', ['length' => 255, 'comment' => '投注值'])->nullable();
            $table->addColumn('string', 'betting_result', ['length' => 255, 'comment' => '投注结果'])->nullable();
            $table->addColumn('smallInteger', 'status', ['default' => 1, 'comment' => '状态 (1正常 2停用)'])->nullable();
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
        Schema::dropIfExists('baccarat_betting_log');
    }
}
