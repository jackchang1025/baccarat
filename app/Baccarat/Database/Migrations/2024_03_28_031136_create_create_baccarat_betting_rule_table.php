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

class CreateCreateBaccaratBettingRuleTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('baccarat_betting_rule_log', function (Blueprint $table) {
            $table->engine = 'Innodb';
            $table->comment('投注日志规则表');
            $table->bigIncrements('id')->comment('主键');

            $table->addColumn('bigInteger', 'baccarat_betting_log_id',['comment' => '投注日志id'])->nullable();
            $table->addColumn('string', 'title', ['length' => 50, 'comment' => '名称'])->nullable();
            $table->addColumn('string', 'rule', ['length' => 255, 'comment' => '规则'])->nullable();
            $table->addColumn('string', 'betting_value', ['length' => 50, 'comment' => '投注值'])->nullable();
            $table->addColumn('timestamp', 'created_at', ['precision' => 0, 'comment' => '创建时间'])->nullable();
            $table->addColumn('timestamp', 'updated_at', ['precision' => 0, 'comment' => '更新时间'])->nullable();

            $table->index('title','title');
            $table->unique('baccarat_betting_log_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baccarat_betting_rule_log');
    }
}
