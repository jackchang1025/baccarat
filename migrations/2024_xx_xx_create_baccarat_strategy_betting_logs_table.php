<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateBaccaratStrategyBettingLogsTable extends Migration
{
    public function up(): void
    {
        Schema::create('baccarat_strategy_betting_logs', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('simulated_betting_id')->comment('模拟投注ID');
            $table->unsignedBigInteger('terrace_deck_id')->comment('牌靴ID');
            $table->string('strategy_type')->comment('策略类型');
            $table->integer('round')->comment('轮次');
            $table->decimal('bet_amount', 15, 2)->comment('投注金额');
            $table->decimal('balance', 15, 2)->comment('当前余额');
            $table->char('result', 1)->comment('结果(0:输,1:赢)');
            $table->timestamps();
            
            // 外键
            $table->foreign('simulated_betting_id')
                ->references('id')
                ->on('baccarat_simulated_betting')
                ->onDelete('cascade');
            $table->foreign('terrace_deck_id')
                ->references('id')
                ->on('baccarat_terrace_decks')
                ->onDelete('cascade');
                
            // 索引
            $table->index(['simulated_betting_id', 'terrace_deck_id', 'round']);
            $table->index('strategy_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baccarat_strategy_betting_logs');
    }
} 