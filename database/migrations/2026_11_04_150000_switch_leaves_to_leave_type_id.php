<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->foreignId('leave_type_id')
                ->nullable()
                ->after('days')
                ->constrained('leave_types')
                ->nullOnDelete();
        });

        // Map legacy codes to the new master ids before dropping the code column.
        foreach (DB::table('leave_types')->select('id', 'code')->get() as $type) {
            DB::table('leaves')
                ->where('type', $type->code)
                ->update(['leave_type_id' => $type->id]);
        }

        // Keep unknown legacy values instead of losing the request data.
        $unknown = DB::table('leaves')
            ->whereNull('leave_type_id')
            ->distinct()
            ->pluck('type');

        foreach ($unknown as $legacyType) {
            $id = DB::table('leave_types')->insertGetId([
                'code' => (string) $legacyType,
                'name' => Str::headline((string) $legacyType),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('leaves')
                ->where('type', $legacyType)
                ->update(['leave_type_id' => $id]);
        }

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
        });

        foreach (DB::table('leave_types')->select('id', 'name')->get() as $type) {
            DB::table('leave_types')
                ->where('id', $type->id)
                ->update(['code' => 'type-'.$type->id]);
        }

        Schema::table('leaves', function (Blueprint $table) {
            $table->string('type', 50)->nullable();
        });

        foreach (DB::table('leaves')->select('id', 'leave_type_id')->get() as $leave) {
            $code = DB::table('leave_types')->where('id', $leave->leave_type_id)->value('code');
            DB::table('leaves')->where('id', $leave->id)->update(['type' => $code]);
        }

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leave_type_id');
        });
    }
};
