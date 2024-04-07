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

class CreateBaccaratSimulatedRuleTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('baccarat_simulated_rule', function (Blueprint $table) {
            $table->engine = 'Innodb';
            $table->comment('投注规则表');
            $table->bigIncrements('id')->comment('主键');

            $table->addColumn('string', 'title', ['length' => 255, 'comment' => '名称']);
            $table->addColumn('string', 'rule', ['length' => 255, 'comment' => '规则']);
            $table->addColumn('string', 'betting_value', ['length' => 255, 'comment' => '投注值']);
            $table->addColumn('smallInteger', 'status', ['default' => 1, 'comment' => '状态 (1正常 2停用)'])->nullable();
            $table->addColumn('smallInteger', 'sort', ['unsigned' => true, 'default' => 0, 'comment' => '排序'])->nullable();
            $table->addColumn('string', 'remark', ['length' => 255, 'comment' => '备注'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baccarat_simulated_rule');
    }
}
