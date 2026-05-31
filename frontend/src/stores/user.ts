/**
 * 用户状态管理 (Pinia)
 * - token 持久化到 localStorage
 * - 用户信息、登录状态
 */

import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { User } from '@/types'

const TOKEN_KEY = 'access_token'
const USER_KEY = 'user_info'

export const useUserStore = defineStore('user', () => {
  // ========== State ==========
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const userInfo = ref<User | null>(null)

  // 初始化时尝试从 localStorage 恢复用户信息
  const cachedUser = localStorage.getItem(USER_KEY)
  if (cachedUser) {
    try {
      userInfo.value = JSON.parse(cachedUser)
    } catch {
      userInfo.value = null
    }
  }

  // ========== Getters ==========
  const isLoggedIn = computed(() => !!token.value)

  // ========== Actions ==========
  /** 设置 token */
  function setToken(newToken: string) {
    token.value = newToken
    localStorage.setItem(TOKEN_KEY, newToken)
  }

  /** 设置用户信息 */
  function setUserInfo(user: User) {
    userInfo.value = user
    localStorage.setItem(USER_KEY, JSON.stringify(user))
  }

  /** 登录成功后的统一处理 */
  function loginSuccess(newToken: string, user: User) {
    setToken(newToken)
    setUserInfo(user)
  }

  /** 退出登录 */
  function logout() {
    token.value = null
    userInfo.value = null
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  }

  return {
    token,
    userInfo,
    isLoggedIn,
    setToken,
    setUserInfo,
    loginSuccess,
    logout,
  }
})
