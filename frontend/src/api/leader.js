import request from './request'

export function listLeaders(params) {
  return request.get('/leaders', { params })
}

export function getLeader(id) {
  return request.get(`/leaders/${id}`)
}

export function createLeader(data) {
  return request.post('/leaders', data)
}

export function updateLeader(id, data) {
  return request.put(`/leaders/${id}`, data)
}

export function deleteLeader(id) {
  return request.delete(`/leaders/${id}`)
}

export function setLeaderStatus(id, status) {
  return request.patch(`/leaders/${id}/status`, { status })
}
