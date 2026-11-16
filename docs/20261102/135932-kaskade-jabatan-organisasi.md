# Kaskade Departemen, Sub-Departemen, dan Jabatan

- **Tipe**: feature
- **Waktu**: 2026-11-02T13:59:32+07:00
- **Tags**: user, organisasi, jabatan, alpine, validasi

## Ringkasan

Form user kini memfilter pilihan secara berjenjang: sub-departemen hanya milik departemen terpilih,
jabatan hanya milik departemen/sub-departemen terpilih. Relasi jabatan ke organisasi ditambahkan
di tabel `positions` (`department_id`, `sub_department_id`) beserta seed dan validasi server.

## Files

- `database/migrations/2026_11_02_000004_add_organization_to_positions_table.php`
- `app/Models/Position.php`
- `database/seeders/PositionSeeder.php`
- `database/seeders/UserSeeder.php`
- `app/Http/Controllers/Admin/UserController.php`
- `app/Http/Controllers/Admin/PositionController.php`
- `resources/js/app.js`
- `resources/views/admin/users/partials/org-fields.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/master/positions/index.blade.php`
- `tests/Feature/Admin/UserOrganizationTest.php`
- `public/build/*` (rebuild)

## Ubah

- Migrasi baru: `positions.department_id`, `positions.sub_department_id` (nullable, nullOnDelete).
- `PositionSeeder`: jabatan dipecah per unit — `MGR-*` per departemen, `SPV-*` per sub-departemen,
  staff (`SE`, `QA`, `DE`, dst.) terikat sub-departemen, `DIR`/`GM` tetap global.
- `UserSeeder`: manager pakai `MGR-{dept}`, supervisor pakai `SPV-{subdept}`.
- `UserController`: validasi `position_id` harus cocok dept+sub, `sub_department_id` harus milik dept.
- `app.js`: komponen Alpine `orgPicker` — filter sub-departemen dan jabatan, reset pilihan tidak valid.
- Partial `org-fields.blade.php`: tiga select berantai dipakai form create & edit.
- Master jabatan: tambah pilihan departemen/sub-departemen + validasi relasi.

## Uji

- `php artisan test tests/Feature/Admin/UserOrganizationTest.php` → 7 test, 22 assertions, exit 0.
- `php artisan test` → 76 deprecated (PDO warning), 1 passed, 864 assertions, exit 0.
- `npm run build` → sukses.
- Catatan: jalankan `php artisan migrate:fresh --seed` agar kolom baru dan jabatan per unit terisi.
