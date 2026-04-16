<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * 10 jabatan resmi PT. BASS Training Center & Consultant.
     * Kolom 'dept' merujuk ke Department.kode.
     */
    private const POSITIONS = [
        // ── Board of Director ────────────────────────────────────────────
        ['nama' => 'Direktur Utama',        'kode' => 'DIRUT',  'dept' => 'BOD', 'level' => 'Direktur Utama'],
        ['nama' => 'Direktur',              'kode' => 'DIR',    'dept' => 'BOD', 'level' => 'Direktur'],
        // ── Business Development ─────────────────────────────────────────
        ['nama' => 'Marketing & Sales',     'kode' => 'MKT',    'dept' => 'BDV', 'level' => 'Staff'],
        ['nama' => 'Digital Marketing',     'kode' => 'DIGMKT', 'dept' => 'BDV', 'level' => 'Staff'],
        // ── Finance & Accounting ─────────────────────────────────────────
        ['nama' => 'Finance',               'kode' => 'FIN',    'dept' => 'FNA', 'level' => 'Staff'],
        ['nama' => 'Accounting',            'kode' => 'ACC',    'dept' => 'FNA', 'level' => 'Staff'],
        // ── HR & GA ──────────────────────────────────────────────────────
        ['nama' => 'HR & GA Manager',       'kode' => 'HRGA',   'dept' => 'HGA', 'level' => 'Manager'],
        ['nama' => 'Driver',                'kode' => 'DRV',    'dept' => 'HGA', 'level' => 'Staff'],
        ['nama' => 'Office Boy & Admin GA', 'kode' => 'OBGA',   'dept' => 'HGA', 'level' => 'Staff'],
        // ── Research & Development ───────────────────────────────────────
        ['nama' => 'R&D Staff',             'kode' => 'RNDS',   'dept' => 'RND', 'level' => 'Staff'],
        // ── Information Technology ───────────────────────────────────────
        ['nama' => 'IT Support',            'kode' => 'ITS',    'dept' => 'ITD', 'level' => 'Staff'],
        ['nama' => 'IT Staff',              'kode' => 'ITST',   'dept' => 'ITD', 'level' => 'Staff'],

    ];

    public function run(): void
    {
        $deptMap = Department::query()->pluck('id', 'kode');

        foreach (self::POSITIONS as $pos) {
            Position::query()->updateOrCreate(
                ['kode' => $pos['kode']],
                [
                    'nama'          => $pos['nama'],
                    'department_id' => $deptMap[$pos['dept']] ?? null,
                    'level'         => $pos['level'],
                    'is_active'     => true,
                ]
            );
        }
    }
}
