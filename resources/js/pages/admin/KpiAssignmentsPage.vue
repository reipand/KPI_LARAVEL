<template>
  <div class="page">
    <AdminPageHeader
      title="KPI Assignments"
      description="Assign KPI templates to employees by period."
      :show-back="true"
    >
      <template #actions>
        <button class="btn-primary" @click="openCreate">+ Assign KPI</button>
      </template>
    </AdminPageHeader>

    <!-- Filters -->
    <div class="filter-bar">
      <select v-model="filters.status" class="filter-select" @change="loadData">
        <option value="">All Status</option>
        <option v-for="s in statuses" :key="s" :value="s">{{ capitalize(s) }}</option>
      </select>
      <input v-model="filters.period_year" type="number" class="filter-input" placeholder="Year" @change="loadData" />
      <select v-model="filters.period_month" class="filter-select" @change="loadData">
        <option value="">All Months</option>
        <option v-for="(m, i) in months" :key="i+1" :value="i+1">{{ m }}</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="store.assignments"
      :loading="store.loading"
      :current-page="page"
      :total-pages="store.meta.last_page ?? 1"
      :searchable="false"
      @page="p => { page = p; loadData() }"
    >
      <template #cell_employee="{ row }">
        {{ row.employee?.nama ?? '—' }}
      </template>
      <template #cell_template="{ row }">
        {{ row.template?.template_name ?? '—' }}
      </template>
      <template #cell_period="{ row }">
        {{ row.period_month ? months[row.period_month - 1] + ' ' + row.period_year : row.period_year }}
      </template>
      <template #cell_status="{ row }">
        <StatusBadge :status="row.status" />
      </template>
      <template #row_actions="{ row }">
        <button class="act-btn" @click="openDetail(row)">👁</button>
        <button v-if="row.status === 'submitted'" class="act-btn text-green" title="Approve" @click="openReview(row, 'approve')">✓</button>
        <button v-if="row.status === 'submitted'" class="act-btn text-red"   title="Reject"  @click="openReview(row, 'reject')">✗</button>
      </template>
    </DataTable>

    <!-- Create Assignment Dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showCreate" class="overlay" @click.self="showCreate = false">
          <div class="form-dialog">
            <h2 class="form-title">Assign KPI Template</h2>
            <div class="form-grid">
              <label class="form-label span-2">
                Employee *
                <select v-model="createForm.employee_id" class="form-input">
                  <option value="">— Select Employee —</option>
                  <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.nama }} ({{ u.nip }})</option>
                </select>
              </label>
              <label class="form-label span-2">
                KPI Template *
                <select v-model="createForm.kpi_template_id" class="form-input">
                  <option value="">— Select Template —</option>
                  <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.template_name }}</option>
                </select>
              </label>
              <label class="form-label">
                Period Year *
                <input v-model.number="createForm.period_year" type="number" class="form-input" :placeholder="currentYear" />
              </label>
              <label class="form-label">
                Period Month
                <select v-model="createForm.period_month" class="form-input">
                  <option :value="null">— (Full Year) —</option>
                  <option v-for="(m, i) in months" :key="i+1" :value="i+1">{{ m }}</option>
                </select>
              </label>
            </div>
            <p v-if="createError" class="form-error">{{ createError }}</p>
            <div class="form-footer">
              <button class="btn-cancel" @click="showCreate = false">Cancel</button>
              <button class="btn-primary" :disabled="creating" @click="doCreate">
                <span v-if="creating" class="spinner-sm" />
                Assign
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Review Dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="reviewMode" class="overlay" @click.self="reviewMode = null">
          <div class="form-dialog">
            <h2 class="form-title" :class="reviewMode === 'approve' ? 'text-green' : 'text-red'">
              {{ reviewMode === 'approve' ? 'Approve KPI' : 'Reject KPI' }}
            </h2>
            <p class="review-sub">{{ reviewTarget?.employee?.nama }} — {{ reviewTarget?.template?.template_name }}</p>
            <label class="form-label">
              {{ reviewMode === 'approve' ? 'Notes (optional)' : 'Rejection Reason *' }}
              <textarea v-model="reviewNote" class="form-input" rows="3" />
            </label>
            <p v-if="reviewError" class="form-error">{{ reviewError }}</p>
            <div class="form-footer">
              <button class="btn-cancel" @click="reviewMode = null">Cancel</button>
              <button class="btn-primary" :class="reviewMode === 'reject' ? 'btn-danger' : ''" :disabled="reviewing" @click="doReview">
                <span v-if="reviewing" class="spinner-sm" />
                {{ reviewMode === 'approve' ? 'Approve' : 'Reject' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Detail Dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="detailRow" class="overlay" @click.self="detailRow = null">
          <div class="form-dialog large">
            <div class="detail-header">
              <div>
                <h2 class="form-title" style="margin-bottom:4px">KPI Assignment Detail</h2>
                <p class="review-sub">{{ detailRow.employee?.nama }} · {{ detailRow.template?.template_name }}</p>
              </div>
              <button class="btn-close" @click="detailRow = null">×</button>
            </div>
            <div class="detail-meta">
              <StatusBadge :status="detailRow.status" />
              <span class="chip">{{ detailRow.period_month ? months[detailRow.period_month-1]+' ' : '' }}{{ detailRow.period_year }}</span>
            </div>
            <table class="ind-table" style="margin-top:16px">
              <thead>
                <tr><th>Indicator</th><th>Target</th><th>Actual</th><th>Achievement</th><th>Weighted</th><th>Status</th></tr>
              </thead>
              <tbody>
                <tr v-for="r in detailRow.results" :key="r.id">
                  <td>{{ r.indicator?.indicator_name }}</td>
                  <td>{{ r.indicator?.target_value }}</td>
                  <td>{{ r.actual_value ?? '—' }}</td>
                  <td>{{ r.achievement_percent != null ? r.achievement_percent.toFixed(1)+'%' : '—' }}</td>
                  <td>{{ r.weighted_score != null ? r.weighted_score.toFixed(2) : '—' }}</td>
                  <td><StatusBadge :status="r.status" /></td>
                </tr>
              </tbody>
            </table>
            <div class="total-row">
              Total Score: <strong>{{ totalScore(detailRow).toFixed(2) }}</strong>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useKpiAssignmentStore } from '@/stores/kpiAssignment'
import { useKpiTemplateStore }   from '@/stores/kpiTemplate'
import { useUserManagementStore } from '@/stores/userManagement'
import DataTable      from '@/components/admin/DataTable.vue'
import StatusBadge    from '@/components/admin/StatusBadge.vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import api from '@/services/api'

const store    = useKpiAssignmentStore()
const tplStore = useKpiTemplateStore()
const usrStore = useUserManagementStore()

const page    = ref(1)
const filters = ref({ status: '', period_year: new Date().getFullYear(), period_month: '' })
const currentYear = new Date().getFullYear()

const months   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
const statuses = ['draft','assigned','submitted','reviewed','approved','rejected']

const showCreate  = ref(false)
const creating    = ref(false)
const createError = ref('')
const createForm  = ref({ employee_id: '', kpi_template_id: '', period_year: currentYear, period_month: null })
const employees   = ref([])
const templates   = ref([])

const reviewMode   = ref(null)
const reviewTarget = ref(null)
const reviewNote   = ref('')
const reviewing    = ref(false)
const reviewError  = ref('')

const detailRow = ref(null)

const columns = [
  { key: 'employee',     label: 'Employee' },
  { key: 'template',     label: 'Template' },
  { key: 'period',       label: 'Period',   width: '110px' },
  { key: 'status',       label: 'Status',   width: '120px' },
  { key: 'submitted_at', label: 'Submitted', width: '130px' },
]

async function loadData() {
  const params = { page: page.value, ...filters.value }
  await store.fetchAll(params)
}

async function openCreate() {
  createError.value = ''
  createForm.value = { employee_id: '', kpi_template_id: '', period_year: currentYear, period_month: null }
  const [eResp, tResp] = await Promise.all([
    api.get('/v2/users', { params: { per_page: 200 } }),
    api.get('/v2/kpi/templates', { params: { is_active: 1, per_page: 200 } }),
  ])
  employees.value = eResp.data.data
  templates.value = tResp.data.data
  showCreate.value = true
}

async function doCreate() {
  createError.value = ''
  creating.value = true
  try {
    await store.create(createForm.value)
    showCreate.value = false
    await loadData()
  } catch (e) {
    createError.value = e.response?.data?.message || 'Failed to assign.'
  } finally {
    creating.value = false
  }
}

function openReview(row, mode) {
  reviewTarget.value = row; reviewMode.value = mode; reviewNote.value = ''; reviewError.value = ''
}
async function doReview() {
  reviewError.value = ''
  reviewing.value = true
  try {
    if (reviewMode.value === 'approve') {
      await store.approve(reviewTarget.value.id, reviewNote.value)
    } else {
      if (!reviewNote.value.trim()) { reviewError.value = 'Reason is required.'; return }
      await store.reject(reviewTarget.value.id, reviewNote.value)
    }
    reviewMode.value = null
    await loadData()
  } catch (e) {
    reviewError.value = e.response?.data?.message || 'Action failed.'
  } finally {
    reviewing.value = false
  }
}

async function openDetail(row) {
  const data = await store.fetchOne(row.id)
  detailRow.value = data
}

function totalScore(row) {
  return (row.results ?? []).reduce((s, r) => s + (r.weighted_score ?? 0), 0)
}

function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }

