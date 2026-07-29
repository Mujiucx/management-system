import request from './request'

// 概览统计（机构数 / 团队长数 / 业务员数 / 本月业绩 + 组织层级树）
export function getOverview() {
  return request.get('/overview')
}
