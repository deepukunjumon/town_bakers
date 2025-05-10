<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerAndDeliveryFieldsToOrdersTable extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_name');
            $table->string('customer_mobile');
            $table->string('customer_email')->nullable();
            $table->time('delivery_time');
            $table->time('delivered_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_mobile', 'customer_email', 'delivery_time', 'delivered_time']);
        });
    }
}

