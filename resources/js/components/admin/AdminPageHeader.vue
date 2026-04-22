<template>
  <div class="page-header">
    <div class="header-left">
      <button v-if="showBack" class="back-btn" @click="goBack">
        ← Back
      </button>
      <div>
        <h1 class="page-title">{{ title }}</h1>
        <p v-if="description" class="page-sub">{{ description }}</p>
      </div>
    </div>
    <div v-if="$slots.actions" class="header-actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const props = defineProps({
  title:       { type: String, required: true },
  description: { type: String, default: '' },
  showBack:    { type: Boolean, default: false },
  backTo:      { type: String, default: '/admin/tenants' },
})

const router = useRouter()

function goBack() {
  if (window.history.length > 2) {
    router.back()
  } else {
    router.push(props.backTo)
  }
}
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
}
.header-left { display: flex; align-items: flex-start; gap: 12px; }
.back-btn {
  margin-top: 3px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 6px 12px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
}
.back-btn:hover { background: #e2e8f0; color: #1e293b; }
.page-title { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1.2; }
.page-sub   { font-size: 13px; color: #94a3b8; margin-top: 3px; }
.header-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; justify-content: flex-end; }
</style>
