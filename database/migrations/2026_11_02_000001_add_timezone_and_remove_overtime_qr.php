<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'timezone'],
            [
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'description' => 'Application timezone',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('settings')->where('key', 'overtime_rate_per_hour')->delete();

        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('overtimes');

        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'qr_token')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('qr_token');
            });
        }

        if (Schema::hasTable('payrolls') && Schema::hasColumn('payrolls', 'overtime_bonus')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->dropColumn('overtime_bonus');
            });
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'timezone')->delete();

        DB::table('settings')->updateOrInsert(
            ['key' => 'overtime_rate_per_hour'],
            [
                'value' => '50000',
                'type' => 'integer',
                'description' => 'Overtime rate per hour (IDR)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if (Schema::hasTable('attendances') && !Schema::hasColumn('attendances', 'qr_token')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->string('qr_token')->nullable();
            });
        }

        if (Schema::hasTable('payrolls') && !Schema::hasColumn('payrolls', 'overtime_bonus')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->decimal('overtime_bonus', 15, 2)->default(0);
            });
        }
    }
};
