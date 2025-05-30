<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['branch_id', 'status', 'payment_status']);
            $table->index(['delivery_date', 'status']);
            $table->index(['customer_name', 'customer_email', 'customer_mobile']);
        });

        // Add indexes to stock_items table
        Schema::table('stock_items', function (Blueprint $table) {
            $table->index(['trip_id', 'item_id']);
            $table->index('quantity');
        });

        // Add indexes to trips table
        Schema::table('trips', function (Blueprint $table) {
            $table->index(['branch_id', 'date']);
            $table->index('employee_id');
        });

        // Add indexes to items table
        Schema::table('items', function (Blueprint $table) {
            $table->index(['name', 'status']);
            $table->index('category');
        });

        // Add indexes to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['branch_id', 'status']);
            $table->index('employee_code');
        });
    }

    public function down(): void
    {
        // Remove indexes from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'status', 'payment_status']);
            $table->dropIndex(['delivery_date', 'status']);
            $table->dropIndex(['customer_name', 'customer_email', 'customer_mobile']);
        });

        // Remove indexes from stock_items table
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropIndex(['trip_id', 'item_id']);
            $table->dropIndex(['quantity']);
        });

        // Remove indexes from trips table
        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'date']);
            $table->dropIndex(['employee_id']);
        });

        // Remove indexes from items table
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['name', 'status']);
            $table->dropIndex(['category']);
        });

        // Remove indexes from employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'status']);
            $table->dropIndex(['employee_code']);
        });
    }
};
