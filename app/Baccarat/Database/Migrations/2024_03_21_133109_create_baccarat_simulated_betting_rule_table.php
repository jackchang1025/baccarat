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

class CreateBaccaratSimulatedBettingRuleTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('baccarat_simulated_betting_rule', function (Blueprint $table) {
            $table->engine = 'Innodb';
            $table->comment('投注规则关联表');
            $table->bigIncrements('id')->comment('主键');
            $table->addColumn('bigInteger', 'betting_id', ['unsigned' => true, 'comment' => '投注id']);
            $table->addColumn('bigInteger', 'rule_id', ['unsigned' => true, 'comment' => '规则id']);
            $table->unique(['betting_id', 'rule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baccarat_simulated_betting_rule');
    }
}
