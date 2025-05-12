<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivered_time');
            $table->dateTime('delivered_at')->nullable()->after('delivery_time');
            $table->uuid('delivered_by')->nullable()->after('delivered_at');
            $table->foreign('delivered_by')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->time('delivered_time')->nullable()->after('delivery_time');
            $table->dropColumn('delivered_at');
            $table->dropForeign(['delivered_by']);
            $table->dropColumn('delivered_by');
        });
    }
};
