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

class CreateBaccaratTerraceDeckTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('baccarat_terrace_deck', function (Blueprint $table) {
            $table->engine = 'Innodb';
            $table->comment('牌靴');
            $table->bigIncrements('id')->comment('主键');
            $table->addColumn('bigInteger', 'terrace_id', ['comment' => '台']);
            $table->addColumn('string', 'deck_number', ['length' => 255,'comment' => '牌靴编号'])->nullable();
            $table->addColumn('string', 'lottery_sequence', ['length' => 255,'comment' => '开奖序列'])->nullable();
            $table->addColumn('timestamp', 'created_at', ['precision' => 0, 'comment' => '创建时间'])->nullable();
            $table->addColumn('timestamp', 'updated_at', ['precision' => 0, 'comment' => '更新时间'])->nullable();
            $table->addColumn('string', 'remark', ['length' => 255, 'comment' => '备注'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baccarat_terrace');
    }
}
