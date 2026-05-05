<script setup>
import { computed, onMounted, onUnmounted, provide, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';
import AdminSidebar from '@/components/admin/AdminSidebar.vue';
import AdminTopbar from '@/components/admin/AdminTopbar.vue';
import ConfirmLogoutDialog from '@/components/admin/ConfirmLogoutDialog.vue';

const route = useRoute();
const auth = useAuthStore();
const mobileMenuOpen = ref(false);
const showLogout = ref(false);
// Collapse sidebar by default on tablet (< 1024px), expand on desktop
const sidebarCollapsed = ref(typeof window !== 'undefined' && window.innerWidth < 1024);
const useAdminShell = computed(() => auth.activeRole === 'super_admin' || auth.user?.role === 'super_admin');

// Provide so nested components can read sidebar state if needed
provide('sidebarCollapsed', sidebarCollapsed);

function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
}

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}

function handleKeydown(event) {
    if (event.key === 'Escape') closeMobileMenu();
}

watch(
    () => route.fullPath,
    closeMobileMenu
);

watch(mobileMenuOpen, (open) => {
    document.body.classList.toggle('overflow-hidden', open);
});

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <div v-if="useAdminShell" class="admin-shell">
        <aside
            :class="[
                'hidden md:flex shrink-0 transition-[width] duration-300 ease-in-out',
                sidebarCollapsed ? 'w-[76px]' : 'w-[272px]',
            ]"
        >
            <AdminSidebar
                :collapsed="sidebarCollapsed"
                @toggle="toggleSidebar"
                @logout="showLogout = true"
            />
        </aside>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 md:hidden">
                <div class="sidebar-overlay" @click="closeMobileMenu" />
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-x-0"
                    leave-to-class="-translate-x-full"
                >
                    <div class="absolute inset-y-0 left-0 z-50 flex w-[272px] flex-col">
                        <AdminSidebar
                            :collapsed="false"
                            @toggle="toggleSidebar"
                            @logout="showLogout = true"
                        />
                    </div>
                </Transition>
            </div>
        </Transition>

        <div class="admin-body">
            <AdminTopbar @open-sidebar="mobileMenuOpen = true">
                <template #actions>
                    <slot name="topbar-actions" />
                </template>
            </AdminTopbar>

            <main class="admin-main">
                <slot />
            </main>
        </div>

        <ConfirmLogoutDialog v-model="showLogout" @confirm="auth.logout" />
    </div>

    <div v-else class="flex min-h-screen overflow-hidden bg-muted/40 dark:bg-[#0a0f1e]">

        <!-- ── Desktop sidebar (lg+: full, md: icon-rail) ─────────────── -->
        <aside
            :class="[
                'app-sidebar-panel hidden md:flex flex-col transition-[width] duration-300 ease-in-out',
                sidebarCollapsed ? 'w-[64px]' : 'w-[240px]',
            ]"
        >
            <AppSidebar :collapsed="sidebarCollapsed" @toggle="toggleSidebar" />
        </aside>

        <!-- ── Mobile sidebar drawer (< md only) ────────────────────────── -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 md:hidden">
                <div class="sidebar-overlay" @click="closeMobileMenu" />
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-x-0"
                    leave-to-class="-translate-x-full"
                >
                    <div class="absolute inset-y-0 left-0 z-50 flex w-[240px] flex-col">
                        <AppSidebar :collapsed="false" mobile @close="closeMobileMenu" />
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- ── Main content ─────────────────────────────────────────────── -->
        <div
            :class="[
                'flex flex-1 flex-col overflow-hidden transition-[margin] duration-300 ease-in-out',
                sidebarCollapsed ? 'md:ml-[64px]' : 'md:ml-[240px]',
            ]"
        >
            <AppTopbar @open-sidebar="mobileMenuOpen = true" @toggle-sidebar="toggleSidebar">
                <template #actions>
                    <slot name="topbar-actions" />
                </template>
            </AppTopbar>

            <main class="flex-1 overflow-y-auto">
                <div class="page-shell">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.admin-shell {
    display: flex;
    min-height: 100vh;
    background: #f8fafc;
}

.admin-body {
    flex: 1;
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.admin-main {
    flex: 1;
    overflow-y: auto;
    padding: 28px;
}
</style>
