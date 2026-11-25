<?php

use App\Models\System\Installation;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * All application tables are managed centrally by Installation.php.
     */
    public function up(): void
    {
        Installation::executeSchemaDatabase();
    }

    public function down(): void
    {
        // Schema is intentionally managed by Installation::executeSchemaDatabase().
    }
};
