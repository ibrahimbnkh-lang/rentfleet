import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export interface User {
  id: number
  name: string
  email: string
  branch_id: number | null
  roles: string[]
  permissions: string[]
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isLoggedIn = computed(() => !!token.value && !!user.value)
  const hasPermission = (perm: string) => user.value?.permissions?.includes(perm) ?? false
  const hasRole = (role: string) => user.value?.roles?.includes(role) ?? false

  async function login(email: string, password: string) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.post('/auth/login', { email, password })
      token.value = data.data.token
      user.value = data.data.user
      localStorage.setItem('auth_token', data.data.token)
    } catch (e: any) {
      error.value = e.response?.data?.error?.message ?? 'Login failed'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function fetchMe() {
    if (!token.value) return
    try {
      const { data } = await api.get('/auth/me')
      user.value = data.data
    } catch {
      token.value = null
      localStorage.removeItem('auth_token')
    }
  }

  return { user, token, loading, error, isLoggedIn, hasPermission, hasRole, login, logout, fetchMe }
})
