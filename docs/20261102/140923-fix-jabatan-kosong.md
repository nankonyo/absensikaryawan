# Perbaikan Jabatan Kosong Setelah Pilih Departemen/Sub-Departemen

- **Tipe**: bugfix
- **Waktu**: 2026-11-02T14:09:23+07:00
- **Tags**: user, jabatan, seeder, migrasi, kaskade

## Ringkasan

Penyebab: migrasi `2026_11_02_000004` belum dijalankan di database lokal, sehingga kolom
`positions.department_id`/`sub_department_id` tidak ada dan semua jabatan terbaca tanpa relasi
(lalu terfilter habis). Selain itu baris lama `MGR`/`SPV` masih global.

Perbaikan: migrasi dijalankan, `PositionSeeder` dijalankan, dan seeder kini memetakan ulang user
lama dari `MGR`/`SPV` ke jabatan per unit (`MGR-ENG`, `SPV-ENG-SD`) lalu menghapus baris legacy.

## Files

- `database/seeders/PositionSeeder.php`
- `tests/Feature/Admin/UserOrganizationTest.php`
- DB lokal: migrasi `2026_11_02_000004` + seed `PositionSeeder`

## Ubah

- `PositionSeeder`: tambah `remapLegacyPositions()` — user pemakai `MGR`/`SPV` dipindah ke
  jabatan sesuai departemen/sub-departemen, baris legacy dihapus.
- `UserOrganizationTest`: tambah test regresi remap legacy.
- DB lokal: kolom relasi ditambahkan, 29 jabatan terisi, 0 baris legacy.

## Uji

- `php artisan test tests/Feature/Admin/UserOrganizationTest.php` → 8 test, 25 assertions, exit 0.
- `php artisan test` → 77 deprecated (PDO warning), 1 passed, 867 assertions, exit 0.
- Cek data:
  - ENG tanpa sub → `Manager Engineering`
  - ENG-SD → `Software Engineer`, `UI/UX Designer`, `Supervisor Software Development`
  - tanpa departemen → `Direktur Utama`, `General Manager`
  - manager sudah memakai `MGR-ENG`…`MGR-COR`
