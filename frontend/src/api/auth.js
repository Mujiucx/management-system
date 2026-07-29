import request from './request'

// 密码登录
export function login(phone, password) {
  return request.post('/auth/login', { phone, password })
}

// 验证码登录
export function loginSms(phone, code) {
  return request.post('/auth/login-sms', { phone, code })
}

// 发送短信验证码（开发态 data.code 返回明文）
export function sendCode(phone) {
  return request.post('/auth/send-code', { phone })
}

// 登出
export function logout() {
  return request.post('/auth/logout')
}
