Judul: Menu Pengaturan dipindah ke paling bawah sidebar admin
Tipe: change
Waktu ISO: 2026-11-04T17:27:40+07:00
Files:
- resources/views/layouts/sidebar/admin.blade.php
- tests/Feature/SidebarTest.php
Tags: sidebar, menu, settings, admin

Ringkasan:
Menu Pengaturan pada sidebar admin dipindah dari posisi setelah Laporan ke urutan paling bawah, setelah menu Tipe Cuti. Isi dan ikon menu tidak berubah.

Ubah:
- resources/views/layouts/sidebar/admin.blade.php: blok nav-link admin.settings dipindah ke akhir daftar menu.
- tests/Feature/SidebarTest.php: tambah test memastikan URL menu tipe cuti muncul sebelum URL menu pengaturan pada sidebar admin.

Uji:
- php artisan test --filter=SidebarTest: exit 0, 2 test deprecated (konstanta PDO), 6 assertion, tidak ada failure.
