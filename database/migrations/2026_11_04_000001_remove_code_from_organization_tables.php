<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropUnique('sub_departments_code_unique');
            $table->dropColumn('code');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
        });
    }
};
