<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useTheme } from '@/composables/useTheme';
import NotificationBell from '@/components/shared/NotificationBell.vue';
import TenantSwitcher   from '@/components/admin/TenantSwitcher.vue';

defineEmits(['open-sidebar', 'toggle-sidebar']);

const route = useRoute();
const auth = useAuthStore();
const { isDark, toggle: toggleTheme } = useTheme();
const user = computed(() => auth.user);

// ── User dropdown ────────────────────────────────────────────────────────────
const showUserMenu = ref(false);
const userMenuRef  = ref(null);

function toggleUserMenu() {
    showUserMenu.value = !showUserMenu.value;
}

function handleClickOutside(e) {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        showUserMenu.value = false;
    }
}

async function logout() {
    showUserMenu.value = false;
    await auth.logout();
}

// ── Live / Echo connectivity indicator ──────────────────────────────────────
const isLive = ref(false);

function checkEcho() {
    isLive.value = !!(window.Echo?.connector?.pusher?.connection?.state === 'connected');
}

let echoTimer = null;
onMounted(() => {
    checkEcho();
    echoTimer = setInterval(checkEcho, 3000);
    document.addEventListener('click', handleClickOutside);
});
onUnmounted(() => {
    clearInterval(echoTimer);
    document.removeEventListener('click', handleClickOutside);
});

// ── Page title map ───────────────────────────────────────────────────────────
const pageMap = {
    '/dashboard': 'Beranda',
    '/pekerjaan': 'Input Pekerjaan',
    '/laporan-kpi': 'Laporan KPI',
    '/progress-kpi': 'Progress KPI',
    '/hr/dashboard': 'Beranda HR',
    '/hr/pegawai': 'Manajemen Pegawai',
    '/hr/mapping': 'Mapping KPI',
    '/hr/jabatan': 'Manajemen Jabatan',
    '/hr/kpi-components': 'Komponen KPI',
    '/hr/sla': 'SLA Pekerjaan',
    '/hr/settings': 'Pengaturan',
    '/hr/departemen': 'Manajemen Departemen',
    '/hr/analytics': 'Analytics KPI',
    '/hr/work-monitor': 'Monitoring Pekerjaan',
    '/hr/laporan-review': 'Tinjau Laporan KPI',
    '/hr/kpi-pegawai': 'Detail KPI Pegawai',
    '/direktur/dashboard': 'Executive Dashboard',
    '/direktur/analytics': 'Analytics',
    '/direktur/ranking': 'Ranking KPI Pegawai',
    '/notifikasi': 'Notifikasi',
};

const pageTitle    = computed(() => pageMap[route.path] ?? 'Dashboard KPI');
const avatarLetter = computed(() => (user.value?.nama || 'U').slice(0, 1).toUpperCase());

onMounted(() => {
    // Refresh tenant list jika user punya multi-tenant access
    if (auth.isLoggedIn) {
        auth.fetchMyTenants().catch(() => {});
    }
});
</script>

<template>
    <header class="app-topbar">

        <!-- Left: menu button + title ───────────────────────────────────── -->
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
            <!-- Mobile hamburger -->
            <button
                type="button"
                class="topbar-menu-button md:hidden"
                aria-label="Buka menu"
                @click="$emit('open-sidebar')"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>

            <div class="min-w-0">
                <h1 class="truncate text-[13px] font-semibold leading-tight text-slate-900 sm:text-[14.5px] dark:text-slate-100">
                    {{ pageTitle }}
                </h1>
                <p class="hidden truncate text-[11px] text-slate-400 sm:block dark:text-slate-500">
                    {{ auth.activeTenant?.tenant_name ?? 'KPI Dashboard' }}
                </p>
            </div>
        </div>

        <!-- Slot for extra page actions (collapses on tiny screens) -->
        <div class="hidden shrink-0 sm:flex">
            <slot name="actions" />
        </div>

        <!-- Right: live dot + dark toggle + notifications + user ─────────── -->
        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">

            <!-- Live indicator — md+ only -->
            <div
                class="hidden items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 md:flex dark:border-slate-700 dark:bg-slate-900"
                :title="isLive ? 'Realtime aktif (WebSocket)' : 'Realtime via polling'"
            >
                <span
                    :class="[
                        'h-1.5 w-1.5 rounded-full',
                        isLive
                            ? 'bg-emerald-500 shadow-[0_0_0_2px_rgba(16,185,129,0.25)] animate-pulse'
                            : 'bg-amber-400',
                    ]"
                />
                <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ isLive ? 'Live' : 'Polling' }}
                </span>
            </div>

            <!-- Dark mode toggle -->
            <button
                type="button"
                class="topbar-icon-btn"
                :title="isDark ? 'Mode terang' : 'Mode gelap'"
                @click="toggleTheme"
            >
                <svg v-if="isDark" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                </svg>
                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79Z"/>
                </svg>
            </button>

            <!-- Tenant Switcher — hanya muncul jika user rangkap di >1 tenant -->
            <TenantSwitcher />

            <!-- Notifications -->
            <NotificationBell />

            <!-- User chip + dropdown — sm+ only -->
            <div ref="userMenuRef" class="relative hidden sm:block">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                    @click.stop="toggleUserMenu"
                >
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-red-600 text-[11px] font-bold text-white">
                        {{ avatarLetter }}
                    </div>
                    <div class="min-w-0 text-left">
                        <p class="max-w-[120px] truncate text-xs font-semibold text-slate-800 dark:text-slate-100">
                            {{ user?.nama || '-' }}
                        </p>
                        <p class="max-w-[120px] truncate text-[10px] text-slate-400 dark:text-slate-500">
                            {{ auth.activeTenant?.tenant_name || user?.jabatan || user?.role || '-' }}
                        </p>
                    </div>
                    <svg
                        class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform duration-200"
                        :class="showUserMenu ? 'rotate-180' : ''"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    >
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <Transition
                    enter-active-class="transition duration-100 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-75 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="showUserMenu"
                        class="absolute right-0 top-full z-50 mt-1.5 w-48 origin-top-right rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div class="border-b border-slate-100 px-4 py-2.5 dark:border-slate-800">
                            <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ user?.nama || '-' }}</p>
                            <p v-if="user?.departemen || user?.jabatan" class="mt-0.5 text-[10px] leading-relaxed text-slate-400">
                                <span v-if="user?.departemen">{{ user.departemen }}</span>
                                <span v-if="user?.departemen && user?.jabatan"> · </span>
                                <span v-if="user?.jabatan">{{ user.jabatan }}</span>
                            </p>
                            <p v-else class="mt-0.5 text-[10px] text-slate-400">{{ user?.role || '-' }}</p>
                        </div>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-950/40"
                            @click="logout"
                        >
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                            </svg>
                            Keluar
                        </button>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>
