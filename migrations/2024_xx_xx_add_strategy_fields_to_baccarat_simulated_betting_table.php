<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddStrategyFieldsToBaccaratSimulatedBettingTable extends Migration
{
    public function up(): void
    {
        Schema::table('baccarat_simulated_betting', function (Blueprint $table) {
            $table->decimal('initial_amount', 15, 2)->after('betting_sequence')->comment('初始金额');
            $table->decimal('default_bet', 15, 2)->after('initial_amount')->comment('默认投注金额');
            $table->decimal('stop_win', 15, 2)->nullable()->after('default_bet')->comment('止盈金额');
            $table->decimal('stop_loss', 15, 2)->nullable()->after('stop_win')->comment('止损金额');
            $table->json('strategy_types')->after('stop_loss')->comment('策略类型集合(FlatNote/Layered/Martingale)');
        });
    }

    public function down(): void
    {
        Schema::table('baccarat_simulated_betting', function (Blueprint $table) {
            $table->dropColumn([
                'initial_amount',
                'default_bet',
                'stop_win',
                'stop_loss',
                'strategy_types'
            ]);
        });
    }
} 