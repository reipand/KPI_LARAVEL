<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Avatar from '@/components/ui/Avatar.vue';
import { useEmployeeStore } from '@/stores/employee';
import { useDepartmentStore } from '@/stores/department';
import { usePositionStore } from '@/stores/position';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const store     = useEmployeeStore();
const deptStore = useDepartmentStore();
const posStore  = usePositionStore();
const auth      = useAuthStore();
const toast     = useToast();

const employees   = computed(() => store.employees);
const showForm    = ref(false);
const editMode    = ref(false);
const selectedId  = ref(null);
const submitting  = ref(false);
const formError   = ref('');
const deleteState        = ref({ open: false, id: null, name: '' });
const deleting           = ref(false);
const syncingHierarchy   = ref(false);

const emptyForm = () => ({
    nip:             '',
    nama:            '',
    jabatan:         '',      // auto-filled from position, sent to API
    departemen:      '',      // auto-filled from department, sent to API
    tenant_id:       null,
    department_id:   null,
    position_id:     null,
    status_karyawan: 'Tetap',
    is_active:       true,
    tanggal_masuk:   '',
    no_hp:           '',
    email:           '',
    role:            'employee',
});

const form   = reactive(emptyForm());
const errors = reactive({});
const canSelectTenant = computed(() => ['hr_manager', 'direktur', 'super_admin'].includes(auth.user?.role));
const tenantOptions = computed(() => auth.myTenants ?? []);
const allowedRoleOptions = computed(() => (
    auth.user?.role === 'tenant_admin'
        ? [
            { value: 'employee', label: 'Pegawai' },
            { value: 'tenant_admin', label: 'HR Perusahaan' },
        ]
        : [
            { value: 'employee', label: 'Pegawai' },
            { value: 'tenant_admin', label: 'HR Perusahaan' },
            { value: 'hr_manager', label: 'HR Global' },
            { value: 'direktur', label: 'Direktur' },
        ]
));

// When dept changes → fill departemen string & reset position
watch(() => form.department_id, (id) => {
    if (syncingHierarchy.value) return;
    const dept = deptStore.findById(id);
    form.departemen  = dept?.nama ?? '';
    form.position_id = null;
    form.jabatan     = '';
});

// When position changes → fill jabatan string
watch(() => form.position_id, (id) => {
    if (syncingHierarchy.value) return;
    const pos = posStore.findById(id);
    form.jabatan = pos?.nama ?? '';
});

watch(() => form.tenant_id, async (tenantId, oldTenantId) => {
    if (syncingHierarchy.value || !tenantId || tenantId === oldTenantId) return;
    form.department_id = null;
    form.position_id = null;
    form.departemen = '';
    form.jabatan = '';

    await Promise.all([
        deptStore.fetchDepartments({ tenant_id: tenantId }),
        posStore.fetchPositions({ tenant_id: tenantId }),
    ]);
});

// Filtered positions based on department
const filteredPositions = computed(() =>
    form.department_id
        ? posStore.positions.filter(p => p.department_id === form.department_id)
        : posStore.positions
);

onMounted(async () => {
    if (!auth.myTenants?.length) {
        await auth.fetchMyTenants().catch(() => {});
    }

    const initialTenantId = auth.activeTenantId || auth.user?.tenant_id || null;

    await Promise.all([
        store.fetchEmployees(),
        deptStore.fetchDepartments(initialTenantId ? { tenant_id: initialTenantId } : {}),
        posStore.fetchPositions(initialTenantId ? { tenant_id: initialTenantId } : {}),
    ]);
});

watch(() => auth.activeTenantId, async (tenantId, oldTenantId) => {
    if (!tenantId || tenantId === oldTenantId) return;

    if (!showForm.value) {
        await Promise.all([
            store.fetchEmployees(),
            deptStore.fetchDepartments({ tenant_id: tenantId }),
            posStore.fetchPositions({ tenant_id: tenantId }),
        ]);
        return;
    }

    form.tenant_id = Number(tenantId);
});

