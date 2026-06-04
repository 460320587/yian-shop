import { vi } from 'vitest'

/** 模拟 API 响应 */
export function mockApiResponse<T>(data: T, code = 0, message = '成功') {
  return Promise.resolve({ code, message, data })
}

/** 模拟 API 错误 */
export function mockApiError(code: number, message: string) {
  return Promise.reject(new Error(message))
}

/** 模拟 localStorage */
export function mockLocalStorage(store: Record<string, string> = {}) {
  const storage: Record<string, string> = { ...store }
  return {
    getItem: vi.fn((key: string) => storage[key] || null),
    setItem: vi.fn((key: string, value: string) => { storage[key] = value }),
    removeItem: vi.fn((key: string) => { delete storage[key] }),
    clear: vi.fn(() => { Object.keys(storage).forEach((k) => delete storage[k]) }),
    _store: storage,
  }
}
