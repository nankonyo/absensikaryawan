<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ActivityReportMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ActivityReportMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_report_is_sent_to_each_members_supervisor(): void
    {
        Mail::fake();

        $supervisor = User::factory()->create([
            'name' => 'Atasan Uji',
            'email' => 'atasan@example.com',
        ]);
        $member = User::factory()->create([
            'name' => 'Member Uji',
            'email' => 'member@example.com',
            'supervisor_id' => $supervisor->id,
        ]);

        $projectId = DB::table('internal_projects')->insertGetId([
            'name' => 'Project Laporan',
            'owner_id' => $supervisor->id,
            'status' => 'in_progress',
            'progress' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('internal_activity_logs')->insert([
            'user_id' => $member->id,
            'project_id' => $projectId,
            'type' => 'task',
            'action' => 'updated',
            'description' => 'Mengerjakan modul laporan',
            'activity_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sent = app(ActivityReportMailService::class)->send('daily');

        $this->assertSame(1, $sent);
        $this->assertDatabaseHas('internal_email_logs', [
            'recipient_email' => 'atasan@example.com',
            'status' => 'sent',
        ]);
    }

    public function test_activity_report_sends_nothing_without_activity(): void
    {
        Mail::fake();

        $sent = app(ActivityReportMailService::class)->send('daily');

        $this->assertSame(0, $sent);
        $this->assertDatabaseCount('internal_email_logs', 0);
    }
}
