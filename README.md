# Raja HR

Sistem absensi karyawan modern dengan fitur lengkap menggunakan Laravel 12, dilengkapi dengan validasi GPS dan Face Recognition.

## Fitur Utama

### Multi-Role Authentication
- **Direksi**: Full access ke master data, users, attendances, leaves, reports, settings, audit logs. Tidak memiliki atasan, jadi tidak membuka absensi pribadi.
- **General Manager**: Approve/reject cuti, lihat seluruh kehadiran tim lintas departemen, absensi pribadi.
- **Manager**: Approve/reject cuti, lihat tim per departemen, absensi pribadi.
- **Supervisor**: Approve/reject cuti, lihat tim per unit, absensi pribadi.
- **Staff**: Check-in/out attendance, request leave, view personal reports.

### Absensi Real-time dengan Validasi Canggih
- **GPS Validation**: Karyawan hanya bisa absen dalam radius kantor
- **Face Recognition**: Validasi wajah saat check-in/out (dummy API ready for integration)
- **Metadata Lengkap**: Simpan koordinat GPS, foto selfie, face score, alamat

### Manajemen Cuti
- Pengajuan cuti dengan upload file pendukung (PDF/JPG max 5MB)
- Approval flow: Staff → Supervisor → Manager → General Manager
- Feedback notes dari approver

### Laporan & Audit Log
- Laporan absensi bulanan (PDF & Excel)
- Laporan cuti
- Audit log semua aktivitas penting
- Dashboard dengan charts (Chart.js/ApexCharts)

### UI Modern
- Design Notion/Vercel style
- Dark mode support
- Responsive (mobile, tablet, desktop)
- Lucide icons
- TailwindCSS + Alpine.js

## Teknologi Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP 8.2, Laravel 12 |
| Frontend | Blade, TailwindCSS, Alpine.js |
| Database | MySQL/MariaDB |
| PDF | barryvdh/laravel-dompdf |
| Excel | maatwebsite/excel |
| Icons | Lucide Icons |
| Charts | Chart.js / ApexCharts |

## Requirements

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL/MariaDB
- XAMPP / MAMP / Laragon (untuk local development)

## Installation

Lihat [INSTALLATION.md](INSTALLATION.md) untuk panduan instalasi lengkap.

### Quick Start

```bash
# Clone atau copy project
cd /Applications/MAMP/htdocs/raja-hr

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database di .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=raja_hr
DB_USERNAME=root
DB_PASSWORD=your_password

# Run migrations & seeders
php artisan migrate:fresh --seed

# Build assets
npm run build

# Start server
php artisan serve
```

## Akses Demo

Database demo di-reset dengan:

```bash
php artisan migrate:fresh --seed
```

Semua akun memakai password: **`password`**

Struktur seed: **1 Direksi, 1 General Manager, 5 Manager, 10 Supervisor, 50 Staff**.
Setiap unit memiliki 1 supervisor dan 5 staff. Total **68 akun**.

### Ringkasan akses per peran

| Peran | Jumlah | Akses utama |
|-------|-------:|-------------|
| Administrator Sistem | 1 | Admin dashboard, master data, users, attendances, leaves, reports, settings, audit logs. |
| Direksi | 1 | Admin dashboard, master department, master unit, master jabatan, users, attendances, leaves, reports, settings, audit logs. Tidak membuka absensi pribadi karena tidak punya atasan. |
| General Manager | 1 | Dashboard atasan, approvals, team attendance semua departemen, cuti, absensi pribadi. |
| Manager | 5 | Dashboard atasan, approvals, team attendance per departemen, cuti, absensi pribadi. |
| Supervisor | 10 | Dashboard atasan, approvals, team attendance per unit, cuti, absensi pribadi. |
| Staff | 50 | Dashboard karyawan, absensi, riwayat absensi, pengajuan cuti. |

### Akun demo lengkap

