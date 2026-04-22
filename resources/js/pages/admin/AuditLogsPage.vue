<template>
  <div class="page">
    <AdminPageHeader
      title="Audit Logs"
      description="Track all system actions and data changes."
      :show-back="true"
    />

    <!-- Filters -->
    <div class="filter-bar">
      <select v-model="filters.module_name" class="filter-select" @change="load">
        <option value="">All Modules</option>
        <option v-for="m in modules" :key="m" :value="m">{{ m }}</option>
      </select>
      <select v-model="filters.action_name" class="filter-select" @change="load">
        <option value="">All Actions</option>
        <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
      </select>
      <input v-model="filters.date_from" type="date" class="filter-input" @change="load" />
      <span class="filter-sep">to</span>
      <input v-model="filters.date_to"   type="date" class="filter-input" @change="load" />
      <button class="btn-reset" @click="resetFilters">Reset</button>
    </div>

    <DataTable
      :columns="columns"
      :rows="store.logs"
      :loading="store.loading"
      :current-page="page"
      :total-pages="store.meta.last_page ?? 1"
      :searchable="false"
      empty-text="No audit logs found."
      @page="p => { page = p; load() }"
    >
      <template #cell_user="{ row }">
        {{ row.user?.nama ?? row.user_id ?? '—' }}
      </template>
      <template #cell_action_name="{ row }">
        <span class="action-badge" :class="`action-${actionColor(row.action_name)}`">
          {{ row.action_name }}
        </span>
      </template>
      <template #cell_entity="{ row }">
        {{ row.entity_name }} <span class="entity-id">#{{ row.entity_id }}</span>
      </template>
      <template #cell_changes="{ row }">
        <button v-if="row.old_value_json || row.new_value_json" class="btn-diff" @click="showDiff(row)">
          View diff
        </button>
        <span v-else class="text-muted">—</span>
      </template>
      <template #cell_created_at="{ row }">
        {{ formatDate(row.created_at) }}
      </template>
    </DataTable>

    <!-- Diff Dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="diffRow" class="overlay" @click.self="diffRow = null">
          <div class="diff-dialog">
            <div class="diff-header">
              <span class="action-badge" :class="`action-${actionColor(diffRow.action_name)}`">{{ diffRow.action_name }}</span>
              <span class="diff-entity">{{ diffRow.entity_name }} #{{ diffRow.entity_id }}</span>
              <button class="btn-close" @click="diffRow = null">×</button>
            </div>
            <div class="diff-grid">
              <div class="diff-pane">
                <p class="diff-label">Before</p>
                <pre class="diff-pre">{{ formatJson(diffRow.old_value_json) }}</pre>
              </div>
              <div class="diff-pane">
                <p class="diff-label">After</p>
                <pre class="diff-pre after">{{ formatJson(diffRow.new_value_json) }}</pre>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuditLogStore } from '@/stores/auditLog'
import DataTable      from '@/components/admin/DataTable.vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'

const store   = useAuditLogStore()
const page    = ref(1)
const diffRow = ref(null)

const filters = ref({ module_name: '', action_name: '', date_from: '', date_to: '' })
const modules = ['tenants','users','kpi_templates','kpi_assignments','tasks','reports']
const actions = ['create','update','delete','submit','approve','reject','export_pdf','login']

const columns = [
  { key: 'user',        label: 'User',    width: '160px' },
  { key: 'module_name', label: 'Module',  width: '140px' },
  { key: 'action_name', label: 'Action',  width: '120px' },
  { key: 'entity',      label: 'Entity',  width: '160px' },
  { key: 'changes',     label: 'Changes', width: '100px' },
  { key: 'ip_address',  label: 'IP',      width: '120px' },
  { key: 'created_at',  label: 'Time',    width: '150px' },
]

async function load() {
  await store.fetchAll({ page: page.value, ...filters.value })
}

function resetFilters() {
  filters.value = { module_name: '', action_name: '', date_from: '', date_to: '' }
  page.value = 1
  load()
}

function showDiff(row) { diffRow.value = row }

function formatDate(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatJson(obj) {
  if (!obj) return 'null'
  return JSON.stringify(obj, null, 2)
}

function actionColor(action) {
  const map = {
    create: 'green', update: 'blue', delete: 'red',
    approve: 'green', reject: 'red', submit: 'yellow',
    export_pdf: 'purple', login: 'gray',
  }
  return map[action] ?? 'gray'
}

onMounted(load)
</script>

<style scoped>
.page { max-width: 1280px; }
.filter-bar  { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
.filter-select, .filter-input { padding: 8px 11px; border: 1px solid #e2e8f0; border-radius: 7px; font-size: 13px; background: #fff; }
.filter-sep  { font-size: 12px; color: #94a3b8; }
.btn-reset   { padding: 7px 14px; border: 1px solid #e2e8f0; border-radius: 7px; background: #fff; font-size: 13px; cursor: pointer; color: #475569; }
.btn-reset:hover { background: #f1f5f9; }
.action-badge { padding: 2px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.action-green  { background: #dcfce7; color: #15803d; }
.action-blue   { background: #dbeafe; color: #1d4ed8; }
.action-red    { background: #fee2e2; color: #b91c1c; }
.action-yellow { background: #fef9c3; color: #a16207; }
.action-purple { background: #f3e8ff; color: #7e22ce; }
.action-gray   { background: #f1f5f9; color: #475569; }
.entity-id { color: #94a3b8; font-size: 11px; }
.btn-diff { background: #eff6ff; color: #2563EB; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; }
.text-muted { color: #94a3b8; font-size: 12px; }
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.6); z-index: 9998; display: flex; align-items: center; justify-content: center; padding: 20px; }
.diff-dialog { background: #fff; border-radius: 14px; padding: 24px; max-width: 820px; width: 100%; max-height: 85vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0,0,0,.18); }
.diff-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.diff-entity { font-weight: 600; color: #1e293b; flex: 1; }
.btn-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; }
.diff-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.diff-pane  { }
.diff-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; font-weight: 700; margin-bottom: 6px; }
.diff-pre   { background: #fef2f2; border-radius: 8px; padding: 12px; font-size: 11px; overflow-x: auto; font-family: monospace; color: #7f1d1d; white-space: pre; }
.diff-pre.after { background: #f0fdf4; color: #14532d; }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
