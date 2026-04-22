import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useKpiAssignmentStore = defineStore('kpiAssignment', () => {
  const assignments   = ref([])
  const myAssignments = ref([])
  const current       = ref(null)
  const meta          = ref({})
  const loading       = ref(false)
  const error         = ref(null)

  async function fetchAll(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/v2/kpi/assignments', { params })
      assignments.value = data.data
      meta.value = { total: data.total, last_page: data.last_page }
    } catch (e) {
      error.value = e.userMessage || 'Failed to load assignments.'
    } finally {
      loading.value = false
    }
  }

  async function fetchMine(params = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/v2/kpi/assignments/my', { params })
      myAssignments.value = data.data
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    loading.value = true
    try {
      const { data } = await api.get(`/v2/kpi/assignments/${id}`)
      current.value = data.data
      return data.data
    } finally {
      loading.value = false
    }
  }

  async function create(payload) {
    const { data } = await api.post('/v2/kpi/assignments', payload)
    assignments.value.unshift(data.data)
    return data
  }

  async function submit(id, results) {
    const { data } = await api.post(`/v2/kpi/assignments/${id}/submit`, { results })
    _updateLocal(id, data.data)
    return data
  }

  async function approve(id, notes = '') {
    const { data } = await api.post(`/v2/kpi/assignments/${id}/approve`, { notes })
    _updateLocal(id, data.data)
    return data
  }

  async function reject(id, reason) {
    const { data } = await api.post(`/v2/kpi/assignments/${id}/reject`, { rejection_reason: reason })
    _updateLocal(id, data.data)
    return data
  }

  function _updateLocal(id, updated) {
    const idx = assignments.value.findIndex(a => a.id === id)
    if (idx !== -1) assignments.value[idx] = updated
    if (current.value?.id === id) current.value = updated
  }

  return {
    assignments, myAssignments, current, meta, loading, error,
    fetchAll, fetchMine, fetchOne, create, submit, approve, reject,
  }
})
