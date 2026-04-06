<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\KpiComponent;
use App\Models\Sla;
use App\Models\Setting;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Settings
        Setting::set('hr_manager_name', 'Katry Oktariani');
        Setting::set('hr_manager_nip', 'BASS-HR-01-2026');
        Setting::set('year', '2026');
        Setting::set('company', 'PT. BASS Training Center & Consultant');
        
        // Employees
        $employees = [
            ['nip' => 'BASS-DIRUT-01-2016', 'name' => 'Eva Rosmalia', 'position' => 'Direktur Utama', 'department' => 'Board of Director', 'status' => 'Tetap', 'join_date' => '2016-09-26', 'phone' => '0812-9868-5372', 'email' => 'feedbackevaros@gmail.com', 'role' => 'director'],
            ['nip' => 'BASS-DIRUT-01-2020', 'name' => 'Daffa Muhammad Ardian', 'position' => 'Direktur', 'department' => 'Board of Director', 'status' => 'Tetap', 'join_date' => '2020-01-11', 'phone' => '0821-2527-7435', 'email' => 'daffaardn10@gmail.com', 'role' => 'director'],
            ['nip' => 'BASS-HR-01-2026', 'name' => 'Katry Oktariani', 'position' => 'HR & GA Manager', 'department' => 'HR & GA', 'status' => 'Kontrak', 'join_date' => '2026-01-02', 'phone' => '0817-6409-005', 'email' => 'katry1990@gmail.com', 'role' => 'hr_manager'],
            // Add more employees...
        ];
        
        foreach ($employees as $emp) {
            Employee::updateOrCreate(
                ['nip' => $emp['nip']],
                $emp
            );
        }
        
        // KPI Components
        $kpis = [
            // Marketing & Sales
            ['position' => 'Marketing & Sales', 'objective' => 'Sales Revenue', 'strategy' => 'Penjualan New Products & New Customer', 'weight' => 0.7, 'target' => 3500000000, 'type' => 'achievement'],
            ['position' => 'Marketing & Sales', 'objective' => 'Customer Satisfaction Survey', 'strategy' => 'Customer Satisfaction Index (CSI)', 'weight' => 0.2, 'target' => 3.5, 'type' => 'csi'],
            ['position' => 'Marketing & Sales', 'objective' => 'Lead Time Proposal', 'strategy' => 'Pembuatan penawaran saat permintaan dari customer', 'weight' => 0.1, 'target' => 0, 'type' => 'zero_delay'],
            
            // Add more KPI components for other positions...
        ];
        
        foreach ($kpis as $kpi) {
            KpiComponent::updateOrCreate(
                [
                    'position' => $kpi['position'],
                    'objective' => $kpi['objective'],
                ],
                $kpi
            );
        }
        
        // SLA
        $slas = [
            ['task_name' => 'Pembuatan Invoice', 'position' => 'Finance', 'hours' => 8, 'description' => 'Selesai dalam 1 hari kerja setelah closing'],
            ['task_name' => 'Laporan P&L', 'position' => 'Accounting', 'hours' => 24, 'description' => 'Terkirim tanggal 3 setiap bulan'],
            ['task_name' => 'Penawaran / Proposal', 'position' => 'Marketing & Sales', 'hours' => 8, 'description' => '1 hari kerja setelah permintaan'],
        ];
        
        foreach ($slas as $sla) {
            Sla::updateOrCreate(
                [
                    'task_name' => $sla['task_name'],
                    'position' => $sla['position'],
                ],
                $sla
            );
        }
    }
}
