<?php

use App\Models\System\Installation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Installation::executeSchemaDatabase();
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('contents');
        Schema::dropIfExists('administrations');
    }
};
