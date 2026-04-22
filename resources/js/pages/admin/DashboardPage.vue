<template>
  <div class="page">
    <AdminPageHeader title="Super Admin Dashboard" description="System-wide overview across all tenants." />

    <!-- Stat cards -->
    <div class="stats-grid">
      <div v-for="card in statCards" :key="card.label" class="stat-card" :class="`stat-${card.color}`">
        <div class="stat-icon">{{ card.icon }}</div>
        <div class="stat-body">
          <div class="stat-value">{{ loading ? '—' : card.value }}</div>
          <div class="stat-label">{{ card.label }}</div>
        </div>
      </div>
    </div>

    <!-- Two-column layout -->
    <div class="main-grid">
      <!-- Recent Tenants -->
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Recent Tenants</h2>
          <RouterLink to="/admin/tenants" class="card-link">View all →</RouterLink>
        </div>
        <div v-if="loadingTenants" class="card-loader">Loading…</div>
        <div v-else-if="!recentTenants.length" class="card-empty">No tenants yet.</div>
        <table v-else class="mini-table">
          <thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Created</th></tr></thead>
          <tbody>
            <tr v-for="t in recentTenants" :key="t.id">
              <td class="mono">{{ t.tenant_code }}</td>
              <td>{{ t.tenant_name }}</td>
              <td><StatusBadge :status="t.status" dot /></td>
              <td class="muted">{{ fmtDate(t.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Recent Audit Logs -->
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Recent Activity</h2>
          <RouterLink to="/admin/audit-logs" class="card-link">View all →</RouterLink>
        </div>
        <div v-if="loadingLogs" class="card-loader">Loading…</div>
        <div v-else-if="!recentLogs.length" class="card-empty">No activity yet.</div>
        <div v-else class="activity-list">
          <div v-for="log in recentLogs" :key="log.id" class="activity-item">
            <span class="action-dot" :class="`dot-${actionColor(log.action_name)}`" />
            <div class="activity-body">
              <span class="activity-who">{{ log.user?.nama ?? 'System' }}</span>
              <span class="activity-action"> {{ log.action_name }} </span>
              <span class="activity-entity">{{ log.entity_name }}</span>
            </div>
            <span class="activity-time">{{ fmtRelative(log.created_at) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tenant status breakdown -->
    <div class="card mt-16">
      <div class="card-header">
        <h2 class="card-title">Tenant Status Breakdown</h2>
      </div>
      <div class="breakdown-row">
        <div v-for="b in breakdown" :key="b.label" class="breakdown-item">
          <div class="breakdown-bar-wrap">
            <div class="breakdown-bar" :style="{ width: b.pct + '%', background: b.color }" />
          </div>
          <div class="breakdown-meta">
            <span class="breakdown-label">{{ b.label }}</span>
            <span class="breakdown-count">{{ b.count }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import StatusBadge     from '@/components/admin/StatusBadge.vue'
import { useTenantStore }   from '@/stores/tenant'
import { useAuditLogStore } from '@/stores/auditLog'
import { useUserManagementStore } from '@/stores/userManagement'

const tenantStore = useTenantStore()
const logStore    = useAuditLogStore()
const userStore   = useUserManagementStore()

const loading        = ref(true)
const loadingTenants = ref(true)
const loadingLogs    = ref(true)
const recentTenants  = ref([])
const recentLogs     = ref([])

const statCards = computed(() => [
  { label: 'Total Tenants',     value: tenantStore.tenants.length || tenantStore.meta.total || 0,   icon: '🏢', color: 'blue'   },
  { label: 'Active Tenants',    value: tenantStore.activeTenants?.length ?? 0,                       icon: '✅', color: 'green'  },
  { label: 'Total Users',       value: userStore.meta.total ?? 0,                                    icon: '👥', color: 'purple' },
  { label: 'Audit Events Today',value: recentLogs.value.length,                                      icon: '🔍', color: 'orange' },
])

const breakdown = computed(() => {
  const tenants = tenantStore.tenants
  const total = tenants.length || 1
  const active     = tenants.filter(t => t.status === 'active').length
  const inactive   = tenants.filter(t => t.status === 'inactive').length
  const suspended  = tenants.filter(t => t.status === 'suspended').length
  return [
    { label: 'Active',    count: active,    pct: Math.round(active/total*100),    color: '#16a34a' },
    { label: 'Inactive',  count: inactive,  pct: Math.round(inactive/total*100),  color: '#94a3b8' },
    { label: 'Suspended', count: suspended, pct: Math.round(suspended/total*100), color: '#dc2626' },
  ]
})

function actionColor(action) {
  const m = { create: '#16a34a', update: '#2563eb', delete: '#dc2626', login: '#94a3b8', approve: '#16a34a', reject: '#dc2626' }
  return m[action] ? action : 'default'
}

function fmtDate(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function fmtRelative(dt) {
  if (!dt) return '—'
  const diff = Date.now() - new Date(dt).getTime()
  const m = Math.floor(diff / 60000)
  if (m < 1) return 'just now'
  if (m < 60) return `${m}m ago`
  const h = Math.floor(m / 60)
  if (h < 24) return `${h}h ago`
  return fmtDate(dt)
}

onMounted(async () => {
  await Promise.all([
    tenantStore.fetchAll({ page: 1 }).then(() => {
      recentTenants.value = [...tenantStore.tenants].slice(0, 6)
      loadingTenants.value = false
    }),
    logStore.fetchAll({ page: 1 }).then(() => {
      recentLogs.value = [...logStore.logs].slice(0, 8)
      loadingLogs.value = false
    }),
    userStore.fetchAll({ page: 1 }),
  ])
  loading.value = false
})
</script>

<style scoped>
.page { max-width: 1280px; }

.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-card { display: flex; align-items: center; gap: 14px; padding: 18px 20px; border-radius: 12px; background: #fff; border: 1px solid #e2e8f0; }
.stat-icon { font-size: 28px; }
.stat-value { font-size: 24px; font-weight: 800; line-height: 1; }
.stat-label { font-size: 12px; color: #64748b; margin-top: 3px; }
.stat-blue   .stat-value { color: #2563eb; }
.stat-green  .stat-value { color: #16a34a; }
.stat-purple .stat-value { color: #7c3aed; }
.stat-orange .stat-value { color: #ea580c; }

.main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 0; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
.mt-16 { margin-top: 20px; }
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.card-title  { font-size: 15px; font-weight: 700; color: #1e293b; }
.card-link   { font-size: 12px; color: #2563eb; text-decoration: none; }
.card-link:hover { text-decoration: underline; }
.card-loader { color: #94a3b8; font-size: 13px; padding: 20px 0; text-align: center; }
.card-empty  { color: #94a3b8; font-size: 13px; padding: 20px 0; text-align: center; }

.mini-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.mini-table th { text-align: left; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
.mini-table td { padding: 8px 0; border-bottom: 1px solid #f8fafc; color: #334155; }
.mini-table tr:last-child td { border-bottom: none; }
.mono  { font-family: monospace; font-size: 12px; color: #475569; }
.muted { color: #94a3b8; font-size: 12px; }

.activity-list { display: flex; flex-direction: column; gap: 10px; }
.activity-item { display: flex; align-items: center; gap: 10px; padding: 6px 0; border-bottom: 1px solid #f8fafc; }
.activity-item:last-child { border-bottom: none; }
.action-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; background: #94a3b8; }
.dot-create  { background: #16a34a; }
.dot-update  { background: #2563eb; }
.dot-delete  { background: #dc2626; }
.dot-login   { background: #94a3b8; }
.dot-approve { background: #16a34a; }
.dot-reject  { background: #dc2626; }
.activity-body { flex: 1; font-size: 13px; color: #334155; }
.activity-who    { font-weight: 600; }
.activity-action { color: #64748b; }
.activity-entity { color: #2563eb; font-size: 12px; }
.activity-time { font-size: 11px; color: #94a3b8; white-space: nowrap; }

.breakdown-row { display: flex; flex-direction: column; gap: 12px; }
.breakdown-item { display: flex; flex-direction: column; gap: 4px; }
.breakdown-bar-wrap { height: 8px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.breakdown-bar { height: 100%; border-radius: 99px; transition: width .4s ease; }
.breakdown-meta { display: flex; justify-content: space-between; font-size: 12px; color: #64748b; }
.breakdown-count { font-weight: 600; color: #1e293b; }

@media (max-width: 900px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .main-grid  { grid-template-columns: 1fr; }
}
</style>
