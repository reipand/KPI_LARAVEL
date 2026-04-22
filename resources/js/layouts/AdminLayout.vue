<template>
  <div class="admin-shell">
    <AdminSidebar
      :collapsed="sidebarCollapsed"
      @toggle="sidebarCollapsed = !sidebarCollapsed"
      @logout="showLogout = true"
    />

    <div class="admin-body">
      <AdminTopbar />
      <main class="admin-main">
        <RouterView />
      </main>
    </div>

    <ConfirmLogoutDialog v-model="showLogout" @confirm="auth.logout" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AdminSidebar        from '@/components/admin/AdminSidebar.vue'
import AdminTopbar         from '@/components/admin/AdminTopbar.vue'
import ConfirmLogoutDialog from '@/components/admin/ConfirmLogoutDialog.vue'
import { useAuthStore }    from '@/stores/auth'

const auth             = useAuthStore()
const sidebarCollapsed = ref(false)
const showLogout       = ref(false)
</script>

<style scoped>
.admin-shell {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
}
.admin-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.admin-main {
  flex: 1;
  padding: 28px;
  overflow-y: auto;
}
</style>
