<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">My KPI Submissions</h1>
        <p class="page-sub">Fill in your actual KPI values for assigned periods.</p>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="store.loading" class="loading-state">
      <div class="spinner-lg" /> Loading your assignments...
    </div>

    <!-- Empty state -->
    <div v-else-if="!store.myAssignments.length" class="empty-state">
      <div class="empty-icon">📋</div>
      <p class="empty-title">No KPI assignments found.</p>
      <p class="empty-sub">Contact your HR manager to get KPI templates assigned.</p>
    </div>

    <!-- Assignment cards -->
    <div v-else class="assignments-grid">
      <div
        v-for="a in store.myAssignments"
        :key="a.id"
        class="assignment-card"
        :class="`card-${a.status}`"
      >
        <div class="card-header">
          <div class="card-info">
            <h3 class="card-name">{{ a.template?.template_name }}</h3>
            <p class="card-period">{{ periodLabel(a) }}</p>
          </div>
          <StatusBadge :status="a.status" dot />
        </div>

        <!-- Indicators progress -->
        <div class="indicators-list">
          <div v-for="r in a.results" :key="r.id" class="ind-item">
            <div class="ind-label">{{ r.indicator?.indicator_name }}</div>
            <div class="ind-meta">
              Weight {{ r.indicator?.weight }}% · Target: {{ r.indicator?.target_value }}
            </div>
            <div class="ind-score" v-if="r.achievement_percent != null">
              <div class="score-bar-wrap">
                <div class="score-bar" :style="{ width: Math.min(r.achievement_percent, 100)+'%' }" :class="scoreClass(r.achievement_percent)" />
              </div>
              <span class="score-pct">{{ r.achievement_percent.toFixed(1) }}%</span>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <div class="total-score" v-if="a.status === 'approved'">
            Total: <strong>{{ totalScore(a).toFixed(2) }}</strong>
          </div>
          <button
            v-if="['assigned','rejected'].includes(a.status)"
            class="btn-submit"
            @click="openSubmit(a)"
          >
            {{ a.status === 'rejected' ? 'Resubmit KPI' : 'Submit KPI' }}
          </button>
        </div>

        <!-- Rejection note -->
        <div v-if="a.status === 'rejected' && a.rejection_reason" class="rejection-note">
          <strong>Rejection reason:</strong> {{ a.rejection_reason }}
        </div>
      </div>
    </div>

    <!-- Submit KPI Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="submitting" class="overlay" @click.self="submitting = false">
          <div class="submit-dialog">
            <h2 class="form-title">Submit KPI: {{ submitTarget?.template?.template_name }}</h2>
            <p class="period-label">{{ submitTarget ? periodLabel(submitTarget) : '' }}</p>

            <div
              v-for="r in submitForm"
              :key="r.indicator_id"
              class="submit-row"
            >
              <div class="submit-ind">
                <span class="ind-name">{{ r.indicator_name }}</span>
                <span class="ind-weight">Weight: {{ r.weight }}% · Target: {{ r.target_value }}</span>
              </div>
              <div class="submit-fields">
                <input
                  v-model.number="r.actual_value"
                  type="number"
                  class="form-input"
                  placeholder="Actual value"
                  min="0"
                />
                <input
                  v-model="r.notes"
                  class="form-input"
                  placeholder="Notes (optional)"
                />
              </div>
            </div>

            <p v-if="submitError" class="form-error">{{ submitError }}</p>

            <div class="form-footer">
              <button class="btn-cancel" @click="submitting = false">Cancel</button>
              <button class="btn-primary" :disabled="saving" @click="doSubmit">
                <span v-if="saving" class="spinner-sm" />
                Submit KPI
              </button>
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
import StatusBadge from '@/components/admin/StatusBadge.vue'

const store = useKpiAssignmentStore()
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

const submitting   = ref(false)
const submitTarget = ref(null)
const submitForm   = ref([])
const saving       = ref(false)
const submitError  = ref('')

onMounted(() => store.fetchMine({ per_page: 50 }))

function periodLabel(a) {
  return a.period_month ? `${months[a.period_month - 1]} ${a.period_year}` : `FY ${a.period_year}`
}

function totalScore(a) {
  return (a.results ?? []).reduce((s, r) => s + (r.weighted_score ?? 0), 0)
}

function scoreClass(pct) {
  if (pct >= 90) return 'bar-green'
  if (pct >= 70) return 'bar-blue'
  if (pct >= 50) return 'bar-yellow'
  return 'bar-red'
}

