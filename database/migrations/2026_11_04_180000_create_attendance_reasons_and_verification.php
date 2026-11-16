<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('attendance_reasons')->insert([
            ['name' => 'WFO', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'WFH', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tugas dalam kota', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tugas pelatihan', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tugas keluar kota', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('attendance_reason_id')->nullable()->after('status')
                ->constrained('attendance_reasons')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->after('attendance_reason_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('verification_notes')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropConstrainedForeignId('attendance_reason_id');
            $table->dropColumn(['verified_at', 'verification_notes']);
        });

        Schema::dropIfExists('attendance_reasons');
    }
};
