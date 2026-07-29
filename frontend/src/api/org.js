import request from './request'

export function listOrgs(params) {
  return request.get('/orgs', { params })
}

export function getOrg(id) {
  return request.get(`/orgs/${id}`)
}

export function createOrg(data) {
  return request.post('/orgs', data)
}

export function updateOrg(id, data) {
  return request.put(`/orgs/${id}`, data)
}

export function deleteOrg(id) {
  return request.delete(`/orgs/${id}`)
}

export function setOrgStatus(id, status) {
  return request.patch(`/orgs/${id}/status`, { status })
}

// 营业执照上传（multipart）
export function uploadFile(file, module = 'org') {
  const form = new FormData()
  form.append('file', file)
  form.append('module', module)
  return request.post('/upload', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}
