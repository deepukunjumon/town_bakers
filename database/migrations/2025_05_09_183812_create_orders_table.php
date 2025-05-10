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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id');
            $table->uuid('employee_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->date('delivery_date');
            $table->float('total_amount')->default(0.00);
            $table->float('advance_amount')->nullable();
            $table->tinyInteger('payment_status')->default(0)->comment('2: full amount paid, 1: advance only paid, 0: unpaid, -1: refunded');
            $table->tinyInteger('status')->default(0)->comment('1: delivered, 0: pending, -1: cancelled'); 
            $table->uuid('created_by'); // Admin or Branch user
            $table->timestamps();

            // Foreign key constraints (optional)
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
