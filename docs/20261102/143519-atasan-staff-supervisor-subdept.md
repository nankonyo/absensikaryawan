# Atasan Staff: Hanya Supervisor Sub-Departemen Terpilih

- **Tipe**: revision
- **Waktu**: 2026-11-02T14:35:19+07:00
- **Tags**: user, atasan, supervisor, validasi, alpine

## Ringkasan

Daftar atasan kini mengikuti satu tingkat di atas peran: staff → supervisor, supervisor → manager,
manager → general manager, general manager → direksi. Untuk staff, pilihannya hanya supervisor
dari sub-departemen yang dipilih (bukan manager/GM/direksi). Server menolak atasan lintas sub
atau beda tingkat.

## Files

- `resources/js/app.js`
- `app/Http/Controllers/Admin/UserController.php`
- `lang/id/ui.php`, `lang/en/ui.php`
- `tests/Feature/Admin/UserSupervisorTest.php`
- `tests/Feature/Admin/UserOrganizationTest.php`
- `tests/Feature/Admin/UserEmployeeIdTest.php`
- `public/build/*` (rebuild)

## Ubah

- `app.js` `availableSupervisors`: target level = level peran - 1, filter sub-departemen terpilih
  (staff), departemen (supervisor), atau level perusahaan (manager/GM).
- `UserController` `supervisorRules`: level atasan harus tepat satu tingkat di atas; cek cabang
  (sub-departemen terpilih, pimpinan departemen, atau level perusahaan).
- Lang: `supervisor_level_invalid`, `supervisor_branch_invalid`.
- Test: staff pakai supervisor sub, tolak manager, tolak supervisor sub lain; penyesuaian helper.

## Uji

- `php artisan test tests/Feature/Admin` → 36 deprecated (PDO warning), 202 assertions, exit 0.
- `php artisan test` → 85 deprecated, 1 passed, 887 assertions, exit 0.
- `npm run build` → sukses.
