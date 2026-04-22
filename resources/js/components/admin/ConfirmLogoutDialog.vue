<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="overlay" @click.self="$emit('update:modelValue', false)">
        <div class="dialog">
          <div class="dialog-icon">🚪</div>
          <h2 class="dialog-title">Konfirmasi Logout</h2>
          <p class="dialog-body">Apakah Anda yakin ingin keluar dari sesi Super Admin?</p>
          <div class="dialog-footer">
            <button class="btn-cancel" @click="$emit('update:modelValue', false)">Batal</button>
            <button class="btn-logout" @click="confirm">Ya, Logout</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({ modelValue: { type: Boolean, required: true } })
const emit = defineEmits(['update:modelValue', 'confirm'])

function confirm() {
  emit('update:modelValue', false)
  emit('confirm')
}
</script>

<style scoped>
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.6); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.dialog { background: #fff; border-radius: 16px; padding: 32px 28px; max-width: 380px; width: 100%; box-shadow: 0 24px 64px rgba(0,0,0,.18); text-align: center; }
.dialog-icon { font-size: 40px; margin-bottom: 12px; }
.dialog-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
.dialog-body { font-size: 14px; color: #64748b; margin-bottom: 24px; line-height: 1.5; }
.dialog-footer { display: flex; gap: 10px; justify-content: center; }
.btn-cancel { padding: 9px 24px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 14px; cursor: pointer; color: #475569; font-weight: 500; }
.btn-cancel:hover { background: #f1f5f9; }
.btn-logout { padding: 9px 24px; border: none; border-radius: 8px; background: #dc2626; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-logout:hover { background: #b91c1c; }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
