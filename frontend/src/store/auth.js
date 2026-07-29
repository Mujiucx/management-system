import { defineStore } from 'pinia'
import { login as apiLogin, loginSms as apiLoginSms, logout as apiLogout } from '../api/auth'

function read(key, fallback = '') {
  const v = localStorage.getItem(key)
  return v === null ? fallback : v
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: read('token'),
    role: read('role'),
    institution_id: read('institution_id') ? Number(read('institution_id')) : null,
    phone: read('phone'),
  }),

  getters: {
    isLoggedIn: (s) => !!s.token,
    isPlatform: (s) => s.role === 'platform',
    isInstitution: (s) => s.role === 'institution',
  },

  actions: {
    setAuth(payload) {
      this.token = payload.token
      this.role = payload.account.role
      this.institution_id = payload.account.institution_id || null
      this.phone = payload.account.phone
      localStorage.setItem('token', this.token)
      localStorage.setItem('role', this.role)
      localStorage.setItem('institution_id', this.institution_id || '')
      localStorage.setItem('phone', this.phone)
    },

    async login(phone, password) {
      const res = await apiLogin(phone, password)
      this.setAuth(res)
      return res
    },

    async loginSms(phone, code) {
      const res = await apiLoginSms(phone, code)
      this.setAuth(res)
      return res
    },

    async logout() {
      try {
        await apiLogout()
      } catch (e) {
        // 忽略登出接口异常
      }
      this.token = ''
      this.role = ''
      this.institution_id = null
      this.phone = ''
      localStorage.removeItem('token')
      localStorage.removeItem('role')
      localStorage.removeItem('institution_id')
      localStorage.removeItem('phone')
    },
  },
})
