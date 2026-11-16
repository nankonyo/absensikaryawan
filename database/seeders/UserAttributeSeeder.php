<?php

namespace Database\Seeders;

use App\Models\UserAttribute;
use Illuminate\Database\Seeder;

class UserAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attrs = [
            ['name' => 'join_date', 'label' => 'Tanggal Masuk', 'type' => 'date', 'options' => null, 'sort_order' => 5, 'is_active' => true],
            ['name' => 'resign_date', 'label' => 'Tanggal Keluar', 'type' => 'date', 'options' => null, 'sort_order' => 6, 'is_active' => true],
            ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea', 'options' => null, 'sort_order' => 8, 'is_active' => true],
            ['name' => 'npwp', 'label' => 'NPWP', 'type' => 'text', 'options' => null, 'sort_order' => 9, 'is_active' => true],
            ['name' => 'bank_name', 'label' => 'Nama Bank', 'type' => 'text', 'options' => null, 'sort_order' => 10, 'is_active' => true],
            ['name' => 'bank_account', 'label' => 'Nomor Rekening', 'type' => 'text', 'options' => null, 'sort_order' => 11, 'is_active' => true],
            ['name' => 'bank_account_holder', 'label' => 'Pemilik Rekening', 'type' => 'text', 'options' => null, 'sort_order' => 12, 'is_active' => true],
            ['name' => 'bpjs_ketenagakerjaan', 'label' => 'BPJS Ketenagakerjaan', 'type' => 'text', 'options' => null, 'sort_order' => 13, 'is_active' => true],
            ['name' => 'bpjs_kesehatan', 'label' => 'BPJS Kesehatan', 'type' => 'text', 'options' => null, 'sort_order' => 14, 'is_active' => true],
            ['name' => 'nik', 'label' => 'NIK', 'type' => 'text', 'options' => null, 'sort_order' => 15, 'is_active' => true],
            ['name' => 'religion', 'label' => 'Agama', 'type' => 'text', 'options' => null, 'sort_order' => 16, 'is_active' => true],
            ['name' => 'marital_status', 'label' => 'Status Pernikahan', 'type' => 'text', 'options' => null, 'sort_order' => 17, 'is_active' => true],
            ['name' => 'blood_type', 'label' => 'Golongan Darah', 'type' => 'text', 'options' => null, 'sort_order' => 18, 'is_active' => true],
        ];

        foreach ($attrs as $a) {
            UserAttribute::firstOrCreate(['name' => $a['name']], $a);
        }
    }
}
