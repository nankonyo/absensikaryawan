<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('level')->constrained()->nullOnDelete();
            $table->foreignId('sub_department_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['sub_department_id']);
            $table->dropColumn(['department_id', 'sub_department_id']);
        });
    }
};
