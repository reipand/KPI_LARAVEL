<template>
  <div class="page">
    <AdminPageHeader title="User Management" description="Manage users and their roles across tenants.">
      <template #actions>
        <button class="btn-primary" @click="openCreate">+ New User</button>
      </template>
    </AdminPageHeader>

    <!-- Stats -->
    <div class="stats-row">
      <MetricCard label="Total Users"   :value="store.meta.total ?? store.users.length" variant="blue" />
      <MetricCard label="Active Roles"  :value="store.roles.length" variant="green" />
      <MetricCard label="Showing"       :value="store.users.length" variant="yellow" />
    </div>

    <DataTable
      :columns="columns"
      :rows="store.users"
      :loading="store.loading"
      :current-page="page"
      :total-pages="store.meta.last_page ?? 1"
      empty-text="No users found."
      @search="q => { search = q; page = 1; loadData() }"
      @page="p => { page = p; loadData() }"
    >
      <template #actions>
        <select v-model="filterRole" class="filter-select" @change="page = 1; loadData()">
          <option value="">All Roles</option>
          <option v-for="r in store.roles" :key="r.name" :value="r.name">{{ formatRole(r.name) }}</option>
        </select>
      </template>

      <template #cell_nama="{ row }">
        <div class="user-cell">
          <span class="user-avatar-sm">{{ initials(row.nama) }}</span>
          <div>
            <div class="user-name">{{ row.nama }}</div>
            <div class="user-nip">{{ row.nip }}</div>
          </div>
        </div>
      </template>

      <template #cell_role="{ row }">
        <span class="role-badge" :class="`role-${roleColor(primaryRole(row))}`">
          {{ formatRole(primaryRole(row)) }}
        </span>
      </template>

      <template #cell_departemen="{ row }">
        {{ row.departemen ?? row.department?.name ?? '—' }}
      </template>

      <template #cell_status_karyawan="{ row }">
        <StatusBadge :status="row.status_karyawan === 'Tetap' ? 'active' : 'inactive'" :label="row.status_karyawan" dot />
      </template>

      <template #row_actions="{ row }">
        <button class="act-btn" title="Edit" @click="openEdit(row)">✏️</button>
        <button class="act-btn text-red" title="Delete" @click="confirmDelete(row)">🗑️</button>
      </template>
    </DataTable>

    <!-- Create / Edit Dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showForm" class="overlay" @click.self="showForm = false">
          <div class="form-dialog">
            <h2 class="form-title">{{ editing ? 'Edit User' : 'New User' }}</h2>

            <div class="form-grid">
              <label class="form-label">
                NIP *
                <input v-model="form.nip" class="form-input" :disabled="!!editing" placeholder="EMP-001" />
              </label>
              <label class="form-label">
                Full Name *
                <input v-model="form.nama" class="form-input" placeholder="Budi Santoso" />
              </label>
              <label class="form-label">
                Email
                <input v-model="form.email" type="email" class="form-input" />
              </label>
              <label class="form-label">
                Phone
                <input v-model="form.no_hp" class="form-input" placeholder="+628..." />
              </label>
              <label class="form-label">
                Position / Title *
                <input v-model="form.jabatan" class="form-input" placeholder="HR Manager" />
              </label>
              <label class="form-label">
                Department *
                <input v-model="form.departemen" class="form-input" placeholder="Human Resources" />
              </label>
              <label class="form-label">
                Employment Status *
                <select v-model="form.status_karyawan" class="form-input">
                  <option value="Tetap">Tetap</option>
                  <option value="Kontrak">Kontrak</option>
                  <option value="Magang">Magang</option>
                  <option value="Outsourcing">Outsourcing</option>
                </select>
              </label>
              <label class="form-label">
                Join Date *
                <input v-model="form.tanggal_masuk" type="date" class="form-input" />
              </label>
              <label class="form-label">
                Role *
                <select v-model="form.role" class="form-input">
                  <option value="">— Select role —</option>
                  <option v-for="r in store.roles" :key="r.name" :value="r.name">{{ formatRole(r.name) }}</option>
                </select>
              </label>
              <label class="form-label">
                Password {{ editing ? '(leave blank to keep)' : '*' }}
                <input v-model="form.password" type="password" class="form-input" :placeholder="editing ? 'unchanged' : 'min 8 chars'" autocomplete="new-password" />
              </label>
            </div>

            <p v-if="formError" class="form-error">{{ formError }}</p>

            <div class="form-footer">
              <button class="btn-cancel" @click="showForm = false">Cancel</button>
              <button class="btn-primary" :disabled="saving" @click="save">
                <span v-if="saving" class="spinner-sm" />
                {{ editing ? 'Save Changes' : 'Create User' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Delete Confirm -->
    <ConfirmDialog
      v-model="showDelete"
      title="Delete User"
      :message="`Are you sure you want to delete ${deleteTarget?.nama}? This cannot be undone.`"
      variant="danger"
      confirm-label="Delete"
      :loading="deleting"
      @confirm="doDelete"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useUserManagementStore } from '@/stores/userManagement'
import DataTable       from '@/components/admin/DataTable.vue'
import StatusBadge     from '@/components/admin/StatusBadge.vue'
import MetricCard      from '@/components/admin/MetricCard.vue'
import ConfirmDialog   from '@/components/admin/ConfirmDialog.vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'

const store      = useUserManagementStore()
const page       = ref(1)
const search     = ref('')
const filterRole = ref('')
const showForm   = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const formError  = ref('')
const showDelete = ref(false)
const deleteTarget = ref(null)
const deleting   = ref(false)

const emptyForm = () => ({
  nip: '', nama: '', email: '', no_hp: '', jabatan: '', departemen: '',
  status_karyawan: 'Tetap', tanggal_masuk: '', role: '', password: '',
})
const form = ref(emptyForm())

const columns = [
  { key: 'nama',            label: 'User',       width: '220px' },
  { key: 'email',           label: 'Email' },
  { key: 'departemen',      label: 'Department', width: '150px' },
  { key: 'role',            label: 'Role',       width: '130px' },
  { key: 'status_karyawan', label: 'Status',     width: '110px' },
]

async function loadData() {
  await store.fetchAll({ page: page.value, search: search.value, role: filterRole.value })
}

function openCreate() {
  editing.value = null
  form.value = emptyForm()
  formError.value = ''
  showForm.value = true
}

function openEdit(row) {
  editing.value = row
  form.value = {
    ...row,
    role: primaryRole(row),
    password: '',
    tanggal_masuk: row.tanggal_masuk ? row.tanggal_masuk.split('T')[0] : '',
  }
  formError.value = ''
  showForm.value = true
}

async function save() {
  formError.value = ''
  saving.value = true
  try {
    const payload = { ...form.value }
    if (editing.value && !payload.password) delete payload.password
    if (editing.value) {
      await store.update(editing.value.id, payload)
    } else {
      await store.create(payload)
    }
    showForm.value = false
    await loadData()
  } catch (e) {
    formError.value = e.response?.data?.message || e.userMessage || 'Failed to save.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(row) {
  deleteTarget.value = row
  showDelete.value = true
}

async function doDelete() {
  deleting.value = true
  try {
    await store.remove(deleteTarget.value.id)
    showDelete.value = false
    await loadData()
  } finally {
    deleting.value = false
  }
}

function primaryRole(row) {
  if (row.role) return row.role
  return row.roles?.[0]?.name ?? ''
}

function formatRole(name) {
  const map = {
    super_admin: 'Super Admin', tenant_admin: 'Tenant Admin',
    hr_manager: 'HR Manager', direktur: 'Direktur',
    pegawai: 'Pegawai', dept_head: 'Dept Head',
    supervisor: 'Supervisor', employee: 'Employee',
  }
  return map[name] ?? name
}

function roleColor(name) {
  const map = {
    super_admin: 'red', tenant_admin: 'purple', hr_manager: 'blue',
    direktur: 'indigo', pegawai: 'gray', dept_head: 'teal',
    supervisor: 'orange', employee: 'gray',
  }
  return map[name] ?? 'gray'
}

function initials(name = '') {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

onMounted(async () => {
  await Promise.all([loadData(), store.fetchRoles()])
})
</script>

<style scoped>
.page { max-width: 1280px; }
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
.filter-select { padding: 7px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; background: #fff; }
.btn-primary { background: #2563EB; color: #fff; padding: 9px 18px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }

/* User cell */
.user-cell { display: flex; align-items: center; gap: 10px; }
.user-avatar-sm { width: 32px; height: 32px; border-radius: 50%; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.user-name { font-size: 13px; font-weight: 600; color: #1e293b; }
.user-nip  { font-size: 11px; color: #94a3b8; margin-top: 1px; }

/* Role badges */
.role-badge { padding: 2px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.role-red    { background: #fee2e2; color: #b91c1c; }
.role-purple { background: #f3e8ff; color: #7e22ce; }
.role-blue   { background: #dbeafe; color: #1d4ed8; }
.role-indigo { background: #e0e7ff; color: #4338ca; }
.role-teal   { background: #ccfbf1; color: #0f766e; }
.role-orange { background: #ffedd5; color: #c2410c; }
.role-gray   { background: #f1f5f9; color: #475569; }

/* Actions */
.act-btn { background: none; border: none; padding: 4px 8px; cursor: pointer; font-size: 16px; border-radius: 4px; }
.act-btn:hover { background: #f1f5f9; }
.text-red { color: #dc2626; }

/* Dialog */
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 9998; display: flex; align-items: center; justify-content: center; padding: 20px; }
.form-dialog { background: #fff; border-radius: 14px; padding: 28px; width: 640px; max-width: 95vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0,0,0,.18); }
.form-title  { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }
.form-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-label  { display: flex; flex-direction: column; gap: 5px; font-size: 13px; font-weight: 500; color: #374151; }
.form-input  { padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; outline: none; }
.form-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.form-input:disabled { background: #f8fafc; color: #94a3b8; }
.form-error  { color: #dc2626; font-size: 13px; margin-top: 10px; }
.form-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
.btn-cancel  { padding: 9px 18px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 14px; cursor: pointer; color: #475569; }
.spinner-sm  { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
@media (max-width: 640px) { .stats-row { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
</style>
