import request from '@/utils/request'

export interface CaptchaData {
  captcha_key: string
  captcha_code: string
}

export function getCaptcha() {
  return request.get<CaptchaData>('/captcha')
}