onMounted(loadData)
</script>

<style scoped>
.page { max-width: 1280px; }
.filter-bar  { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.filter-select, .filter-input { padding: 8px 11px; border: 1px solid #e2e8f0; border-radius: 7px; font-size: 13px; background: #fff; }
.btn-primary { background: #2563EB; color: #fff; padding: 9px 18px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }
.btn-danger  { background: #dc2626 !important; }
.act-btn { background: none; border: none; padding: 4px 8px; cursor: pointer; font-size: 16px; border-radius: 4px; }
.act-btn:hover { background: #f1f5f9; }
.text-green { color: #16a34a; }
.text-red   { color: #dc2626; }
.chip { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 9998; display: flex; align-items: center; justify-content: center; padding: 20px; }
.form-dialog { background: #fff; border-radius: 14px; padding: 28px; max-width: 520px; width: 100%; max-height: 88vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0,0,0,.18); }
.form-dialog.large { max-width: 760px; }
.form-title  { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 16px; }
.text-green  { color: #15803d; }
.form-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-label  { display: flex; flex-direction: column; gap: 5px; font-size: 13px; font-weight: 500; color: #374151; }
.form-input  { padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; outline: none; width: 100%; }
.form-input:focus { border-color: #2563EB; }
.span-2 { grid-column: span 2; }
.form-error  { color: #dc2626; font-size: 13px; margin-top: 10px; }
.form-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
.btn-cancel  { padding: 9px 18px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 14px; cursor: pointer; color: #475569; }
.review-sub  { font-size: 13px; color: #64748b; margin-bottom: 16px; }
.detail-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.detail-meta { display: flex; gap: 8px; margin-bottom: 4px; }
.btn-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; }
.ind-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ind-table th { background: #f8fafc; padding: 8px 12px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; border-bottom: 1px solid #e2e8f0; }
.ind-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.total-row { text-align: right; padding: 10px 12px; font-size: 14px; color: #1e293b; border-top: 2px solid #e2e8f0; margin-top: 4px; }
.spinner-sm { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
