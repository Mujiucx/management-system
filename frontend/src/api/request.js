import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '../router'

const service = axios.create({
  baseURL: '/api',
  timeout: 15000,
})

// 请求拦截：注入 Bearer token
service.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// 响应拦截：解包 {code,msg,data}
service.interceptors.response.use(
  (response) => {
    const res = response.data
    if (res.code !== 0) {
      ElMessage.error(res.msg || '请求失败')
      if (res.code === 401) {
        localStorage.removeItem('token')
        router.push({ name: 'login' })
      }
      return Promise.reject(new Error(res.msg || 'error'))
    }
    // 返回 data（列表接口为完整 {list,total,page,page_size}）
    return res.data
  },
  (error) => {
    ElMessage.error(error.message || '网络错误')
    return Promise.reject(error)
  },
)

export default service
