<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="overlay" @click.self="$emit('update:modelValue', false)">
        <div class="dialog" :class="`dialog-${variant}`">
          <div class="dialog-icon">
            <svg v-if="variant === 'danger'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
          </div>
          <h3 class="dialog-title">{{ title }}</h3>
          <p class="dialog-body">{{ message }}</p>
          <div class="dialog-actions">
            <button class="btn-cancel" @click="$emit('update:modelValue', false)">Cancel</button>
            <button class="btn-confirm" :class="`btn-${variant}`" :disabled="loading" @click="$emit('confirm')">
              <span v-if="loading" class="spinner" />
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  modelValue:  { type: Boolean, default: false },
  title:       { type: String, default: 'Are you sure?' },
  message:     { type: String, default: 'This action cannot be undone.' },
  confirmText: { type: String, default: 'Confirm' },
  variant:     { type: String, default: 'danger' },
  loading:     { type: Boolean, default: false },
})
defineEmits(['update:modelValue', 'confirm'])
</script>

<style scoped>
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 9999; display: flex; align-items: center; justify-content: center; }
.dialog { background: #fff; border-radius: 14px; padding: 28px 32px; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
.dialog-icon { width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
.dialog-icon svg { width: 26px; height: 26px; }
.dialog-danger .dialog-icon { background: #fee2e2; color: #dc2626; }
.dialog-warning .dialog-icon { background: #fef9c3; color: #d97706; }
.dialog-title { font-size: 17px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
.dialog-body  { font-size: 14px; color: #64748b; margin-bottom: 24px; line-height: 1.5; }
.dialog-actions { display: flex; gap: 10px; justify-content: center; }
.btn-cancel { padding: 9px 22px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 14px; cursor: pointer; color: #475569; font-weight: 500; }
.btn-cancel:hover { background: #f8fafc; }
.btn-confirm { padding: 9px 22px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 8px; }
.btn-danger  { background: #dc2626; }
.btn-danger:hover  { background: #b91c1c; }
.btn-warning { background: #d97706; }
.spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
