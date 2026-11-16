<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->string('category', 20)->default('cuti')->after('name');
            $table->boolean('requires_attachment')->default(false)->after('category');
            $table->boolean('requires_reason')->default(true)->after('requires_attachment');
            $table->boolean('deducts_quota')->default(false)->after('requires_reason');
            $table->unsignedSmallInteger('quota_days')->nullable()->after('deducts_quota');
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->dropColumn('requires_attachment');
            $table->dropColumn('requires_reason');
            $table->dropColumn('deducts_quota');
            $table->dropColumn('quota_days');
        });
    }
};
