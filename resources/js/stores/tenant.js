import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useTenantStore = defineStore('tenant', () => {
  const tenants   = ref([])
  const current   = ref(null)
  const meta      = ref({})
  const loading   = ref(false)
  const error     = ref(null)

  const activeTenants = computed(() => tenants.value.filter(t => t.status === 'active'))

  async function fetchAll(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/v2/tenants', { params })
      tenants.value = data.data
      meta.value = { total: data.total, last_page: data.last_page, current_page: data.current_page }
    } catch (e) {
      error.value = e.userMessage || 'Failed to load tenants.'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    loading.value = true
    try {
      const { data } = await api.get(`/v2/tenants/${id}`)
      current.value = data.data
      return data.data
    } finally {
      loading.value = false
    }
  }

  async function create(payload) {
    const { data } = await api.post('/v2/tenants', payload)
    tenants.value.unshift(data.data)
    return data
  }

  async function update(id, payload) {
    const { data } = await api.put(`/v2/tenants/${id}`, payload)
    const idx = tenants.value.findIndex(t => t.id === id)
    if (idx !== -1) tenants.value[idx] = data.data
    return data
  }

  async function setStatus(id, action) {
    const { data } = await api.patch(`/v2/tenants/${id}/${action}`)
    const idx = tenants.value.findIndex(t => t.id === id)
    if (idx !== -1) tenants.value[idx] = data.data
    return data
  }

  return { tenants, current, meta, loading, error, activeTenants, fetchAll, fetchOne, create, update, setStatus }
})
