<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Installation extends Model
{
    /**
     * Create / update every table required by the InternalApp modules.
     *
     * The method is idempotent: it only creates tables and columns that are
     * still missing, so it is safe to run on an existing installation.
     */
    public static function executeSchemaDatabase(): void
    {
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('is_active')->default(true);
                $table->string('code', 50)->nullable()->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status', 30)->default('planning');
                $table->string('priority', 20)->default('medium');
                $table->unsignedSmallInteger('progress')->default(0);
                $table->decimal('budget', 15, 2)->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('status');
                $table->index('created_at');
                $table->index('updated_at');
            });
        } else {
            Schema::table('projects', function (Blueprint $table) {
                if (! Schema::hasColumn('projects', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (! Schema::hasColumn('projects', 'code')) {
                    $table->string('code', 50)->nullable();
                }
                if (! Schema::hasColumn('projects', 'manager_id')) {
                    $table->unsignedBigInteger('manager_id')->nullable();
                    $table->index('manager_id');
                }
                if (! Schema::hasColumn('projects', 'start_date')) {
                    $table->date('start_date')->nullable();
                }
                if (! Schema::hasColumn('projects', 'end_date')) {
                    $table->date('end_date')->nullable();
                }
                if (! Schema::hasColumn('projects', 'status')) {
                    $table->string('status', 30)->default('planning');
                    $table->index('status');
                }
                if (! Schema::hasColumn('projects', 'priority')) {
                    $table->string('priority', 20)->default('medium');
                }
                if (! Schema::hasColumn('projects', 'progress')) {
                    $table->unsignedSmallInteger('progress')->default(0);
                }
                if (! Schema::hasColumn('projects', 'budget')) {
                    $table->decimal('budget', 15, 2)->nullable();
                }
                if (! Schema::hasColumn('projects', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        if (! Schema::hasTable('project_members')) {
            Schema::create('project_members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 50)->nullable();
                $table->timestamps();
                $table->unique(['project_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('project_tasks')) {
            Schema::create('project_tasks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('todo');
                $table->string('priority', 20)->default('medium');
                $table->date('start_date')->nullable();
                $table->date('due_date')->nullable();
                $table->unsignedSmallInteger('progress')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->index('status');
                $table->index('created_at');
                $table->index('updated_at');
            });
        }

        if (! Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('is_active')->default(true);
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('activity_date')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('location')->nullable();
                $table->string('status', 30)->default('planned');
                $table->timestamps();
                $table->softDeletes();
                $table->index('activity_date');
                $table->index('status');
                $table->index('created_at');
                $table->index('updated_at');
            });
        }

        if (! Schema::hasTable('contents')) {
            Schema::create('contents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('is_active')->default(true);
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('type', 30)->default('article');
                $table->string('category', 100)->nullable();
                $table->text('excerpt')->nullable();
                $table->longText('body')->nullable();
                $table->string('status', 20)->default('draft');
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('type');
                $table->index('status');
                $table->index('created_at');
                $table->index('updated_at');
            });
        }

        if (! Schema::hasTable('administrations')) {
            Schema::create('administrations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('is_active')->default(true);
                $table->string('number', 100)->nullable()->unique();
                $table->string('title');
                $table->string('category', 100)->nullable();
                $table->text('description')->nullable();
                $table->string('file_path')->nullable();
                $table->date('document_date')->nullable();
                $table->string('status', 30)->default('active');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index('category');
                $table->index('status');
                $table->index('created_at');
                $table->index('updated_at');
            });
        }
    }
}
