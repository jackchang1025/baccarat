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

class CreateUpdateBaccaratTerraceDeckTableTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('baccarat_terrace_deck', function (Blueprint $table) {
            // 修改 issue 字段为 bigInteger 格式

            $table->integer('deck_number')->change();

            $table->index(['terrace_id','deck_number','created_at'],'index_terrace_id_deck_number_created_at');

            $table->index('terrace_id','index_terrace_id');
        
            $table->index('created_at','index_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baccarat_terrace_deck', function (Blueprint $table) {
            // 修改 issue 字段为 bigInteger 格式

            $table->string('deck_number')->change();

            $table->dropIndex('index_terrace_id_deck_number_created_at');

            $table->dropIndex('index_terrace_id');
        
            $table->dropIndex('index_created_at');
        });
    }
}
