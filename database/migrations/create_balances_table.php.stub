<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function O21\LaravelWallet\ConfigHelpers\table_name;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(table_name('balances'), function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            $table->decimal('value', 16, 8)->default(0);
            $table->decimal('value_pending', 16, 8)->default(0);
            $table->decimal('value_on_hold', 16, 8)->default(0);
            $table->string('currency', 10)->index();
            $table->unique(['payable_id', 'payable_type', 'currency'], 'unique_balance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(table_name('balances'));
    }
};
