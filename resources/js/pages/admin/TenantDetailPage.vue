<template>
  <div class="page">
    <AdminPageHeader
      :title="tenant?.tenant_name ?? 'Tenant Detail'"
      :description="tenant?.domain ?? tenant?.tenant_code"
      :show-back="true"
      back-to="/admin/tenants"
    >
      <template v-if="tenant" #actions>
        <StatusBadge :status="tenant.status" dot />
        <button
          v-if="tenant.status !== 'active'"
          class="btn-success"
          :disabled="toggling"
          @click="toggleStatus('activate')"
        >▶ Activate</button>
        <button
          v-else
          class="btn-warning"
          :disabled="toggling"
          @click="toggleStatus('deactivate')"
        >⏸ Deactivate</button>
      </template>
    </AdminPageHeader>

    <div v-if="loading" class="loading-state">Loading tenant…</div>
    <div v-else-if="!tenant" class="loading-state">Tenant not found.</div>
    <template v-else>
      <!-- Info cards -->
      <div class="info-grid">
        <div class="info-card">
          <p class="info-label">Tenant Code</p>
          <p class="info-value mono">{{ tenant.tenant_code }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Contact Email</p>
          <p class="info-value">{{ tenant.contact_email ?? '—' }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Phone</p>
          <p class="info-value">{{ tenant.contact_phone ?? '—' }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Domain</p>
          <p class="info-value mono">{{ tenant.domain ?? '—' }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Users</p>
          <p class="info-value">{{ tenant.users_count ?? '—' }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">KPI Templates</p>
          <p class="info-value">{{ tenant.kpi_templates_count ?? '—' }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Created</p>
          <p class="info-value">{{ fmtDate(tenant.created_at) }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Address</p>
          <p class="info-value">{{ tenant.address ?? '—' }}</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tab-bar">
        <button v-for="t in tabs" :key="t" class="tab-btn" :class="{ active: activeTab === t }" @click="activeTab = t">
          {{ t }}
        </button>
      </div>

      <!-- Users tab -->
      <div v-if="activeTab === 'Users'" class="card">
        <div v-if="loadingUsers" class="card-loader">Loading users…</div>
        <div v-else-if="!tenantUsers.length" class="card-empty">No users in this tenant.</div>
        <table v-else class="mini-table">
          <thead>
            <tr><th>NIP</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>
          </thead>
          <tbody>
            <tr v-for="u in tenantUsers" :key="u.id">
              <td class="mono">{{ u.nip }}</td>
              <td>{{ u.nama }}</td>
              <td class="muted">{{ u.email ?? '—' }}</td>
              <td>
                <span class="role-chip">{{ formatRole(u.roles?.[0]?.name ?? u.role) }}</span>
              </td>
              <td><StatusBadge :status="u.status_karyawan === 'Tetap' ? 'active' : 'inactive'" :label="u.status_karyawan" dot /></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- KPI Templates tab -->
      <div v-else-if="activeTab === 'KPI Templates'" class="card">
        <div v-if="loadingTemplates" class="card-loader">Loading templates…</div>
        <div v-else-if="!tenantTemplates.length" class="card-empty">No KPI templates in this tenant.</div>
        <table v-else class="mini-table">
          <thead>
            <tr><th>Name</th><th>Period</th><th>Indicators</th><th>Status</th><th>Created</th></tr>
          </thead>
          <tbody>
            <tr v-for="tpl in tenantTemplates" :key="tpl.id">
              <td>{{ tpl.name }}</td>
              <td class="muted">{{ tpl.period_label ?? '—' }}</td>
              <td>{{ tpl.indicators_count ?? tpl.indicators?.length ?? '—' }}</td>
              <td><StatusBadge :status="tpl.is_active ? 'active' : 'inactive'" dot /></td>
              <td class="muted">{{ fmtDate(tpl.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Audit Logs tab -->
      <div v-else-if="activeTab === 'Audit Logs'" class="card">
        <div v-if="loadingActivity" class="card-loader">Loading activity…</div>
        <div v-else-if="!tenantLogs.length" class="card-empty">No activity recorded.</div>
        <div v-else class="activity-list">
          <div v-for="log in tenantLogs" :key="log.id" class="activity-item">
            <span class="action-dot" :class="`dot-${log.action_name}`" />
            <div class="activity-body">
              <span class="activity-who">{{ log.user?.nama ?? 'System' }}</span>
              <span class="activity-sep"> · </span>
              <span class="activity-action">{{ log.action_name }}</span>
              <span class="activity-sep"> on </span>
              <span class="activity-entity">{{ log.entity_name }}</span>
            </div>
            <span class="activity-time">{{ fmtDate(log.created_at) }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute }       from 'vue-router'
import AdminPageHeader    from '@/components/admin/AdminPageHeader.vue'
import StatusBadge        from '@/components/admin/StatusBadge.vue'
import { useTenantStore } from '@/stores/tenant'
import api                from '@/services/api'

const route       = useRoute()
const tenantStore = useTenantStore()

const tenant           = ref(null)
const loading          = ref(true)
const toggling         = ref(false)
const activeTab        = ref('Users')
const tabs             = ['Users', 'KPI Templates', 'Audit Logs']
const tenantUsers      = ref([])
const tenantTemplates  = ref([])
const tenantLogs       = ref([])
const loadingUsers     = ref(false)
const loadingTemplates = ref(false)
const loadingActivity  = ref(false)

async function loadTenant() {
  loading.value = true
  try {
    const { data } = await api.get(`/v2/tenants/${route.params.id}`)
    tenant.value = data.data
  } finally {
    loading.value = false
  }
}

async function loadUsers() {
  loadingUsers.value = true
  try {
    const { data } = await api.get('/v2/users', { params: { page: 1, per_page: 50 } })
    tenantUsers.value = data.data ?? []
  } finally {
    loadingUsers.value = false
  }
}

async function loadTemplates() {
  loadingTemplates.value = true
  try {
    const { data } = await api.get('/v2/kpi/templates', { params: { page: 1, per_page: 50 } })
    tenantTemplates.value = data.data ?? []
  } finally {
    loadingTemplates.value = false
  }
}

async function loadActivity() {
  loadingActivity.value = true
  try {
    const { data } = await api.get('/v2/audit-logs', { params: { page: 1 } })
    tenantLogs.value = (data.data ?? []).slice(0, 20)
  } finally {
    loadingActivity.value = false
  }
}

async function toggleStatus(action) {
  toggling.value = true
  try {
    await tenantStore.setStatus(tenant.value.id, action)
    await loadTenant()
  } finally {
    toggling.value = false
  }
}

function fmtDate(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatRole(name = '') {
  const m = { super_admin: 'Super Admin', tenant_admin: 'Tenant Admin', hr_manager: 'HR Manager', direktur: 'Direktur', pegawai: 'Pegawai', dept_head: 'Dept Head', supervisor: 'Supervisor' }
  return m[name] ?? name
}

onMounted(async () => {
  await loadTenant()
  await Promise.all([loadUsers(), loadTemplates(), loadActivity()])
})
</script>

<style scoped>
.page { max-width: 1280px; }
.loading-state { text-align: center; padding: 60px; color: #94a3b8; font-size: 14px; }
.btn-success { background: #16a34a; color: #fff; padding: 8px 14px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-success:hover { background: #15803d; }
.btn-warning { background: #ea580c; color: #fff; padding: 8px 14px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-warning:hover { background: #c2410c; }

.info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
.info-card  { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; }
.info-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; font-weight: 600; margin-bottom: 4px; }
.info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
.mono { font-family: monospace; font-size: 13px; }

.tab-bar { display: flex; gap: 4px; margin-bottom: 16px; background: #f1f5f9; padding: 4px; border-radius: 10px; width: fit-content; }
.tab-btn { padding: 7px 18px; border: none; border-radius: 7px; background: none; font-size: 13px; font-weight: 500; color: #64748b; cursor: pointer; }
.tab-btn.active { background: #fff; color: #1e293b; font-weight: 600; box-shadow: 0 1px 4px rgba(0,0,0,.08); }

.card        { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
.card-loader { text-align: center; padding: 32px; color: #94a3b8; font-size: 13px; }
.card-empty  { text-align: center; padding: 32px; color: #94a3b8; font-size: 13px; }

.mini-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.mini-table th { text-align: left; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; }
.mini-table td { padding: 9px 0; border-bottom: 1px solid #f8fafc; color: #334155; }
.mini-table tr:last-child td { border-bottom: none; }
.muted { color: #94a3b8; font-size: 12px; }
.role-chip { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }

.activity-list { display: flex; flex-direction: column; }
.activity-item { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f8fafc; }
.activity-item:last-child { border-bottom: none; }
.action-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; background: #94a3b8; }
.dot-create  { background: #16a34a; }
.dot-update  { background: #2563eb; }
.dot-delete  { background: #dc2626; }
.dot-login   { background: #94a3b8; }
.dot-approve { background: #16a34a; }
.dot-reject  { background: #dc2626; }
.activity-body { flex: 1; font-size: 13px; color: #334155; }
.activity-who    { font-weight: 600; }
.activity-sep    { color: #94a3b8; }
.activity-action { color: #64748b; }
.activity-entity { color: #2563eb; }
.activity-time { font-size: 11px; color: #94a3b8; white-space: nowrap; }

@media (max-width: 900px) { .info-grid { grid-template-columns: repeat(2, 1fr); } }
</style>
