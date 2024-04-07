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

class CreateUpdateBaccaratSimulatedBettingLogWithRuleTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('baccarat_betting_log', function (Blueprint $table) {

//            $table->addColumn('bigInteger', 'baccarat_rule_id', ['comment' => '投注日志规则id']);

//            $table->index('baccarat_rule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baccarat_betting_log', function (Blueprint $table) {

//            $table->dropColumn('baccarat_rule_id');

//            $table->dropIndex('baccarat_rule_id');
        });
    }
}