function resetForm() {
    Object.assign(form, emptyForm());
    form.tenant_id = auth.activeTenantId ? Number(auth.activeTenantId) : (auth.user?.tenant_id ?? null);
    formError.value = '';
    Object.keys(errors).forEach(k => { errors[k] = ''; });
}

function openCreate() {
    editMode.value   = false;
    selectedId.value = null;
    resetForm();
    showForm.value   = true;
}

function openEdit(emp) {
    syncingHierarchy.value = true;   // must be set BEFORE resetForm so reset-watchers are blocked
    editMode.value   = true;
    selectedId.value = emp.id;
    resetForm();
    Object.assign(form, {
        nip:             emp.nip ?? '',
        nama:            emp.nama ?? '',
        jabatan:         emp.jabatan ?? '',
        departemen:      emp.departemen ?? '',
        tenant_id:       emp.tenant_id ?? null,
        department_id:   emp.department_id ?? null,
        position_id:     emp.position_id ?? null,
        status_karyawan: emp.status_karyawan ?? 'Tetap',
        is_active:       emp.is_active ?? true,
        tanggal_masuk:   emp.tanggal_masuk ?? '',
        no_hp:           emp.no_hp ?? '',
        email:           emp.email ?? '',
        role:            emp.role ?? 'employee',
    });
    if (emp.tenant_id) {
        Promise.all([
            deptStore.fetchDepartments({ tenant_id: emp.tenant_id }),
            posStore.fetchPositions({ tenant_id: emp.tenant_id }),
        ]).finally(() => {
            nextTick(() => { syncingHierarchy.value = false; });
        });
    } else {
        nextTick(() => { syncingHierarchy.value = false; });
    }
    // Turn off flag AFTER the async watcher flush, not synchronously
    showForm.value = true;
}

function validate() {
    Object.assign(errors, { nip: '', nama: '', jabatan: '', departemen: '', tenant_id: '', tanggal_masuk: '', role: '' });
    let valid = true;
    [['nip','NIP'], ['nama','Nama'], ['jabatan','Jabatan'], ['departemen','Departemen'],
     ['tanggal_masuk','Tanggal masuk'], ['role','Role']].forEach(([f, label]) => {
        if (!String(form[f] ?? '').trim()) {
            errors[f] = `${label} wajib diisi.`;
            valid = false;
        }
    });
    if (canSelectTenant.value && !form.tenant_id) {
        errors.tenant_id = 'Perusahaan wajib dipilih.';
        valid = false;
    }
    return valid;
}

async function submit() {
    if (!validate()) return;
    submitting.value = true;
    formError.value  = '';
    try {
        if (editMode.value) {
            await store.updateEmployee(selectedId.value, { ...form });
            toast.success('Data pegawai berhasil diperbarui.');
        } else {
            await store.createEmployee({ ...form });
            toast.success('Pegawai berhasil ditambahkan.');
        }
        showForm.value = false;
        await store.fetchEmployees();
    } catch (err) {
        formError.value = err.response?.data?.message || 'Gagal menyimpan data pegawai.';
    } finally {
        submitting.value = false;
    }
}

function askDelete(emp) {
    deleteState.value = { open: true, id: emp.id, name: emp.nama };
}

async function confirmDelete() {
    deleting.value = true;
    try {
        await store.deleteEmployee(deleteState.value.id);
        toast.success('Pegawai berhasil dihapus.');
        deleteState.value.open = false;
    } catch (err) {
        toast.error(err.response?.data?.message || 'Gagal menghapus pegawai.');
    } finally {
        deleting.value = false;
    }
}