| Nama | Peran | Email | Departemen | Unit |
|------|-------|-------|------------|----------------|
| Administrator Sistem | Administrator Sistem | admin@example.com | - | - |
| Budi Santoso | Direksi | direksi@example.com | - | - |
| Siti Rahmawati | General Manager | gm@example.com | - | - |
| Gita Puspita | Manager | manager.consulting@example.com | Consulting & Delivery | - |
| Melati Sari | Manager | manager.corporate@example.com | Corporate Services | - |
| Rendra Wibowo | Manager | manager.data@example.com | Data & AI | - |
| Andi Pratama | Manager | manager.engineering@example.com | Engineering | - |
| Citra Maharani | Manager | manager.infrastructure@example.com | Infrastructure & Cloud | - |
| Haris Fadillah | Supervisor | supervisor.business@example.com | Consulting & Delivery | Business Analysis |
| Iwan Kurnia | Supervisor | supervisor.delivery@example.com | Consulting & Delivery | Project Delivery |
| Putra Mahendra | Supervisor | supervisor.finance@example.com | Corporate Services | Finance & Accounting |
| Rani Anggraini | Supervisor | supervisor.hr@example.com | Corporate Services | Human Resources |
| Naufal Hakim | Supervisor | supervisor.data@example.com | Data & AI | Data Engineering |
| Olivia Chandra | Supervisor | supervisor.datascience@example.com | Data & AI | Data Science |
| Bayu Saputra | Supervisor | supervisor.qa@example.com | Engineering | Quality Assurance |
| Rina Kusuma | Supervisor | supervisor.software@example.com | Engineering | Software Development |
| Dimas Aryo | Supervisor | supervisor.devops@example.com | Infrastructure & Cloud | DevOps & Cloud |
| Eko Prasetyo | Supervisor | supervisor.network@example.com | Infrastructure & Cloud | Network & Security |
| Ayu Wulandari | Staff | ayu.wulandari@example.com | Consulting & Delivery | Business Analysis |
| Bima Kurniawan | Staff | bima.kurniawan@example.com | Consulting & Delivery | Business Analysis |
| Cindy Halim | Staff | cindy.halim@example.com | Consulting & Delivery | Business Analysis |
| Doni Setiabudi | Staff | doni.setiabudi@example.com | Consulting & Delivery | Business Analysis |
| Elsa Novita | Staff | elsa.novita@example.com | Consulting & Delivery | Business Analysis |
| Farhan Maulana | Staff | farhan.maulana@example.com | Consulting & Delivery | Project Delivery |
| Grace Simanjuntak | Staff | grace.simanjuntak@example.com | Consulting & Delivery | Project Delivery |
| Hana Puspasari | Staff | hana.puspasari@example.com | Consulting & Delivery | Project Delivery |
| Irfan Setiawan | Staff | irfan.setiawan@example.com | Consulting & Delivery | Project Delivery |
| Jessica Tan | Staff | jessica.tan@example.com | Consulting & Delivery | Project Delivery |
| Anisa Putri | Staff | anisa.putri@example.com | Corporate Services | Finance & Accounting |
| Bagus Wicaksono | Staff | bagus.wicaksono@example.com | Corporate Services | Finance & Accounting |
| Cahya Lestari | Staff | cahya.lestari@example.com | Corporate Services | Finance & Accounting |
| Dimas Anggara | Staff | dimas.anggara@example.com | Corporate Services | Finance & Accounting |
| Erika Sari | Staff | erika.sari@example.com | Corporate Services | Finance & Accounting |
| Fitri Handayani | Staff | fitri.handayani@example.com | Corporate Services | Human Resources |
| Galih Permana | Staff | galih.permana@example.com | Corporate Services | Human Resources |
| Hesti Wulandari | Staff | hesti.wulandari@example.com | Corporate Services | Human Resources |
| Ivan Kurniawan | Staff | ivan.kurniawan@example.com | Corporate Services | Human Resources |
| Jihan Aulia | Staff | jihan.aulia@example.com | Corporate Services | Human Resources |
| Kevin Wijaya | Staff | kevin.wijaya@example.com | Data & AI | Data Engineering |
| Laila Nuraini | Staff | laila.nuraini@example.com | Data & AI | Data Engineering |
| Miko Pratama | Staff | miko.pratama@example.com | Data & AI | Data Engineering |
| Nadia Safitri | Staff | nadia.safitri@example.com | Data & AI | Data Engineering |
| Okta Ramadhan | Staff | okta.ramadhan@example.com | Data & AI | Data Engineering |
| Prasetyo Adi | Staff | prasetyo.adi@example.com | Data & AI | Data Science |
| Qori Amalia | Staff | qori.amalia@example.com | Data & AI | Data Science |
| Rizal Fahmi | Staff | rizal.fahmi@example.com | Data & AI | Data Science |
| Sari Indah | Staff | sari.indah@example.com | Data & AI | Data Science |
| Tommy Kurnia | Staff | tommy.kurnia@example.com | Data & AI | Data Science |
| Joko Susilo | Staff | joko.susilo@example.com | Engineering | Quality Assurance |
| Kartika Sari | Staff | kartika.sari@example.com | Engineering | Quality Assurance |
| Lukman Hakim | Staff | lukman.hakim@example.com | Engineering | Quality Assurance |
| Maya Anggraini | Staff | maya.anggraini@example.com | Engineering | Quality Assurance |
| Nanda Putra | Staff | nanda.putra@example.com | Engineering | Quality Assurance |
| Agus Setiawan | Staff | agus.setiawan@example.com | Engineering | Software Development |
| Dewi Lestari | Staff | dewi.lestari@example.com | Engineering | Software Development |
| Fajar Nugroho | Staff | fajar.nugroho@example.com | Engineering | Software Development |
| Hendra Wijaya | Staff | hendra.wijaya@example.com | Engineering | Software Development |
| Intan Permata | Staff | intan.permata@example.com | Engineering | Software Development |
| Oscar Maulana | Staff | oscar.maulana@example.com | Infrastructure & Cloud | DevOps & Cloud |
| Putri Ayu | Staff | putri.ayu@example.com | Infrastructure & Cloud | DevOps & Cloud |
| Rahmat Hidayat | Staff | rahmat.hidayat@example.com | Infrastructure & Cloud | DevOps & Cloud |
| Sinta Dewi | Staff | sinta.dewi@example.com | Infrastructure & Cloud | DevOps & Cloud |
| Taufik Akbar | Staff | taufik.akbar@example.com | Infrastructure & Cloud | DevOps & Cloud |
| Umar Faruq | Staff | umar.faruq@example.com | Infrastructure & Cloud | Network & Security |
| Vina Oktaviani | Staff | vina.oktaviani@example.com | Infrastructure & Cloud | Network & Security |
| Wahyu Saputra | Staff | wahyu.saputra@example.com | Infrastructure & Cloud | Network & Security |
| Yulia Sari | Staff | yulia.sari@example.com | Infrastructure & Cloud | Network & Security |
| Zaki Ramadhan | Staff | zaki.ramadhan@example.com | Infrastructure & Cloud | Network & Security |

