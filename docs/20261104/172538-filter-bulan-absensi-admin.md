Judul: Filter bulan-tahun absensi admin otomatis terisi bulan berjalan
Tipe: change
Waktu ISO: 2026-11-04T17:25:38+07:00
Files:
- app/Http/Controllers/Admin/AttendanceController.php
- resources/views/admin/attendances/index.blade.php
- tests/Feature/Admin/AdminTableViewTest.php
Tags: admin, attendances, filter, bulan, regression-test

Ringkasan:
Filter bulan-tahun di /admin/attendances kini otomatis memakai bulan dan tahun berjalan saat halaman dibuka tanpa query month. Daftar absensi langsung menampilkan data bulan ini. Reset mengembalikan filter ke bulan berjalan, bukan menghapus bulan.

Ubah:
- app/Http/Controllers/Admin/AttendanceController.php: nilai default query month menjadi now()->format('Y-m'). Query month kosong eksplisit tetap berarti tanpa filter bulan.
- resources/views/admin/attendances/index.blade.php: tombol reset tampil saat pencarian/status aktif atau bulan berbeda dari bulan berjalan.
- tests/Feature/Admin/AdminTableViewTest.php: test lama "shows all months by default" diganti "defaults to the current month" sesuai requirement baru. Test memastikan value bulan berjalan terisi, data bulan ini tampil, data bulan lalu tidak tampil, dan filter bulan lain tetap bekerja.

Uji:
- php artisan test --filter=AdminTableViewTest: exit 0, 8 test deprecated (konstanta PDO), 51 assertion, tidak ada failure.