const roleBadge = { employee: 'badge-info', tenant_admin: 'badge-warning', hr_manager: 'badge-warning', direktur: 'badge-success' };
const roleLabel = { employee: 'Pegawai', tenant_admin: 'HR Perusahaan', hr_manager: 'HR Global', direktur: 'Direktur' };
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">+ Tambah Pegawai</button>
        </template>

        <section class="page-hero">
            <div>
                <div class="page-hero-meta">HR Panel</div>
                <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Manajemen Pegawai</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                    Kelola data karyawan, jabatan, dan hak akses aplikasi.
                </p>
            </div>
        </section>

        <!-- Table -->
        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Daftar Pegawai</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ employees.length }} pegawai terdaftar</h3>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 6" :key="i" class="h-14 rounded-2xl" />
                    </div>
                </template>

                <template v-else-if="employees.length">
                    <div class="space-y-3">
                        <div
                            v-for="emp in employees"
                            :key="emp.id"
                            class="data-row"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <Avatar :name="emp.nama" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ emp.nama }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ emp.nip }}
                                        <template v-if="emp.tenant?.tenant_name"> · {{ emp.tenant.tenant_name }}</template>
                                        <template v-if="emp.jabatan"> · {{ emp.jabatan }}</template>
                                        <template v-if="emp.departemen"> · {{ emp.departemen }}</template>
                                    </p>
                                </div>
                            </div>

                            <div class="hidden min-w-[180px] truncate text-sm text-slate-600 md:block">
                                {{ emp.email || '-' }}
                            </div>

                            <div class="hidden min-w-[110px] md:block">
                                <span :class="roleBadge[emp.role] ?? 'badge-info'">
                                    {{ roleLabel[emp.role] ?? emp.role }}
                                </span>
                            </div>

                            <div class="hidden min-w-[110px] md:block">
                                <span :class="emp.is_active ? 'badge-success' : 'badge-neutral'">
                                    {{ emp.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <button class="btn-secondary !px-3 !py-1.5 text-xs" @click="openEdit(emp)">Edit</button>
                                <button class="btn-danger !px-3 !py-1.5 text-xs" @click="askDelete(emp)">Hapus</button>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="py-14 text-center text-sm text-slate-400">
                    Belum ada data pegawai.
                </div>
            </div>
        </section>

        <!-- ── Form Dialog ─────────────────────────────────────────────── -->
        <Dialog v-model:open="showForm" :title="editMode ? 'Edit Pegawai' : 'Tambah Pegawai'" class="w-full max-w-lg sm:max-w-2xl">
            <Alert v-if="formError" variant="danger" class="mb-4">{{ formError }}</Alert>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">

                <!-- NIP -->
                <div>
                    <label class="form-label">NIP <span class="text-red-500">*</span></label>
                    <Input v-model="form.nip" placeholder="Contoh: BASS-IT-01-2024" />
                    <p v-if="errors.nip" class="mt-1 text-xs text-red-500">{{ errors.nip }}</p>
                </div>

                <!-- Nama -->
                <div>
                    <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                    <Input v-model="form.nama" placeholder="Nama lengkap pegawai" />
                    <p v-if="errors.nama" class="mt-1 text-xs text-red-500">{{ errors.nama }}</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="form-label">Perusahaan <span v-if="canSelectTenant" class="text-red-500">*</span></label>
                    <select v-if="canSelectTenant" v-model="form.tenant_id" class="form-input">
                        <option :value="null">— Pilih Perusahaan —</option>
                        <option v-for="tenant in tenantOptions" :key="tenant.id" :value="tenant.id">
                            {{ tenant.tenant_name }}
                        </option>
                    </select>
                    <div v-else class="form-input bg-slate-50 text-slate-600">
                        {{ auth.activeTenant?.tenant_name || 'Perusahaan aktif' }}
                    </div>
                    <p v-if="errors.tenant_id" class="mt-1 text-xs text-red-500">{{ errors.tenant_id }}</p>
                </div>

                <!-- Departemen -->
                <div>
                    <label class="form-label">Departemen <span class="text-red-500">*</span></label>
                    <select v-model="form.department_id" class="form-input">
                        <option :value="null">— Pilih Departemen —</option>
                        <option v-for="d in deptStore.departments" :key="d.id" :value="d.id">{{ d.nama }}</option>
                    </select>
                    <p v-if="errors.departemen" class="mt-1 text-xs text-red-500">{{ errors.departemen }}</p>
                </div>

                <!-- Jabatan -->
                <div>
                    <label class="form-label">Jabatan <span class="text-red-500">*</span></label>
                    <select v-model="form.position_id" class="form-input">
                        <option :value="null">— Pilih Jabatan —</option>
                        <option v-for="p in filteredPositions" :key="p.id" :value="p.id">
                            {{ p.nama }}
                        </option>
                    </select>
                    <p v-if="errors.jabatan" class="mt-1 text-xs text-red-500">{{ errors.jabatan }}</p>
                </div>

                <!-- Status Karyawan -->
                <div>
                    <label class="form-label">Status Karyawan</label>
                    <select v-model="form.status_karyawan" class="form-input">
                        <option value="Tetap">Tetap</option>
                        <option value="Kontrak">Kontrak</option>
                        <option value="Magang">Magang</option>
                        <option value="Paruh Waktu">Paruh Waktu</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Status Kerja</label>
                    <label class="mt-2 flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-3 text-sm text-slate-700">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600"
                        />
                        <span>Masih aktif bekerja di perusahaan</span>
                    </label>
                </div>

                <!-- Tanggal Masuk -->
                <div>
                    <label class="form-label">Tanggal Masuk <span class="text-red-500">*</span></label>
                    <input v-model="form.tanggal_masuk" type="date" class="form-input" />
                    <p v-if="errors.tanggal_masuk" class="mt-1 text-xs text-red-500">{{ errors.tanggal_masuk }}</p>
                </div>

                <!-- No HP -->
                <div>
                    <label class="form-label">No. HP</label>
                    <Input v-model="form.no_hp" placeholder="08xx-xxxx-xxxx" />
                </div>

                <!-- Email -->
                <div>
                    <label class="form-label">Email</label>
                    <Input v-model="form.email" type="email" placeholder="nama@bass.co.id" />
                </div>

                <!-- Role -->
                <div class="sm:col-span-2">
                    <label class="form-label">Role <span class="text-red-500">*</span></label>
                    <select v-model="form.role" class="form-input">
                        <option v-for="role in allowedRoleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
                    </select>
                    <p v-if="errors.role" class="mt-1 text-xs text-red-500">{{ errors.role }}</p>
                </div>

            </div>

            <!-- hint: jabatan & departemen auto-filled -->
            <p v-if="form.jabatan || form.departemen" class="mt-3 rounded-lg bg-blue-50 px-3 py-2 text-[11px] text-blue-600">
                Akan disimpan sebagai: <strong>{{ form.jabatan || '–' }}</strong> · <strong>{{ form.departemen || '–' }}</strong>
            </p>

            <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                <button class="btn-secondary w-full sm:w-auto" :disabled="submitting" @click="showForm = false">Batal</button>
                <button class="btn-primary w-full sm:w-auto" :disabled="submitting" @click="submit">
                    {{ submitting ? 'Menyimpan...' : editMode ? 'Perbarui Pegawai' : 'Simpan Pegawai' }}
                </button>
            </div>
        </Dialog>

        <!-- ── Delete Dialog ───────────────────────────────────────────── -->
        <Dialog v-model:open="deleteState.open" title="Hapus Pegawai" class="w-full">
            <p class="mt-3 text-sm text-slate-600">
                Hapus <strong>{{ deleteState.name }}</strong> dari sistem? Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                <button class="btn-secondary w-full sm:w-auto" :disabled="deleting" @click="deleteState.open = false">Batal</button>
                <button class="btn-danger w-full sm:w-auto" :disabled="deleting" @click="confirmDelete">
                    {{ deleting ? 'Menghapus...' : 'Ya, Hapus' }}
                </button>
            </div>
        </Dialog>
    </AppLayout>
</template>
