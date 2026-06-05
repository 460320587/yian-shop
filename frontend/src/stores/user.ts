import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { getUserInfo as apiGetUserInfo } from '@/api/user'

export interface User {
  id: number
  phone: string
  nickname: string | null
  avatar: string | null
  type: number
  auth_status: number
  vip_level: number
  balance: number
  points?: number
  link_person?: string | null
  qq?: string | null
}

const TOKEN_KEY = 'access_token'
const USER_KEY = 'user_info'

export const useUserStore = defineStore('user', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const userInfo = ref<User | null>(null)

  const cachedUser = localStorage.getItem(USER_KEY)
  if (cachedUser) {
    try { userInfo.value = JSON.parse(cachedUser) } catch { userInfo.value = null }
  }

  const isLoggedIn = computed(() => !!token.value)

  function setToken(newToken: string) {
    token.value = newToken
    localStorage.setItem(TOKEN_KEY, newToken)
  }

  function setUserInfo(user: User) {
    userInfo.value = user
    localStorage.setItem(USER_KEY, JSON.stringify(user))
  }

  /**
   * 从服务器获取用户信息
   * - 未登录时不发起请求
   * - 失败时不抛异常，保持 userInfo 原值
   */
  async function fetchUserInfo(): Promise<void> {
    if (!token.value) {
      return
    }

    try {
      const res = await apiGetUserInfo()
      if (res.data) {
        setUserInfo(res.data)
      }
    } catch {
      // 静默失败：网络异常或 token 过期时不中断用户体验
      // 由 Axios 拦截器统一处理 401 跳转
    }
  }

  function loginSuccess(newToken: string, user: User) {
    setToken(newToken)
    setUserInfo(user)
  }

  function logout() {
    token.value = null
    userInfo.value = null
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  }

  return { token, userInfo, isLoggedIn, setToken, setUserInfo, fetchUserInfo, loginSuccess, logout }
})
