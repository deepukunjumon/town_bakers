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
        Schema::table('branches', function (Blueprint $table) {
            $table->string('email')->nullable()->after('mobile');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('username');
            $table->string('mobile')->nullable()->after('name');
            $table->string('email')->nullable()->after('mobile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('email');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('mobile');
            $table->dropColumn('email');
        });
    }
};
