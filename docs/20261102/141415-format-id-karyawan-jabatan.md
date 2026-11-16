# Format Baru ID Karyawan dengan Kode Jabatan

- **Tipe**: feature
- **Waktu**: 2026-11-02T14:14:15+07:00
- **Tags**: user, employee-id, format, seeder

## Ringkasan

Format ID karyawan diubah menjadi `{kode departemen}-{kode sub-departemen}-{kode jabatan}-{6 digit urut}`,
contoh `ENG-SD-SE-000001`. Kode sub memakai suffix (`ENG-SD` → `SD`), kode jabatan memakai bagian
sebelum strip (`MGR-ENG` → `MGR`, `SPV-ENG-SD` → `SPV`). User tanpa departemen tetap prefix `ADM`,
mis. `ADM-DIR-000002`. Nomor urut global diambil dari 6 digit terakhir ID terbesar lalu +1.

## Files

- `app/Models/User.php`
- `app/Http/Controllers/Admin/UserController.php`
- `database/seeders/UserSeeder.php`
- `lang/id/ui.php`
- `lang/en/ui.php`
- `tests/Feature/Admin/UserEmployeeIdTest.php`

## Ubah

- `User::generateEmployeeId()`: tambah parameter `$positionId`, susun bagian dengan `-`, 6 digit di akhir.
- `UserController`: kirim `position_id` saat generate ID.
- `UserSeeder`: kirim `position_id` ke generator.
- `lang/{id,en}/ui.php`: teks bantuan ID menyebut format + contoh.
- Test: regex format baru, prefix ADM, urutan global, ID tetap saat edit, format seed.

## Uji

- `php artisan test tests/Feature/Admin/UserEmployeeIdTest.php` → 6 test, 95 assertions, exit 0.
- `php artisan test` → 77 deprecated (PDO warning), 1 passed, 868 assertions, exit 0.
- Catatan: user lama di DB tetap memakai ID lama (`EMPxxx`); jalankan `php artisan migrate:fresh --seed` untuk regenerasi.
