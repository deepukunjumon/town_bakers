<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action');
            $table->string('table');
            $table->uuid('record_id');
            $table->text('description')->nullable();
            $table->text('comments')->nullable();
            $table->foreignUuid('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['table', 'record_id']);
            $table->index('action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
}; 