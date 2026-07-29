import request from './request'

export function getSettings() {
  return request.get('/settings')
}

export function updateDomain(shareDomain) {
  return request.put('/settings/domain', { share_domain: shareDomain })
}
