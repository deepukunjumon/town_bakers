<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id');
            $table->uuid('employee_id');
            $table->uuid('item_id');
            $table->string('trip_code')->nullable();
            $table->float('quantity', 8, 2)->change();
            $table->date('date')->default(DB::raw('CURRENT_DATE'));
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
