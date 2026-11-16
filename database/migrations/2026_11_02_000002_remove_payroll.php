<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->whereIn('key', [
            'late_penalty_per_minute',
            'leave_deduction_per_day',
        ])->delete();

        Schema::dropIfExists('payrolls');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'base_salary')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('base_salary');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'base_salary')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('base_salary', 15, 2)->default(0)->after('employee_id');
            });
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'late_penalty_per_minute'],
            [
                'value' => '5000',
                'type' => 'integer',
                'description' => 'Penalty amount per minute late (IDR)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('settings')->updateOrInsert(
            ['key' => 'leave_deduction_per_day'],
            [
                'value' => '100000',
                'type' => 'integer',
                'description' => 'Leave deduction per day (IDR)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
