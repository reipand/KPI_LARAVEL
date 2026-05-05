<template>
  <aside class="sidebar" :class="{ collapsed }">
    <div class="sidebar-logo">
      <div class="brand-mark">SA</div>

      <div v-if="!collapsed" class="brand-copy">
        <span class="logo-text">System Admin</span>
        <span class="logo-subtitle">Full Access Console</span>
      </div>

      <button
        class="collapse-btn"
        :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        @click="$emit('toggle')"
      >
        <svg
          class="collapse-icon"
          :class="{ rotated: collapsed }"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <path d="M15 18l-6-6 6-6" />
        </svg>
      </button>
    </div>

    <nav class="sidebar-nav">
      <section
        v-for="group in navGroups"
        :key="group.section"
        class="nav-group"
      >
        <p v-if="!collapsed" class="nav-section">{{ group.section }}</p>

        <RouterLink
          v-for="item in group.items"
          :key="item.to"
          :to="item.to"
          class="nav-item"
          :class="{ active: isActive(item.to), compact: collapsed }"
          :title="collapsed ? item.label : undefined"
        >
          <span class="nav-icon">{{ item.icon }}</span>
          <span v-if="!collapsed" class="nav-label">{{ item.label }}</span>
        </RouterLink>
      </section>
    </nav>

    <div class="sidebar-footer">
      <button
        class="nav-item logout-item"
        :class="{ compact: collapsed }"
        :title="collapsed ? 'Logout' : undefined"
        @click="$emit('logout')"
      >
        <span class="nav-icon">⎋</span>
        <span v-if="!collapsed" class="nav-label">Logout</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { useRoute } from 'vue-router'

defineProps({
  collapsed: { type: Boolean, default: false },
})

defineEmits(['toggle', 'logout'])

const route = useRoute()

const navGroups = [
  {
    section: 'System Admin',
    items: [
      { to: '/admin/dashboard', icon: '📊', label: 'Dashboard Admin' },
      { to: '/admin/tenants', icon: '🏢', label: 'Tenant Management' },
      { to: '/admin/users', icon: '👥', label: 'User Management' },
      { to: '/admin/reports', icon: '🧾', label: 'Reports' },
      { to: '/admin/audit-logs', icon: '🕵️', label: 'Audit Logs' },
    ],
  },
  {
    section: 'KPI Enterprise',
    items: [
      { to: '/admin/kpi/templates', icon: '📋', label: 'KPI Templates' },
      { to: '/admin/kpi/assignments', icon: '📌', label: 'KPI Assignments' },
      { to: '/kpi/submit', icon: '📝', label: 'KPI Submission' },
    ],
  },
  {
    section: 'HR Operations',
    items: [
      { to: '/hr/dashboard', icon: '📈', label: 'HR Dashboard' },
      { to: '/hr/pegawai', icon: '🧑‍💼', label: 'Manajemen Pegawai' },
      { to: '/hr/penugasan', icon: '✅', label: 'Penugasan Tugas' },
      { to: '/hr/mapping', icon: '🗂️', label: 'Mapping KPI' },
      { to: '/hr/kpi-pegawai', icon: '🎯', label: 'Detail KPI Pegawai' },
      { to: '/hr/laporan-review', icon: '🔎', label: 'Tinjau Laporan KPI' },
      { to: '/hr/analytics', icon: '📉', label: 'Analytics HR' },
      { to: '/hr/departemen', icon: '🏬', label: 'Departemen' },
      { to: '/hr/jabatan', icon: '🪪', label: 'Jabatan' },
      { to: '/hr/kpi-components', icon: '🧱', label: 'Komponen KPI' },
      { to: '/hr/kpi-indicators', icon: '📍', label: 'Indikator KPI' },
      { to: '/hr/sla', icon: '⏱️', label: 'SLA Pekerjaan' },
      { to: '/hr/settings', icon: '⚙️', label: 'Pengaturan' },
      { to: '/hr/logs', icon: '📚', label: 'Log Aktivitas' },
    ],
  },
  {
    section: 'Direktur View',
    items: [
      { to: '/direktur/dashboard', icon: '🧭', label: 'Executive Dashboard' },
      { to: '/direktur/analytics', icon: '📡', label: 'Direktur Analytics' },
      { to: '/direktur/ranking', icon: '🏆', label: 'Ranking Pegawai' },
    ],
  },
  {
    section: 'Pegawai View',
    items: [
      { to: '/dashboard', icon: '🏠', label: 'Beranda Pegawai' },
      { to: '/pekerjaan', icon: '🗒️', label: 'Input Pekerjaan' },
      { to: '/my-tasks', icon: '☑️', label: 'Tugas Saya' },
      { to: '/laporan-kpi', icon: '📤', label: 'Laporan KPI' },
      { to: '/progress-kpi', icon: '📌', label: 'Progress KPI' },
      { to: '/notifikasi', icon: '🔔', label: 'Notifikasi' },
    ],
  },
]

function isActive(path) {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<style scoped>
.sidebar {
  width: 272px;
  min-height: 100vh;
  background:
    radial-gradient(circle at top, rgba(59, 130, 246, 0.18), transparent 26%),
    linear-gradient(180deg, #0f172a 0%, #111827 100%);
  color: #cbd5e1;
  display: flex;
  flex-direction: column;
  border-right: 1px solid rgba(148, 163, 184, 0.18);
  transition: width .22s ease;
  flex-shrink: 0;
}

.sidebar.collapsed {
  width: 76px;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 14px;
  min-height: 74px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}

.brand-mark {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: linear-gradient(135deg, #2563eb, #0ea5e9);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .04em;
  flex-shrink: 0;
}

.brand-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.logo-text {
  font-size: 14px;
  font-weight: 700;
  color: #f8fafc;
  line-height: 1.2;
}

.logo-subtitle {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.2;
}

.collapse-btn {
  margin-left: auto;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  background: rgba(15, 23, 42, 0.72);
  color: #cbd5e1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
}

.collapse-btn:hover {
  background: rgba(30, 41, 59, 0.95);
  color: #fff;
}

.collapse-icon {
  width: 16px;
  height: 16px;
  transition: transform .18s ease;
}

.collapse-icon.rotated {
  transform: rotate(180deg);
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 12px 10px 14px;
}

.nav-group + .nav-group {
  margin-top: 12px;
}

.nav-section {
  margin: 0 8px 6px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #64748b;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 12px;
  border-radius: 12px;
  color: #cbd5e1;
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  transition: background .15s ease, color .15s ease, transform .15s ease;
}

.nav-item:hover {
  background: rgba(30, 41, 59, 0.92);
  color: #fff;
  transform: translateX(1px);
}

.nav-item.active {
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.88), rgba(14, 165, 233, 0.75));
  color: #fff;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
}

.nav-item.compact {
  justify-content: center;
  padding: 10px 0;
}

.nav-icon {
  width: 22px;
  text-align: center;
  flex-shrink: 0;
  font-size: 16px;
}

.nav-label {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sidebar-footer {
  padding: 10px;
  border-top: 1px solid rgba(148, 163, 184, 0.16);
}

.logout-item {
  border: none;
  background: none;
  cursor: pointer;
  color: #fda4af;
}

.logout-item:hover {
  background: rgba(127, 29, 29, 0.35);
  color: #fecdd3;
}
</style>
