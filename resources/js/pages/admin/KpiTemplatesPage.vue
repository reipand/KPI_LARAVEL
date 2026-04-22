<template>
  <div class="page">
    <AdminPageHeader
      title="KPI Templates"
      description="Configure reusable KPI templates with weighted indicators."
      :show-back="true"
    >
      <template #actions>
        <button class="btn-primary" @click="openCreate">+ New Template</button>
      </template>
    </AdminPageHeader>

    <DataTable
      :columns="columns"
      :rows="store.templates"
      :loading="store.loading"
      :current-page="page"
      :total-pages="store.meta.last_page ?? 1"
      search-placeholder="Search templates..."
      @search="q => { search = q; loadData() }"
      @page="p => { page = p; loadData() }"
    >
      <template #cell_is_active="{ row }">
        <StatusBadge :status="row.is_active ? 'active' : 'inactive'" />
      </template>
      <template #cell_period_type="{ row }">
        <span class="chip">{{ row.period_type }}</span>
      </template>
      <template #cell_indicators="{ row }">
        <span class="badge-count">{{ row.indicators?.length ?? 0 }} indicators</span>
      </template>
      <template #row_actions="{ row }">
        <button class="act-btn" @click="openDetail(row)">👁</button>
        <button class="act-btn" @click="openEdit(row)">✏️</button>
        <button class="act-btn text-red" @click="confirmDelete(row)">🗑</button>
      </template>
    </DataTable>

    <!-- Create / Edit Dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showForm" class="overlay" @click.self="closeForm">
          <div class="form-dialog large">
            <h2 class="form-title">{{ editing ? 'Edit Template' : 'New KPI Template' }}</h2>

            <!-- Template fields -->
            <div class="section-label">Template Info</div>
            <div class="form-grid">
              <label class="form-label span-2">
                Template Name *
                <input v-model="form.template_name" class="form-input" placeholder="e.g. Marketing Monthly KPI" />
              </label>
              <label class="form-label">
                Period Type *
                <select v-model="form.period_type" class="form-input">
                  <option value="monthly">Monthly</option>
                  <option value="quarterly">Quarterly</option>
                  <option value="yearly">Yearly</option>
                </select>
              </label>
              <label class="form-label">
                Status
                <select v-model="form.is_active" class="form-input">
                  <option :value="true">Active</option>
                  <option :value="false">Inactive</option>
                </select>
              </label>
              <label class="form-label span-2">
                Description
                <textarea v-model="form.description" class="form-input" rows="2" />
              </label>
            </div>

            <!-- Indicators (only on create) -->
            <template v-if="!editing">
              <div class="section-label" style="margin-top:20px">
                KPI Indicators
                <span class="weight-total" :class="totalWeight === 100 ? 'ok' : 'warn'">
                  Total: {{ totalWeight.toFixed(1) }}%
                </span>
              </div>

              <div v-for="(ind, idx) in form.indicators" :key="idx" class="indicator-row">
                <div class="ind-num">{{ idx + 1 }}</div>
                <div class="ind-fields">
                  <input v-model="ind.indicator_name" class="form-input" placeholder="Indicator name" />
                  <div class="ind-row2">
                    <input v-model.number="ind.weight" type="number" class="form-input sm" placeholder="Weight %" min="0" max="100" />
                    <select v-model="ind.target_type" class="form-input sm">
                      <option value="number">Number</option>
                      <option value="percent">Percent</option>
                      <option value="boolean">Boolean</option>
                      <option value="rating">Rating</option>
                    </select>
                    <input v-model.number="ind.target_value" type="number" class="form-input sm" placeholder="Target" />
                    <select v-model="ind.scoring_method" class="form-input sm">
                      <option value="higher_is_better">Higher ↑</option>
                      <option value="lower_is_better">Lower ↓</option>
                      <option value="exact_match">Exact =</option>
                    </select>
                  </div>
                </div>
                <button class="ind-remove" @click="removeIndicator(idx)">×</button>
              </div>

              <button class="btn-add-ind" @click="addIndicator">+ Add Indicator</button>
            </template>

            <p v-if="formError" class="form-error">{{ formError }}</p>

            <div class="form-footer">
              <button class="btn-cancel" @click="closeForm">Cancel</button>
              <button class="btn-primary" :disabled="saving" @click="save">
                <span v-if="saving" class="spinner-sm" />
                {{ editing ? 'Save Changes' : 'Create Template' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Detail / Indicators Dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="detailTemplate" class="overlay" @click.self="detailTemplate = null">
          <div class="form-dialog large">
            <div class="detail-header">
              <div>
                <h2 class="form-title" style="margin-bottom:4px">{{ detailTemplate.template_name }}</h2>
                <div class="detail-meta">
                  <StatusBadge :status="detailTemplate.is_active ? 'active' : 'inactive'" />
                  <span class="chip">{{ detailTemplate.period_type }}</span>
                </div>
              </div>
              <button class="btn-close" @click="detailTemplate = null">×</button>
            </div>

            <table class="ind-table">
              <thead>
                <tr>
                  <th>#</th><th>Indicator</th><th>Weight</th><th>Target Type</th><th>Target</th><th>Scoring</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(ind, i) in detailTemplate.indicators" :key="ind.id">
                  <td>{{ i + 1 }}</td>
                  <td>{{ ind.indicator_name }}</td>
                  <td>{{ ind.weight }}%</td>
                  <td><span class="chip">{{ ind.target_type }}</span></td>
                  <td>{{ ind.target_value }}</td>
                  <td><span class="chip">{{ ind.scoring_method.replace(/_/g, ' ') }}</span></td>
                </tr>
              </tbody>
            </table>
            <div class="ind-total">Total weight: <strong>{{ detailTemplate.indicators?.reduce((s, i) => s + i.weight, 0).toFixed(1) }}%</strong></div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <ConfirmDialog
      v-model="showDeleteConfirm"
      title="Delete Template"
      :message="`Delete '${deleteTarget?.template_name}'? This cannot be undone.`"
      :loading="deleting"
      @confirm="doDelete"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useKpiTemplateStore } from '@/stores/kpiTemplate'
import DataTable      from '@/components/admin/DataTable.vue'
import StatusBadge    from '@/components/admin/StatusBadge.vue'
import ConfirmDialog  from '@/components/admin/ConfirmDialog.vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'

const store   = useKpiTemplateStore()
const page    = ref(1)
const search  = ref('')
const showForm = ref(false)
const editing  = ref(null)
const saving   = ref(false)
const formError = ref('')
const detailTemplate = ref(null)
const showDeleteConfirm = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

const emptyForm = () => ({
  template_name: '', description: '', period_type: 'monthly', is_active: true,
  indicators: [emptyInd()],
})
const emptyInd = () => ({
  indicator_name: '', weight: 0, target_type: 'number',
  target_value: 0, scoring_method: 'higher_is_better',
})
const form = ref(emptyForm())

const totalWeight = computed(() => form.value.indicators?.reduce((s, i) => s + (Number(i.weight) || 0), 0) ?? 0)

const columns = [
  { key: 'template_name', label: 'Template Name' },
  { key: 'period_type',   label: 'Period',    width: '110px' },
  { key: 'indicators',    label: 'Indicators', width: '120px' },
  { key: 'is_active',     label: 'Status',    width: '100px' },
]

async function loadData() {
  await store.fetchAll({ page: page.value, search: search.value })
}

function openCreate() {
  editing.value = null; form.value = emptyForm(); formError.value = ''; showForm.value = true
}
function openEdit(row) {
  editing.value = row; form.value = { ...row }; formError.value = ''; showForm.value = true
}
function closeForm() { showForm.value = false }

async function openDetail(row) {
  const data = await store.fetchOne(row.id)
  detailTemplate.value = data
}

function addIndicator() { form.value.indicators.push(emptyInd()) }
function removeIndicator(idx) { form.value.indicators.splice(idx, 1) }

async function save() {
  formError.value = ''
  saving.value = true
  try {
    if (editing.value) {
      await store.update(editing.value.id, form.value)
    } else {
      await store.create(form.value)
    }
    showForm.value = false
    await loadData()
  } catch (e) {
    formError.value = e.response?.data?.message || e.userMessage || 'Save failed.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(row) { deleteTarget.value = row; showDeleteConfirm.value = true }
async function doDelete() {
  deleting.value = true
  try {
    await store.remove(deleteTarget.value.id)
    showDeleteConfirm.value = false
    await loadData()
  } finally {
    deleting.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.page { max-width: 1280px; }
.btn-primary { background: #2563EB; color: #fff; padding: 9px 18px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }
.act-btn { background: none; border: none; padding: 4px 8px; cursor: pointer; font-size: 16px; border-radius: 4px; }
.act-btn:hover { background: #f1f5f9; }
.text-red { color: #dc2626; }
.chip { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.badge-count { background: #dbeafe; color: #1d4ed8; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
/* Overlay / Dialog */
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 9998; display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 20px; }
.form-dialog { background: #fff; border-radius: 14px; padding: 28px; max-width: 560px; width: 100%; max-height: 88vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0,0,0,.18); }
.form-dialog.large { max-width: 760px; }
.form-title  { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 16px; }
.section-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
.weight-total { font-size: 12px; font-weight: 700; padding: 2px 10px; border-radius: 6px; }
.weight-total.ok   { background: #dcfce7; color: #15803d; }
.weight-total.warn { background: #fee2e2; color: #b91c1c; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-label { display: flex; flex-direction: column; gap: 5px; font-size: 13px; font-weight: 500; color: #374151; }
.form-input { padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; outline: none; width: 100%; }
.form-input:focus { border-color: #2563EB; }
.form-input.sm { padding: 6px 8px; font-size: 12px; }
.span-2 { grid-column: span 2; }
.indicator-row { display: flex; gap: 10px; align-items: flex-start; padding: 10px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px; }
.ind-num { width: 24px; height: 24px; background: #2563EB; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; margin-top: 8px; }
.ind-fields { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.ind-row2 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 6px; }
.ind-remove { background: none; border: none; font-size: 20px; color: #dc2626; cursor: pointer; padding: 4px 6px; }
.btn-add-ind { background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 8px 16px; font-size: 13px; color: #475569; cursor: pointer; width: 100%; margin-top: 4px; }
.btn-add-ind:hover { background: #e2e8f0; }
.form-error  { color: #dc2626; font-size: 13px; margin-top: 10px; }
.form-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
.btn-cancel  { padding: 9px 18px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 14px; cursor: pointer; color: #475569; }
.detail-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.detail-meta { display: flex; gap: 8px; align-items: center; margin-top: 6px; }
.btn-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; }
.ind-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ind-table th { background: #f8fafc; padding: 8px 12px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; border-bottom: 1px solid #e2e8f0; }
.ind-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.ind-total { text-align: right; padding: 10px 12px; font-size: 13px; color: #475569; }
.spinner-sm { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
