import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useTaskV2Store = defineStore('taskV2', () => {
  const tasks   = ref([])
  const myTasks = ref([])
  const meta    = ref({})
  const loading = ref(false)
  const error   = ref(null)

  async function fetchAll(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/v2/tasks/v2', { params })
      tasks.value = data.data
      meta.value = { total: data.total, last_page: data.last_page }
    } catch (e) {
      error.value = e.userMessage || 'Failed to load tasks.'
    } finally {
      loading.value = false
    }
  }

  async function fetchMine(params = {}) {
    const { data } = await api.get('/v2/tasks/v2/my', { params })
    myTasks.value = data.data
    return data.data
  }

  async function create(payload) {
    const { data } = await api.post('/v2/tasks/v2', payload)
    tasks.value.unshift(data.data)
    return data
  }

  async function update(id, payload) {
    const { data } = await api.put(`/v2/tasks/v2/${id}`, payload)
    const idx = tasks.value.findIndex(t => t.id === id)
    if (idx !== -1) tasks.value[idx] = data.data
    return data
  }

  async function remove(id) {
    await api.delete(`/v2/tasks/v2/${id}`)
    tasks.value = tasks.value.filter(t => t.id !== id)
  }

  return { tasks, myTasks, meta, loading, error, fetchAll, fetchMine, create, update, remove }
})