## Struktur Project

```
raja-hr/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin controllers
│   │   │   ├── Atasan/         # Supervisor controllers
│   │   │   ├── Karyawan/       # Employee controllers
│   │   │   └── Api/            # API controllers
│   │   └── Middleware/
│   │       └── RoleMiddleware.php
│   ├── Models/                 # Eloquent models
│   ├── Services/               # Business logic
│   └── Repositories/           # Data access layer
├── database/
│   ├── migrations/             # Database migrations
│   └── seeders/                # Database seeders
├── resources/
│   ├── views/
│   │   ├── admin/              # Admin views
│   │   ├── atasan/             # Supervisor views
│   │   ├── karyawan/           # Employee views
│   │   ├── components/         # Blade components
│   │   └── layouts/            # Layouts
│   ├── css/
│   └── js/
└── routes/
    ├── web.php                 # Web routes
    └── api.php                 # API routes
```

## Fitur yang Sudah Diimplementasi

### Phase 1: Project Setup & Foundation
- Laravel 12 project initialized
- Dependencies installed (Breeze, DomPDF, Excel)
- TailwindCSS + Alpine.js configured
- Dark mode support

### Phase 2: Database Schema
- 9 comprehensive migrations created
- All tables with proper indexes and foreign keys
- Support for GPS and Face Recognition