function openSubmit(a) {
  submitTarget.value = a
  submitForm.value = (a.results ?? []).map(r => ({
    indicator_id:  r.indicator_id,
    indicator_name: r.indicator?.indicator_name,
    weight:        r.indicator?.weight,
    target_value:  r.indicator?.target_value,
    actual_value:  r.actual_value ?? '',
    notes:         r.employee_notes ?? '',
  }))
  submitError.value = ''
  submitting.value = true
}

async function doSubmit() {
  submitError.value = ''
  saving.value = true
  try {
    const results = submitForm.value.map(r => ({
      indicator_id:  r.indicator_id,
      actual_value:  r.actual_value,
      notes:         r.notes,
    }))
    await store.submit(submitTarget.value.id, results)
    submitting.value = false
    await store.fetchMine({ per_page: 50 })
  } catch (e) {
    submitError.value = e.response?.data?.message || 'Submission failed.'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.page { padding: 24px; max-width: 960px; margin: 0 auto; }
.page-header { margin-bottom: 24px; }
.page-title { font-size: 22px; font-weight: 800; color: #1e293b; }
.page-sub   { font-size: 13px; color: #94a3b8; margin-top: 2px; }
.loading-state { display: flex; align-items: center; gap: 12px; padding: 40px; color: #64748b; font-size: 14px; }
.spinner-lg { width: 22px; height: 22px; border: 3px solid #e2e8f0; border-top-color: #2563EB; border-radius: 50%; animation: spin .7s linear infinite; }
.empty-state { text-align: center; padding: 60px 20px; }
.empty-icon  { font-size: 48px; margin-bottom: 12px; }
.empty-title { font-size: 16px; font-weight: 600; color: #475569; margin-bottom: 6px; }
.empty-sub   { font-size: 13px; color: #94a3b8; }
.assignments-grid { display: flex; flex-direction: column; gap: 16px; }
.assignment-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.07); border-left: 4px solid #e2e8f0; }
.card-assigned { border-left-color: #2563EB; }
.card-submitted { border-left-color: #d97706; }
.card-approved  { border-left-color: #16a34a; }
.card-rejected  { border-left-color: #dc2626; }
.card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.card-name   { font-size: 15px; font-weight: 700; color: #1e293b; }
.card-period { font-size: 12px; color: #94a3b8; margin-top: 3px; }
.indicators-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.ind-item  { }
.ind-label { font-size: 13px; font-weight: 600; color: #334155; }
.ind-meta  { font-size: 11px; color: #94a3b8; margin-bottom: 5px; }
.ind-score { display: flex; align-items: center; gap: 10px; }
.score-bar-wrap { flex: 1; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; }
.score-bar { height: 6px; border-radius: 3px; transition: width .4s ease; }
.bar-green  { background: #16a34a; }
.bar-blue   { background: #2563EB; }
.bar-yellow { background: #d97706; }
.bar-red    { background: #dc2626; }
.score-pct { font-size: 12px; font-weight: 600; color: #475569; min-width: 40px; text-align: right; }
.card-footer { display: flex; align-items: center; justify-content: space-between; }
.total-score { font-size: 13px; color: #475569; }
.total-score strong { color: #1e293b; font-size: 15px; }
.btn-submit  { background: #2563EB; color: #fff; padding: 7px 16px; border: none; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-submit:hover { background: #1d4ed8; }
.rejection-note { background: #fef2f2; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #b91c1c; margin-top: 12px; }
/* Overlay / Dialog */
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 9998; display: flex; align-items: center; justify-content: center; padding: 20px; overflow-y: auto; }
.submit-dialog { background: #fff; border-radius: 14px; padding: 28px; max-width: 620px; width: 100%; max-height: 88vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0,0,0,.18); }
.form-title  { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.period-label { font-size: 13px; color: #94a3b8; margin-bottom: 20px; }
.submit-row  { background: #f8fafc; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
.submit-ind  { margin-bottom: 8px; }
.ind-name    { font-size: 13px; font-weight: 600; color: #1e293b; }
.ind-weight  { font-size: 11px; color: #94a3b8; display: block; margin-top: 2px; }
.submit-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.form-input  { padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; outline: none; width: 100%; }
.form-input:focus { border-color: #2563EB; }
.form-error  { color: #dc2626; font-size: 13px; margin-top: 10px; }
.form-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
.btn-cancel  { padding: 9px 18px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 14px; cursor: pointer; color: #475569; }
.btn-primary { background: #2563EB; color: #fff; padding: 9px 18px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.spinner-sm  { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
