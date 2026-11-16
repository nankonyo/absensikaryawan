<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->default(0)->after('name');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('sub_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedTinyInteger('level')->default(5);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('supervisor_id')->constrained()->nullOnDelete();
            $table->foreignId('sub_department_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->after('sub_department_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['sub_department_id']);
            $table->dropForeign(['position_id']);
            $table->dropColumn(['department_id', 'sub_department_id', 'position_id']);
        });

        Schema::dropIfExists('positions');
        Schema::dropIfExists('sub_departments');
        Schema::dropIfExists('departments');

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
