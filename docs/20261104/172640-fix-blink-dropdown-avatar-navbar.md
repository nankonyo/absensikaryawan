Judul: Hilangkan blink dropdown avatar navbar saat reload
Tipe: fix
Waktu ISO: 2026-11-04T17:26:40+07:00
Files:
- resources/views/components/navbar.blade.php
- tests/Feature/Admin/AdminTableViewTest.php
Tags: navbar, dropdown, alpine, x-cloak, blink

Ringkasan:
Dropdown avatar di navbar sempat terlihat terbuka lalu tertutup saat reload karena elemen x-show dirender tanpa x-cloak. Alpine baru berjalan setelah HTML tampil, sehingga menu sempat tampil sekejap. x-cloak menahan elemen tetap display:none sampai Alpine siap.

Ubah:
- resources/views/components/navbar.blade.php: tambah x-cloak pada panel dropdown user.
- tests/Feature/Admin/AdminTableViewTest.php: tambah test memastikan markup dropdown memakai x-cloak.

Uji:
- php artisan test --filter=AdminTableViewTest: exit 0, 9 test deprecated (konstanta PDO), 53 assertion, tidak ada failure.
