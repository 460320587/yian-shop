import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { adminLogin, adminLogout } from '@/api/admin'
import type { AdminLoginData } from '@/api/admin'

export interface AdminInfo {
  id: number
  username: string
  real_name: string | null
  role: string
}

const ADMIN_TOKEN_KEY = 'admin_token'
const ADMIN_INFO_KEY = 'admin_info'

export const useAdminStore = defineStore('admin', () => {
  const token = ref<string | null>(localStorage.getItem(ADMIN_TOKEN_KEY))
  const adminInfo = ref<AdminInfo | null>(null)

  const cached = localStorage.getItem(ADMIN_INFO_KEY)
  if (cached) {
    try { adminInfo.value = JSON.parse(cached) } catch { adminInfo.value = null }
  }

  const isLoggedIn = computed(() => !!token.value)

  function setToken(newToken: string) {
    token.value = newToken
    localStorage.setItem(ADMIN_TOKEN_KEY, newToken)
  }

  function setAdminInfo(admin: AdminInfo) {
    adminInfo.value = admin
    localStorage.setItem(ADMIN_INFO_KEY, JSON.stringify(admin))
  }

  async function login(data: AdminLoginData) {
    const res = await adminLogin(data)
    setToken(res.token)
    setAdminInfo(res.admin)
    return res
  }

  function logout() {
    token.value = null
    adminInfo.value = null
    localStorage.removeItem(ADMIN_TOKEN_KEY)
    localStorage.removeItem(ADMIN_INFO_KEY)
  }

  async function logoutApi() {
    try { await adminLogout() } catch { /* ignore */ }
    logout()
  }

  return { token, adminInfo, isLoggedIn, login, logout, logoutApi }
})
