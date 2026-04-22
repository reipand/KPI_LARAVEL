import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useAuditLogStore = defineStore('auditLog', () => {
  const logs    = ref([])
  const meta    = ref({})
  const loading = ref(false)

  async function fetchAll(params = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/v2/audit-logs', { params })
      logs.value = data.data
      meta.value = { total: data.total, last_page: data.last_page, current_page: data.current_page }
    } finally {
      loading.value = false
    }
  }

  return { logs, meta, loading, fetchAll }
})
