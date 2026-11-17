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
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('servers');
    }
};
