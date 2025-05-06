<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateBaccaratDeckStatisticsTable extends Migration
{
    public function up(): void
    {
        Schema::create('baccarat_deck_statistics', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('terrace_id')->comment('房间ID');
            $table->unsignedBigInteger('terrace_deck_id')->unique()->comment('牌靴ID');
            $table->integer('deck_number')->comment('牌靴号');
            
            // 总体统计
            $table->integer('total_bets')->default(0)->comment('总投注次数');
            $table->integer('total_wins')->default(0)->comment('总胜利次数');
            $table->integer('total_losses')->default(0)->comment('总失败次数');
            $table->integer('total_ties')->default(0)->comment('总和局次数');
            $table->decimal('total_win_rate', 5, 2)->default(0)->comment('总胜率(%)');
            
            // 可信度统计 (JSON格式)
            $table->json('credibility_stats')->nullable()->comment('可信度统计数据');
            
            // 投注序列
            $table->text('betting_sequence')->nullable()->comment('投注结果序列');
            
            // 时间戳
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            
            // 索引
            $table->index('terrace_id');
            $table->index('deck_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baccarat_deck_statistics');
    }
} 