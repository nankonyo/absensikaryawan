# Perbaikan Tabel Global + Atasan Dinamis Berdasarkan Organisasi

- **Tipe**: feature + ui
- **Waktu**: 2026-11-02T14:27:10+07:00
- **Tags**: tabel, uiux, user, atasan, alpine, validasi

## Ringkasan

Tabel daftar di seluruh aplikasi memakai gaya baru yang mengikuti komponen `TableView`
dari project `jual-beli-mobil`: header rata kiri non-kapital, padding ringkas `px-4 py-2`,
baris bergaris tipis, hover, dan scroll horizontal.

Form user: atasan wajib untuk semua peran kecuali level teratas (admin/direksi), dan pilihannya
dinamis — hanya user dengan level lebih tinggi di cabang yang sama (sub-departemen, pimpinan
departemen, atau level perusahaan).

## Files

- `resources/css/app.css`
- `resources/js/app.js`
- `resources/views/admin/users/partials/org-fields.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `app/Http/Controllers/Admin/UserController.php`
- `lang/id/ui.php`, `lang/en/ui.php`
- 15 view daftar (`table-view` class, `overflow-x-auto`, `divide-slate-100`)
- `tests/Feature/Admin/UserSupervisorTest.php` (baru)
- `tests/Feature/Admin/UserOrganizationTest.php`, `UserEmployeeIdTest.php` (penyesuaian)
- `public/build/*` (rebuild)

## Ubah

- `app.css`: aturan `.table-view` untuk tabel, header, sel, border baris, hover.
- Semua tabel daftar: tambah class `table-view`; wrapper `overflow-hidden` → `overflow-x-auto`;
  `divide-gray-200` → `divide-slate-100`.
- `app.js`: komponen Alpine `userForm` — kaskade departemen/sub/jabatan + filter atasan
  berdasarkan level peran dan cabang organisasi.
- `UserController`: `picker` data untuk Alpine; validasi `supervisor_id` wajib bila level peran > 1,
  dan atasan harus punya level lebih tinggi.
- Form create/edit: `x-data="userForm(...)"`, select peran terhubung, select atasan dinamis,
  label berubah wajib/opsional, disembunyikan untuk peran teratas.
- Lang: `supervisor_required`, `supervisor_level_invalid`.

## Uji

- `php artisan test tests/Feature/Admin` → 34 deprecated (PDO warning), 198 assertions, exit 0.
- `php artisan test` → 83 deprecated, 1 passed, 883 assertions, exit 0.
- `npm run build` → sukses.
