# ID Karyawan Otomatis (Kode Dept + Sub Dept + 6 Digit)

- **Tipe**: feature
- **Waktu**: 2026-11-02T13:49:26+07:00
- **Tags**: user, employee-id, seeder, admin

## Ringkasan

ID karyawan tidak lagi diisi manual. Format: `{kode departemen}{suffix kode sub-departemen}{nomor urut 6 digit}`,
contoh `ENGSD000001`. User tanpa departemen memakai prefix `ADM` (`ADM000001`). Nomor urut global
(tidak reset per departemen). Semua seeder user memakai generator yang sama.

## Files

- `app/Models/User.php`
- `app/Http/Controllers/Admin/UserController.php`
- `database/seeders/UserSeeder.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `lang/id/ui.php`
- `lang/en/ui.php`
- `tests/Feature/Admin/UserEmployeeIdTest.php`

## Ubah

- `app/Models/User.php`: tambah `generateEmployeeId()` — ambil kode departemen, suffix sub-departemen
  (setelah `-`, mis. `ENG-SD` → `SD`), nomor urut global 6 digit.
- `app/Http/Controllers/Admin/UserController.php`: `employee_id` dihapus dari validasi & payload;
  dibuat otomatis saat `store`, tetap saat `update`.
- `database/seeders/UserSeeder.php`: `EMP%03d` diganti `User::generateEmployeeId()`.
- `resources/views/admin/users/create.blade.php`: input ID diganti teks info otomatis.
- `resources/views/admin/users/edit.blade.php`: input ID jadi read-only.
- `lang/id/ui.php`, `lang/en/ui.php`: `employee_id_required` → `employee_id_auto`.
- `tests/Feature/Admin/UserEmployeeIdTest.php`: test format, prefix ADM, urutan global, ID tetap saat edit, format seed, render form.

## Uji

- `php artisan test tests/Feature/Admin/UserEmployeeIdTest.php` → 6 deprecated (PDO warning), 94 assertions, exit 0.
- `php artisan test` → 68 deprecated, 1 passed, 838 assertions, exit 0.
- Catatan: data lama di DB tidak berubah; jalankan `php artisan migrate:fresh --seed` (menghapus data) untuk menerapkan format baru.
