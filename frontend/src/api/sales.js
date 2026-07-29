import request from './request'

export function listSales(params) {
  return request.get('/sales', { params })
}

export function getSales(id) {
  return request.get(`/sales/${id}`)
}

export function setSalesStatus(id, status) {
  return request.patch(`/sales/${id}/status`, { status })
}
