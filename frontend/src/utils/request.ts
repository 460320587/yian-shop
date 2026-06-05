/**
 * Axios 统一封装
 * - baseURL: /api/v1
 * - 请求拦截：添加 Token Authorization 头
 * - 响应拦截：统一错误处理、Token 过期跳转登录
 */

import axios, {
  type AxiosInstance,
  type AxiosRequestConfig,
  type AxiosResponse,
  type AxiosError,
} from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'
import { useUserStore } from '@/stores/user'

/** HTTP 状态码对应的错误提示 */
const HTTP_ERROR_MESSAGES: Record<number, string> = {
  400: '请求参数错误',
  401: '登录已过期，请重新登录',
  403: '没有权限执行此操作',
  404: '请求的资源不存在',
  500: '服务器内部错误',
  502: '网关错误',
  503: '服务暂时不可用',
}

/** 创建 axios 实例 */
const request: AxiosInstance = axios.create({
  baseURL: '/api/v1',
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

/** 请求拦截器 */
request.interceptors.request.use(
  (config: AxiosRequestConfig) => {
    // 尝试从 localStorage 读取 token（优先），其次从 pinia 读取
    const url = config.url || ''
    const isAdminApi = url.startsWith('/admin')
    const token = isAdminApi
      ? localStorage.getItem('admin_token')
      : localStorage.getItem('access_token')
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error: AxiosError) => {
    return Promise.reject(error)
  }
)

/** 响应拦截器 */
request.interceptors.response.use(
  (response: AxiosResponse) => {
    // 若后端返回结构为 { code, message, data }
    const res = response.data
    if (res.code !== undefined && res.code !== 200 && res.code !== 0) {
      ElMessage.error(res.message || '请求失败')
      return Promise.reject(new Error(res.message || '请求失败'))
    }
    return res.data
  },
  (error: AxiosError) => {
    const status = error.response?.status
    const resData = error.response?.data as any
    let message = HTTP_ERROR_MESSAGES[status || 0] || error.message || '网络请求异常'

    // 422 验证错误：展示后端返回的具体字段错误
    if (status === 422 && resData?.data) {
      const errors = Object.values(resData.data).flat()
      if (errors.length > 0) {
        message = errors.join('；')
      } else if (resData.message) {
        message = resData.message
      }
    } else if (resData?.message) {
      message = resData.message
    }

    ElMessage.error(message)

    // 401 统一处理：清除 token 并跳转登录页
    if (status === 401) {
      const userStore = useUserStore()
      userStore.logout()
      router.push('/login')
    }

    return Promise.reject(error)
  }
)

/** 封装常用 HTTP 方法 */
export function get<T = unknown>(url: string, params?: Record<string, unknown>): Promise<T> {
  return request.get(url, { params }) as Promise<T>
}

export function post<T = unknown>(url: string, data?: unknown): Promise<T> {
  return request.post(url, data) as Promise<T>
}

export function put<T = unknown>(url: string, data?: unknown): Promise<T> {
  return request.put(url, data) as Promise<T>
}

export function del<T = unknown>(url: string, params?: Record<string, unknown>): Promise<T> {
  return request.delete(url, { params }) as Promise<T>
}

export default request
