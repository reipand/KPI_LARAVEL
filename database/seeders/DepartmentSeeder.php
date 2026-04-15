<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /** 6 departments */
    private const DEPARTMENTS = [
        ['nama' => 'Board of Director',      'kode' => 'BOD', 'deskripsi' => 'Dewan direksi dan pengambil keputusan strategis perusahaan.'],
        ['nama' => 'Finance & Accounting',   'kode' => 'FNA', 'deskripsi' => 'Pengelolaan keuangan, akuntansi, dan pelaporan fiskal.'],
        ['nama' => 'HR & GA',                'kode' => 'HGA', 'deskripsi' => 'Human Resources dan General Affairs.'],
        ['nama' => 'Business Development',   'kode' => 'BDV', 'deskripsi' => 'Pengembangan bisnis, marketing, dan penjualan.'],
        ['nama' => 'Research & Development', 'kode' => 'RND', 'deskripsi' => 'Riset, inovasi, dan pengembangan produk.'],
        ['nama' => 'Information Technology', 'kode' => 'ITD', 'deskripsi' => 'Infrastruktur teknologi, sistem informasi, dan keamanan data.'],
    ];

    public function run(): void
    {
        foreach (self::DEPARTMENTS as $dept) {
            Department::query()->updateOrCreate(
                ['kode' => $dept['kode']],
                [
                    'nama'      => $dept['nama'],
                    'deskripsi' => $dept['deskripsi'],
                    'is_active' => true,
                ]
            );
        }
    }
}
