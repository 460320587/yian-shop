/**
 * 常用格式化工具函数
 */

/**
 * 格式化金额（分 -> 元）
 * @param value 金额（分）
 * @param prefix 前缀，默认 ¥
 * @returns 格式化后的字符串
 */
export function formatPrice(value: number, prefix = '¥'): string {
  const price = (value / 100).toFixed(2)
  return `${prefix}${price}`
}

/**
 * 格式化日期
 * @param value 日期字符串或时间戳
 * @param format 格式模板，默认 YYYY-MM-DD HH:mm:ss
 * @returns 格式化后的字符串
 */
export function formatDate(
  value: string | number | Date,
  format = 'YYYY-MM-DD HH:mm:ss'
): string {
  const date = new Date(value)
  if (isNaN(date.getTime())) return '-'

  const pad = (n: number) => String(n).padStart(2, '0')
  const map: Record<string, string> = {
    YYYY: String(date.getFullYear()),
    MM: pad(date.getMonth() + 1),
    DD: pad(date.getDate()),
    HH: pad(date.getHours()),
    mm: pad(date.getMinutes()),
    ss: pad(date.getSeconds()),
  }

  return format.replace(/YYYY|MM|DD|HH|mm|ss/g, (match) => map[match])
}

/**
 * 文件大小格式化
 * @param bytes 字节数
 * @returns 如 "1.5 MB"
 */
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
