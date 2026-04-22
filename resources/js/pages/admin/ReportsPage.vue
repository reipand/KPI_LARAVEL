<template>
  <div class="page">
    <AdminPageHeader
      title="Reports & Export"
      description="Generate KPI summary reports and export as PDF."
      :show-back="true"
    />

    <!-- Report Selector Tabs -->
    <div class="tab-bar">
      <button v-for="t in tabs" :key="t.id" class="tab-btn" :class="{ active: activeTab === t.id }" @click="activeTab = t.id">
        {{ t.label }}
      </button>
    </div>

    <!-- KPI Summary Report -->
    <div v-if="activeTab === 'summary'" class="report-panel">
      <h2 class="panel-title">KPI Summary Report</h2>
      <div class="report-filters">
        <label class="filter-label">
          Year
          <input v-model.number="summaryFilters.year" type="number" class="form-input sm" :placeholder="currentYear" />
        </label>
        <label class="filter-label">
          Month (optional)
          <select v-model="summaryFilters.month" class="form-input sm">
            <option :value="null">Full Year</option>
            <option v-for="(m, i) in months" :key="i+1" :value="i+1">{{ m }}</option>
          </select>
        </label>
        <button class="btn-primary" :disabled="loadingSummary" @click="loadSummary">
          <span v-if="loadingSummary" class="spinner-sm" /> Generate
        </button>
        <button v-if="summaryData" class="btn-export" @click="exportSummaryPdf">
          📄 Export PDF
        </button>
      </div>

      <div v-if="summaryData" class="summary-content">
        <div class="summary-stats">
          <div class="stat-box">
            <span class="stat-label">Period</span>
            <span class="stat-value">{{ summaryData.period }}</span>
          </div>
          <div class="stat-box blue">
            <span class="stat-label">Total Assignments</span>
            <span class="stat-value">{{ summaryData.total_assignments }}</span>
          </div>
          <div class="stat-box green">
            <span class="stat-label">Approved</span>
            <span class="stat-value">{{ summaryData.approved }}</span>
          </div>
          <div class="stat-box yellow">
            <span class="stat-label">Avg Score</span>
            <span class="stat-value">{{ summaryData.avg_score?.toFixed(1) }}%</span>
          </div>
          <div class="stat-box">
            <span class="stat-label">Highest</span>
            <span class="stat-value">{{ summaryData.highest_score?.toFixed(1) }}%</span>
          </div>
          <div class="stat-box">
            <span class="stat-label">Lowest</span>
            <span class="stat-value">{{ summaryData.lowest_score?.toFixed(1) }}%</span>
          </div>
        </div>

        <h3 class="dept-title">Department Breakdown</h3>
        <table class="report-table">
          <thead>
            <tr>
              <th>Department</th><th>Employees</th><th>Avg Score</th><th>Highest</th><th>Lowest</th>
              <th style="width:140px">Score Bar</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="dept in summaryData.department_breakdown" :key="dept.department">
              <td>{{ dept.department }}</td>
              <td>{{ dept.total_emp }}</td>
              <td><strong>{{ dept.avg_score?.toFixed(1) }}%</strong></td>
              <td>{{ dept.max_score?.toFixed(1) }}%</td>
              <td>{{ dept.min_score?.toFixed(1) }}%</td>
              <td>
                <div class="bar-wrap">
                  <div class="bar" :style="{ width: Math.min(dept.avg_score, 100)+'%' }" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Employee Performance Report -->
    <div v-if="activeTab === 'employee'" class="report-panel">
      <h2 class="panel-title">Employee Performance Report</h2>
      <div class="report-filters">
        <label class="filter-label">
          Employee
          <select v-model="empFilters.employee_id" class="form-input sm">
            <option value="">— Select —</option>
            <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.nama }}</option>
          </select>
        </label>
        <label class="filter-label">
          Year
          <input v-model.number="empFilters.year" type="number" class="form-input sm" :placeholder="currentYear" />
        </label>
        <button class="btn-primary" :disabled="loadingEmp" @click="loadEmployee">
          <span v-if="loadingEmp" class="spinner-sm" /> Generate
        </button>
        <button v-if="empData" class="btn-export" @click="exportEmpPdf">📄 Export PDF</button>
      </div>

      <div v-if="empData" class="emp-content">
        <div class="emp-profile">
          <div class="emp-avatar">{{ empData.employee.name?.charAt(0) }}</div>
          <div>
            <p class="emp-name">{{ empData.employee.name }}</p>
            <p class="emp-sub">{{ empData.employee.position }} · {{ empData.employee.department }}</p>
          </div>
          <div class="emp-grade" :class="`grade-${empData.final_grade}`">
            {{ empData.final_grade }}
          </div>
          <div class="emp-score">{{ empData.annual_avg?.toFixed(1) }}% avg</div>
        </div>

        <table class="report-table">
          <thead>
            <tr><th>Period</th><th>Status</th><th>Score</th><th>Grade</th></tr>
          </thead>
          <tbody>
            <tr v-for="m in empData.monthly_data" :key="m.period">
              <td>{{ m.period }}</td>
              <td><span class="status-pill" :class="`pill-${m.status}`">{{ m.status }}</span></td>
              <td><strong>{{ m.total_score?.toFixed(2) }}</strong></td>
              <td><span class="grade-pill" :class="`grade-${m.grade}`">{{ m.grade }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { downloadFile } from '@/services/api'

const currentYear = new Date().getFullYear()
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

const activeTab = ref('summary')
const tabs = [
  { id: 'summary',  label: 'KPI Summary' },
  { id: 'employee', label: 'Employee Performance' },
]

const summaryFilters  = ref({ year: currentYear, month: null })
const summaryData     = ref(null)
const loadingSummary  = ref(false)

const empFilters   = ref({ employee_id: '', year: currentYear })
const empData      = ref(null)
const loadingEmp   = ref(false)
const employees    = ref([])

async function loadSummary() {
  loadingSummary.value = true
  try {
    const { data } = await api.get('/v2/reports/kpi-summary', { params: summaryFilters.value })
    summaryData.value = data.data
  } finally {
    loadingSummary.value = false
  }
}

async function loadEmployee() {
  if (!empFilters.value.employee_id) return
  loadingEmp.value = true
  try {
    const { data } = await api.get('/v2/reports/employee-performance', { params: empFilters.value })
    empData.value = data.data
  } finally {
    loadingEmp.value = false
  }
}

async function exportSummaryPdf() {
  await downloadFile('/v2/reports/export/summary-pdf', {
    params: summaryFilters.value,
    fallbackFilename: `kpi_summary_${summaryFilters.value.year}.pdf`,
  })
}

async function exportEmpPdf() {
  await downloadFile('/v2/reports/export/employee-pdf', {
    params: empFilters.value,
    fallbackFilename: `kpi_employee_${empFilters.value.year}.pdf`,
  })
}

onMounted(async () => {
  const { data } = await api.get('/v2/users', { params: { per_page: 200 } })
  employees.value = data.data
})
</script>

<style scoped>
.page { padding: 24px; max-width: 1100px; margin: 0 auto; }

.page-sub   { font-size: 13px; color: #94a3b8; margin-top: 2px; }
.tab-bar { display: flex; gap: 4px; background: #f1f5f9; border-radius: 10px; padding: 4px; margin-bottom: 20px; width: fit-content; }
.tab-btn { padding: 8px 20px; border: none; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; background: transparent; color: #64748b; }
.tab-btn.active { background: #fff; color: #1e293b; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.report-panel { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
.panel-title { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 16px; }
.report-filters { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 24px; }
.filter-label { display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600; color: #475569; }
.form-input.sm { padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; outline: none; }
.btn-primary { background: #2563EB; color: #fff; padding: 9px 16px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-export  { background: #f1f5f9; color: #475569; padding: 9px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-export:hover { background: #e2e8f0; }
.summary-stats { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 24px; }
.stat-box { background: #f8fafc; border-radius: 10px; padding: 14px; text-align: center; }
.stat-box.blue   { background: #dbeafe; }
.stat-box.green  { background: #dcfce7; }
.stat-box.yellow { background: #fef9c3; }
.stat-label { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
.stat-value { font-size: 20px; font-weight: 800; color: #1e293b; }
.dept-title { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 12px; }
.report-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.report-table th { background: #f8fafc; padding: 9px 14px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; border-bottom: 1px solid #e2e8f0; }
.report-table td { padding: 9px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.bar-wrap { background: #e2e8f0; height: 8px; border-radius: 4px; }
.bar { background: #2563EB; height: 8px; border-radius: 4px; }
.emp-content {}
.emp-profile { display: flex; align-items: center; gap: 16px; padding: 16px; background: #f8fafc; border-radius: 10px; margin-bottom: 20px; }
.emp-avatar { width: 48px; height: 48px; background: #2563EB; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; flex-shrink: 0; }
.emp-name { font-size: 15px; font-weight: 700; color: #1e293b; }
.emp-sub  { font-size: 12px; color: #64748b; margin-top: 2px; }
.emp-grade { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; margin-left: auto; }
.grade-A { background: #dcfce7; color: #15803d; }
.grade-B { background: #dbeafe; color: #1d4ed8; }
.grade-C { background: #fef9c3; color: #a16207; }
.grade-D { background: #ffedd5; color: #c2410c; }
.grade-E { background: #fee2e2; color: #b91c1c; }
.emp-score { font-size: 22px; font-weight: 800; color: #1e293b; margin-left: 8px; }
.status-pill { padding: 2px 8px; border-radius: 99px; font-size: 11px; font-weight: 600; text-transform: capitalize; background: #f1f5f9; color: #475569; }
.pill-approved { background: #dcfce7; color: #15803d; }
.pill-submitted { background: #dbeafe; color: #1d4ed8; }
.pill-rejected  { background: #fee2e2; color: #b91c1c; }
.grade-pill { width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 12px; font-weight: 800; }
.spinner-sm { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 720px) { .summary-stats { grid-template-columns: repeat(3, 1fr); } }
</style>
