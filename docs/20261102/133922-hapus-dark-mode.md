# Hapus Dark Mode, Terapkan Light Mode Saja

- **Tipe**: refactor / UI
- **Waktu**: 2026-11-02T13:39:22+07:00
- **Tags**: tailwind, blade, tema, light-mode

## Ringkasan

Sistem tema gelap/terang dihapus. Semua halaman kini selalu memakai mode terang.
Kelas utilitas `dark:` dibersihkan dari seluruh Blade view, toggle dark mode di navbar
dihapus, dan Tailwind dikonfigurasi `darkMode: 'class'` sehingga varian `dark:` sisa
(vendor pagination) tidak pernah aktif karena class `.dark` tidak pernah dipasang.

## Files

- `tailwind.config.js`
- `resources/views/layouts/app.blade.php`
- `resources/views/**/*.blade.php` (56 file, pembersihan token `dark:`)
- `tests/Feature/RemovedFeaturesTest.php`
- `public/build/*` (hasil build ulang)

## Ubah

- `tailwind.config.js`: tambah `darkMode: 'class'` agar varian `dark:` tidak mengikuti OS.
- `resources/views/layouts/app.blade.php`: hapus state Alpine `darkMode`, `:class="{ 'dark': ... }"`, dan tombol toggle dark mode.
- `resources/views/**/*.blade.php`: hapus seluruh token `dark:*` (1228 token).
- `tests/Feature/RemovedFeaturesTest.php`: tambah test regresi `test_dark_mode_is_removed`.

## Uji

- `php artisan test` → exit 0, 1 passed, 63 deprecated (peringatan `PDO::MYSQL_ATTR_SSL_CA`), 748 assertions.
- `npm run build` → sukses; CSS hasil build tidak lagi punya `prefers-color-scheme`.
- Grep verifikasi: 0 token `dark:` dan 0 `darkMode` di `resources/` (kecuali `welcome.blade.php` yang tidak dipakai route).