### Phase 3: Authentication & Authorization
- Laravel Breeze installed
- Role-based middleware implemented
- Multi-role authentication ready

### Phase 4: Core Models
- All models created with relationships
- Business logic helpers
- Type casting and accessors

### Phase 5: Seeders
- RoleSeeder (Direksi, General Manager, Manager, Supervisor, Staff)
- DepartmentSeeder (5 departemen)
- UnitSeeder (10 unit)
- PositionSeeder (16 jabatan)
- UserSeeder (68 akun demo lengkap dengan hierarki atasan)
- SettingSeeder (system settings)
- OfficeLocationSeeder (default office location)

## Configuration

### System Settings

Edit di Admin Dashboard → Settings atau langsung di database `settings` table:

- **Timezone**: `timezone` (default: `Asia/Jakarta`)
- **Work Hours**: `work_start_time`, `work_end_time`
- **Late Tolerance**: `late_tolerance` (minutes)
- **Face Recognition**: `face_recognition_enabled`, `face_recognition_threshold`
- **Company Info**: `company_name`, `company_address`

### Office Location

Edit di Admin Dashboard → Settings → Office Location:
- Latitude & Longitude (koordinat kantor)
- Radius (dalam meter)

## Usage

### Staff
1. Login dengan akun staff, contoh `agus.setiawan@example.com`
2. Dashboard → Attendance
3. Click "Check In"
4. Allow GPS access
5. Upload selfie photo
6. Submit

### Supervisor / Manager / General Manager
1. Login dengan akun sesuai peran
2. Dashboard → Approvals untuk melihat pengajuan cuti bawahan
3. Team Attendance untuk melihat tim
4. Supervisor melihat grup per unit; Manager dan General Manager melihat grup per departemen
5. Approve atau Reject dengan notes

### Direksi
1. Login dengan `direksi@example.com`
2. Full access ke master data, users, attendances, leaves, reports, settings, dan audit logs
3. Tidak memiliki menu absensi pribadi karena tidak punya atasan

## Customization

### Dark Mode
- Toggle dark mode di navbar
- Preferences tersimpan di localStorage

### Company Logo
- Upload logo di `public/images/logo.png`
- Logo akan muncul di halaman aplikasi

## Reports

### Available Reports
- Monthly Attendance Report (PDF & Excel)
- Leave Report (PDF & Excel)

## Security

- Password hashing dengan bcrypt
- CSRF protection
- Role-based access control
- SQL injection protection (Eloquent ORM)
- XSS protection (Blade templating)

## Troubleshooting

### Database Connection Error
```bash
# Check .env configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=raja_hr
DB_USERNAME=root
DB_PASSWORD=your_password

# Create database
mysql -u root -p
CREATE DATABASE raja_hr;
exit;

# Run migrations again
php artisan migrate:fresh --seed
```

### Permission Error
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

### Assets Not Loading
```bash
# Rebuild assets
npm run build

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## License

This project is open-sourced software licensed under the MIT license.

## Developer

Built with  using Laravel 12

## Credits

- [Laravel](https://laravel.com)
- [TailwindCSS](https://tailwindcss.com)
- [Alpine.js](https://alpinejs.dev)
- [Lucide Icons](https://lucide.dev)
- [DomPDF](https://github.com/barryvdh/laravel-dompdf)
- [Laravel Excel](https://laravel-excel.com)
