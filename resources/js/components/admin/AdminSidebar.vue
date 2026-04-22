<template>
  <aside class="sidebar" :class="{ collapsed }">
    <div class="sidebar-logo">
      <span class="logo-icon">⚙️</span>
      <span v-if="!collapsed" class="logo-text">Super Admin</span>
      <button class="collapse-btn" @click="$emit('toggle')">
        {{ collapsed ? '›' : '‹' }}
      </button>
    </div>

    <nav class="sidebar-nav">
      <RouterLink
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="nav-item"
        :class="{ active: isActive(item) }"
        :title="collapsed ? item.label : undefined"
      >
        <span class="nav-icon">{{ item.icon }}</span>
        <span v-if="!collapsed" class="nav-label">{{ item.label }}</span>
        <span v-if="!collapsed && item.badge" class="nav-badge">{{ item.badge }}</span>
      </RouterLink>
    </nav>

    <div class="sidebar-footer">
      <button class="nav-item logout-item" :title="collapsed ? 'Logout' : undefined" @click="$emit('logout')">
        <span class="nav-icon">🚪</span>
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

const navItems = [
  { to: '/admin/dashboard',       icon: '📊', label: 'Dashboard' },
  { to: '/admin/tenants',         icon: '🏢', label: 'Tenants' },
  { to: '/admin/users',           icon: '👥', label: 'Users' },
  { to: '/admin/kpi/templates',   icon: '📋', label: 'KPI Templates' },
  { to: '/admin/kpi/assignments', icon: '📌', label: 'Assignments' },
  { to: '/admin/reports',         icon: '📊', label: 'Reports' },
  { to: '/admin/audit-logs',      icon: '🔍', label: 'Audit Logs' },
]

function isActive(item) {
  return route.path === item.to || route.path.startsWith(item.to + '/')
}
</script>

<style scoped>
.sidebar {
  width: 220px;
  min-height: 100vh;
  background: #1e293b;
  display: flex;
  flex-direction: column;
  transition: width .2s ease;
  flex-shrink: 0;
}
.sidebar.collapsed { width: 60px; }

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 18px 14px 16px;
  border-bottom: 1px solid #334155;
  min-height: 60px;
}
.logo-icon { font-size: 22px; flex-shrink: 0; }
.logo-text { font-size: 15px; font-weight: 700; color: #f1f5f9; white-space: nowrap; flex: 1; }
.collapse-btn {
  margin-left: auto;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  font-size: 18px;
  padding: 2px 4px;
  border-radius: 4px;
  line-height: 1;
  flex-shrink: 0;
}
.collapse-btn:hover { color: #f1f5f9; background: #334155; }

.sidebar-nav { flex: 1; padding: 10px 8px; display: flex; flex-direction: column; gap: 2px; }
.sidebar-footer { padding: 10px 8px 16px; border-top: 1px solid #334155; }

.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: 8px;
  color: #94a3b8;
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  transition: background .15s, color .15s;
  white-space: nowrap;
  overflow: hidden;
}
.nav-item:hover { background: #334155; color: #f1f5f9; }
.nav-item.active { background: #2563eb; color: #fff; }
.nav-item.active .nav-icon { filter: none; }
.nav-icon { font-size: 17px; flex-shrink: 0; width: 22px; text-align: center; }
.nav-label { flex: 1; }
.nav-badge {
  background: #334155;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 99px;
}
.nav-item.active .nav-badge { background: rgba(255,255,255,.15); color: #fff; }
.logout-item { color: #f87171; }
.logout-item:hover { background: #450a0a; color: #fca5a5; }
</style>
