<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('sub_departments', 'units');

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('sub_department_id', 'unit_id');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->renameColumn('sub_department_id', 'unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->renameColumn('unit_id', 'sub_department_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('unit_id', 'sub_department_id');
        });

        Schema::rename('units', 'sub_departments');
    }
};
