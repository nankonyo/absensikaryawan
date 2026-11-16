<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ui_translation_files_have_identical_keys(): void
    {
        $id = $this->flatten(require lang_path('id/ui.php'));
        $en = $this->flatten(require lang_path('en/ui.php'));

        sort($id);
        sort($en);

        $this->assertSame($id, $en, 'lang/id/ui.php and lang/en/ui.php must define the same keys.');
    }

    public function test_every_static_ui_key_used_in_views_exists_in_both_locales(): void
    {
        $keys = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all("/__\\('ui\\.([A-Za-z0-9_.]+)'/", $file->getContents(), $matches);
            $keys = array_merge($keys, $matches[1]);
        }

        $this->assertNotEmpty($keys);

        foreach (array_unique($keys) as $key) {
            if (str_ends_with($key, '.')) {
                continue;
            }

            $this->assertTrue(Lang::has('ui.'.$key, 'id'), "Missing id translation for ui.$key");
            $this->assertTrue(Lang::has('ui.'.$key, 'en'), "Missing en translation for ui.$key");
        }
    }

    public function test_indonesian_email_labels_use_email_not_surel(): void
    {
        $this->assertSame('Email', __('ui.email', [], 'id'));

        $files = array_merge([lang_path('id.json')], File::glob(lang_path('id/*.php')));

        foreach ($files as $file) {
            $this->assertStringNotContainsStringIgnoringCase(
                'surel',
                File::get($file),
                basename($file).' must use "email", not "surel".',
            );
        }
    }

    public function test_locale_switch_changes_rendered_language(): void
    {
        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($admin)
            ->get(route('locale.switch', 'en'))
            ->assertRedirect();

        $this->assertSame('en', session('locale'));

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('ui.total_employees', [], 'en'));
        $response->assertDontSee(__('ui.total_employees', [], 'id'));
    }

    public function test_indonesian_is_used_by_default(): void
    {
        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('ui.total_employees', [], 'id'));
    }

    private function flatten(array $translations, string $prefix = ''): array
    {
        $keys = [];

        foreach ($translations as $key => $value) {
            $full = $prefix === '' ? $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flatten($value, $full));
            } else {
                $keys[] = $full;
            }
        }

        return $keys;
    }
}
