<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('document')->nullable();
            $table->timestamps();
        });

        $users = DB::table('users')
            ->whereNotNull('contract_start_date')
            ->orWhereNotNull('contract_end_date')
            ->orWhereNotNull('contract_document')
            ->get(['id', 'contract_start_date', 'contract_end_date', 'contract_document']);

        foreach ($users as $user) {
            DB::table('user_contracts')->insert([
                'user_id' => $user->id,
                'start_date' => $user->contract_start_date,
                'end_date' => $user->contract_end_date,
                'document' => $user->contract_document,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['contract_start_date', 'contract_end_date', 'contract_document']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->string('contract_document')->nullable();
        });

        DB::table('user_contracts')->orderBy('id')->each(function ($contract) {
            DB::table('users')->where('id', $contract->user_id)->update([
                'contract_start_date' => $contract->start_date,
                'contract_end_date' => $contract->end_date,
                'contract_document' => $contract->document,
            ]);
        });

        Schema::dropIfExists('user_contracts');
    }
};
