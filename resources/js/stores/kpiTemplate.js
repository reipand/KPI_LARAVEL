import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useKpiTemplateStore = defineStore('kpiTemplate', () => {
  const templates = ref([])
  const current   = ref(null)
  const meta      = ref({})
  const loading   = ref(false)
  const error     = ref(null)

  async function fetchAll(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/v2/kpi/templates', { params })
      templates.value = data.data
      meta.value = { total: data.total, last_page: data.last_page }
    } catch (e) {
      error.value = e.userMessage || 'Failed to load KPI templates.'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    loading.value = true
    try {
      const { data } = await api.get(`/v2/kpi/templates/${id}`)
      current.value = data.data
      return data.data
    } finally {
      loading.value = false
    }
  }

  async function create(payload) {
    const { data } = await api.post('/v2/kpi/templates', payload)
    templates.value.unshift(data.data)
    return data
  }

  async function update(id, payload) {
    const { data } = await api.put(`/v2/kpi/templates/${id}`, payload)
    const idx = templates.value.findIndex(t => t.id === id)
    if (idx !== -1) templates.value[idx] = data.data
    return data
  }

  async function remove(id) {
    await api.delete(`/v2/kpi/templates/${id}`)
    templates.value = templates.value.filter(t => t.id !== id)
  }

  async function addIndicator(templateId, payload) {
    const { data } = await api.post(`/v2/kpi/templates/${templateId}/indicators`, payload)
    if (current.value?.id === templateId) {
      current.value.indicators.push(data.data)
    }
    return data
  }

  async function updateIndicator(indicatorId, payload) {
    const { data } = await api.put(`/v2/kpi/indicators/${indicatorId}`, payload)
    return data
  }

  async function removeIndicator(indicatorId) {
    await api.delete(`/v2/kpi/indicators/${indicatorId}`)
    if (current.value) {
      current.value.indicators = current.value.indicators.filter(i => i.id !== indicatorId)
    }
  }

  return {
    templates, current, meta, loading, error,
    fetchAll, fetchOne, create, update, remove,
    addIndicator, updateIndicator, removeIndicator,
  }
})
