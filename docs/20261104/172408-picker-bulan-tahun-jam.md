Judul: Picker baru diterapkan ke bulan-tahun, jam, dan tanggal lengkap
Tipe: feature
Waktu ISO: 2026-11-04T17:24:08+07:00
Files:
- resources/js/datepicker.js
- resources/css/datepicker.css
- public/build/**
Tags: ui, ux, datepicker, flatpickr, month, time

Ringkasan:
Picker modern sekarang menangani semua tipe input waktu di aplikasi, bukan hanya type="date". Cakupan: tanggal saja (date), bulan-tahun saja (month), jam saja (time), dan tanggal + jam (datetime-local). Semua filter dan form field otomatis ikut karena enhancement berjalan global, termasuk filter bulan-tahun di /admin/attendances dan /karyawan/attendance/history serta jam kerja di /admin/settings.

Ubah:
- resources/js/datepicker.js: konfigurasi per tipe input. Month memakai plugin monthSelect (grid 12 bulan + navigasi tahun, simpan format Y-m). Time memakai Flatpickr time-only 24 jam (format H:i, tanpa kalender). Datetime-local memakai kalender + jam (format Y-m-dTH:i). Alt input tetap menampilkan format ramah: d M Y, F Y, H:i, d M Y H:i. Selector global mencakup date, month, time, datetime-local.
- resources/css/datepicker.css: tema grid bulan-tahun (bulan terpilih biru, hari ini soft blue), tema popup jam (input jam/menit rounded), lebar popup jam lebih ringkas, ikon jam untuk field waktu.
- public/build/**: hasil npm run build.

Audit input:
- date: admin/users create+edit, attribute fields, contract fields, admin/audit-logs filter, admin/reports, atasan/team-attendance filter, karyawan/leave create+edit.
- month: admin/attendances filter, karyawan/attendance/history filter.
- time: admin/settings work_start_time dan work_end_time.
- datetime-local: belum ada field di aplikasi, dukungan tetap disiapkan.

Uji:
- npm run build: sukses, exit 0.
- node --check resources/js/datepicker.js: OK.
- php artisan test --filter=AdminTableViewTest: 8 test deprecated (konstanta PDO), 51 assertion, tidak ada failure. Test perilaku daftar absensi semua bulan default tetap lulus.
- Verifikasi headless Chromium memakai bundle hasil build: popup bulan-tahun, popup jam, popup tanggal+jam tampil sesuai tema, locale Indonesia.
- Cek nilai hidden input lewat DOM setelah init: date=2026-11-04, month=2026-11, time=08:00, datetime-local=2026-11-04T08:00. Format submit tidak berubah.
