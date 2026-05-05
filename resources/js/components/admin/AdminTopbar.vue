<template>
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-btn md:hidden" type="button" @click="$emit('open-sidebar')">
        ☰
      </button>
      <nav class="breadcrumb" aria-label="breadcrumb">
        <RouterLink to="/admin/tenants" class="bc-link">Super Admin</RouterLink>
        <span class="bc-sep">/</span>
        <span class="bc-current">{{ currentPageLabel }}</span>
      </nav>
    </div>
    <div class="topbar-right">
      <slot name="actions" />
      <TenantSwitcher />
      <span class="user-chip">
        <span class="user-avatar">{{ initials(auth.user?.nama) }}</span>
        <span class="user-name">{{ auth.user?.nama ?? 'Super Admin' }}</span>
      </span>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import TenantSwitcher from '@/components/admin/TenantSwitcher.vue'

defineEmits(['open-sidebar'])

const route = useRoute()
const auth  = useAuthStore()

function initials(name = '') {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() || 'SA'
}

const pageLabels = {
  '/admin':             'Dashboard',
  '/admin/dashboard':   'Dashboard',
  '/admin/tenants':         'Tenants',
  '/admin/users':           'Users',
  '/admin/kpi/templates':   'KPI Templates',
  '/admin/kpi/assignments': 'KPI Assignments',
  '/kpi/submit':            'KPI Submission',
  '/admin/reports':         'Reports',
  '/admin/audit-logs':      'Audit Logs',
  '/hr/dashboard':          'HR Dashboard',
  '/hr/pegawai':            'Manajemen Pegawai',
  '/hr/penugasan':          'Penugasan Tugas',
  '/hr/mapping':            'Mapping KPI',
  '/hr/kpi-pegawai':        'Detail KPI Pegawai',
  '/hr/laporan-review':     'Tinjau Laporan KPI',
  '/hr/analytics':          'Analytics HR',
  '/hr/departemen':         'Departemen',
  '/hr/jabatan':            'Jabatan',
  '/hr/kpi-components':     'Komponen KPI',
  '/hr/kpi-indicators':     'Indikator KPI',
  '/hr/sla':                'SLA Pekerjaan',
  '/hr/settings':           'Pengaturan',
  '/hr/logs':               'Log Aktivitas',
  '/dashboard':             'Beranda Pegawai',
  '/pekerjaan':             'Input Pekerjaan',
  '/my-tasks':              'Tugas Saya',
  '/laporan-kpi':           'Laporan KPI',
  '/progress-kpi':          'Progress KPI',
  '/notifikasi':            'Notifikasi',
  '/direktur/dashboard':    'Executive Dashboard',
  '/direktur/analytics':    'Direktur Analytics',
  '/direktur/ranking':      'Ranking Pegawai',
}

const currentPageLabel = computed(() => pageLabels[route.path] ?? 'Panel')
</script>

<style scoped>
.topbar {
  height: 56px;
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  flex-shrink: 0;
}
.topbar-left { display: flex; align-items: center; gap: 12px; }
.breadcrumb { display: flex; align-items: center; gap: 6px; }
.bc-link { color: #2563eb; text-decoration: none; font-size: 13px; font-weight: 500; }
.bc-link:hover { text-decoration: underline; }
.bc-sep { color: #cbd5e1; font-size: 13px; }
.bc-current { color: #1e293b; font-size: 13px; font-weight: 600; }
.topbar-right { display: flex; align-items: center; gap: 12px; }
.menu-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
  font-size: 18px;
}
.user-chip { display: flex; align-items: center; gap: 8px; padding: 5px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 99px; }
.user-avatar { width: 26px; height: 26px; border-radius: 50%; background: #2563eb; color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.user-name { font-size: 13px; font-weight: 500; color: #334155; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
