import { ref } from 'vue'

export type NoticeLevel = 'info' | 'warning' | 'error' | 'success'

export type Notice = {
  id: string
  source?: 'system' | 'backend'
  level: NoticeLevel
  title: string
  message?: string
  meta?: Record<string, any>
}

export const notices = ref<Notice[]>([])

export function setNotice(notice: Notice): void {
  const idx = notices.value.findIndex((x) => x.id === notice.id)
  if (idx === -1) {
    notices.value = [...notices.value, notice]
    return
  }

  const next = [...notices.value]
  next[idx] = { ...next[idx], ...notice }
  notices.value = next
}

export function removeNotice(id: string): void {
  notices.value = notices.value.filter((x) => x.id !== id)
}

export function replaceNoticesBySource(source: Notice['source'], nextItems: Notice[]): void {
  const keep = notices.value.filter((x) => x.source !== source)
  notices.value = [...keep, ...(Array.isArray(nextItems) ? nextItems : [])]
}
