export type Brand = {
  companyName?: string
  websiteUrl?: string
  companyDescription?: string
  audienceDescription?: string
  primaryColor1?: string
  primaryColor2?: string
  primaryColor3?: string
  primaryColor4?: string
  logoPath?: string
  fonts?: string
  slogan?: string
  visual_guidelines?: string
  updatedAt?: string
}

export type SubscriptionStatus = {
  status: 'none' | 'active' | 'expired'
  plan: {
    id: number
    name: string
    description?: string | null
    max_tokens_per_month: number
    formatted_tokens?: string
    price_per_month: number
    formatted_price?: string
    features?: string[]
  } | null
  remaining_days: number | null
  tokens_remaining: number
  tokens_limit: number
  usage_percentage?: number
}

export type SubscriptionResponse = {
  subscription: SubscriptionStatus
  usage_history?: { period: string; tokens: number; cost: number }[]
}

export type TokensSummaryResponse = {
  status: 'none' | 'active'
  period: string
  limit: number
  used: number
  remaining: number
  usage_percentage: number
}

export type NotificationItem = {
  id: number
  level: 'info' | 'warning' | 'error' | 'success'
  title: string
  message: string
  data?: any
  starts_at?: string | null
  ends_at?: string | null
}

export type NotificationsResponse = {
  notifications: NotificationItem[]
}

export type MeResponse = {
  user: { id: number; name: string; email: string }
  companies: { id: number; name: string; logo_path: string | null }[]
}

export type User = {
  id: number
  name: string
  email: string
}

export type Ad = {
  id: string
  text: string
  instructions?: string | null
  imageWidth?: number
  imageHeight?: number
  status: 'creating' | 'generating' | 'success' | 'failed'
  nanobananaTaskId?: string | null
  resultImageUrl?: string | null
  localFilePath?: string | null
  integrationInstances?: {
    id: number
    integration_key: string
    name: string
    is_active: boolean
    published_at?: string | null
  }[]
  error?: string | null
  updatedAt?: string | null
}

export type IntegrationDefinition = {
  key: string
  type: string
  name: string
  description?: string | null
  capabilities?: any[] | null
  is_active: boolean
}

export type IntegrationInstance = {
  id: number
  company_id: number
  integration_key: string
  name: string
  is_active: boolean
  config?: Record<string, any> | null
  created_at?: string | null
  updated_at?: string | null
}

import { ref } from 'vue'

const ENV_API_BASE = (import.meta.env.VITE_API_BASE as string | undefined) ?? ''

export const API_BASE =
  typeof window !== 'undefined' && window.location.hostname === 'localhost' ? '' : ENV_API_BASE

export const authToken = ref<string | null>(
  typeof window !== 'undefined' ? localStorage.getItem('apiToken') : null,
)

export const activeCompanyId = ref<string | null>(
  typeof window !== 'undefined' ? localStorage.getItem('activeCompanyId') : null,
)

export function setAuthToken(token: string | null) {
  if (typeof window === 'undefined') return

  if (token) {
    localStorage.setItem('apiToken', token)
  } else {
    localStorage.removeItem('apiToken')
  }

  authToken.value = token
}

export function toAbsoluteBackendUrl(pathOrUrl: string): string {
  if (!pathOrUrl) return pathOrUrl
  if (pathOrUrl.startsWith('http://') || pathOrUrl.startsWith('https://')) return pathOrUrl
  if (!API_BASE) return pathOrUrl
  if (pathOrUrl.startsWith('/')) return `${API_BASE}${pathOrUrl}`
  return `${API_BASE}/${pathOrUrl}`
}

async function apiFetch(path: string, init?: RequestInit) {
  const token = authToken.value
  const companyId = activeCompanyId.value

  const headers = new Headers(init?.headers ?? undefined)
  if (!headers.has('accept')) headers.set('accept', 'application/json')
  if (token) headers.set('authorization', `Bearer ${token}`)
  if (companyId) headers.set('X-Company-Id', companyId)

  const res = await fetch(`${API_BASE}${path}`, { ...init, headers })

  const contentType = res.headers.get('content-type') ?? ''
  const isApiPath = path.startsWith('/api/')
  const isHtml = contentType.includes('text/html')

  if (isApiPath && isHtml) {
    const text = await res.text()
    const snippet = text.slice(0, 200).replace(/\s+/g, ' ').trim()
    throw new Error(`Uventet HTML-svar fra API (HTTP ${res.status}). ${snippet}`)
  }

  if (!res.ok) {
    const text = await res.text()
    const snippet = text.slice(0, 200).replace(/\s+/g, ' ').trim()
    throw new Error(snippet || `HTTP ${res.status}`)
  }
  return res
}

export async function login(email: string, password: string, deviceName?: string) {
  const res = await apiFetch('/api/auth/login', {
    method: 'POST',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify({ email, password, device_name: deviceName ?? 'frontend' }),
  })

  const json = (await res.json()) as { token: string }
  setAuthToken(json.token)

  return json
}

export async function forgotPassword(email: string): Promise<void> {
  const res = await fetch(`${API_BASE}/api/auth/forgot-password`, {
    method: 'POST',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify({ email }),
  })

  if (!res.ok) {
    const text = await res.text()
    throw new Error(text || `HTTP ${res.status}`)
  }
}

export async function getMe(): Promise<MeResponse> {
  const res = await apiFetch('/api/me')
  return (await res.json()) as MeResponse
}

export async function logout(): Promise<void> {
  await apiFetch('/api/auth/logout', { method: 'POST' })
  setAuthToken(null)
  clearActiveCompanyId()
}

export async function updateProfile(input: { name: string; email: string }): Promise<User> {
  const res = await apiFetch('/api/profile', {
    method: 'PUT',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify(input),
  })

  const json = (await res.json()) as { user: User }
  return json.user
}

export async function updatePassword(input: {
  current_password: string
  password: string
  password_confirmation: string
}): Promise<void> {
  await apiFetch('/api/profile/password', {
    method: 'PUT',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify(input),
  })
}

export function setActiveCompanyId(companyId: number) {
  if (typeof window === 'undefined') return
  const v = String(companyId)
  localStorage.setItem('activeCompanyId', v)
  activeCompanyId.value = v
}

export function clearActiveCompanyId() {
  if (typeof window === 'undefined') return
  localStorage.removeItem('activeCompanyId')
  activeCompanyId.value = null
}

export async function getBrand(): Promise<Brand> {
  const res = await apiFetch('/api/brand')
  return (await res.json()) as Brand
}

export async function getSubscription(): Promise<SubscriptionResponse> {
  const res = await apiFetch('/api/subscription')
  return (await res.json()) as SubscriptionResponse
}

export async function getTokensSummary(): Promise<TokensSummaryResponse> {
  const res = await apiFetch('/api/tokens/summary')
  return (await res.json()) as TokensSummaryResponse
}

export async function getNotifications(limit = 10): Promise<NotificationsResponse> {
  const res = await apiFetch(`/api/notifications?limit=${encodeURIComponent(String(limit))}`)
  return (await res.json()) as NotificationsResponse
}

export const tokensSummary = ref<TokensSummaryResponse | null>(null)

export async function refreshTokensSummary(): Promise<void> {
  tokensSummary.value = await getTokensSummary()
}

export async function saveBrand(form: FormData): Promise<Brand> {
  const res = await apiFetch('/api/brand', { method: 'POST', body: form })
  return (await res.json()) as Brand
}

export async function listIntegrationDefinitions(): Promise<{ definitions: IntegrationDefinition[] }> {
  const res = await apiFetch('/api/integrations/definitions')
  return (await res.json()) as { definitions: IntegrationDefinition[] }
}

export async function listIntegrationInstances(): Promise<{ instances: IntegrationInstance[] }> {
  const res = await apiFetch('/api/integrations/instances')
  return (await res.json()) as { instances: IntegrationInstance[] }
}

export async function createIntegrationInstance(input: {
  integration_key: string
  name: string
  is_active: boolean
  config?: Record<string, any> | null
}): Promise<{ instance: IntegrationInstance }> {
  const res = await apiFetch('/api/integrations/instances', {
    method: 'POST',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify(input),
  })
  return (await res.json()) as { instance: IntegrationInstance }
}

export async function updateIntegrationInstance(
  id: number,
  input: {
    integration_key: string
    name: string
    is_active: boolean
    config?: Record<string, any> | null
  },
): Promise<{ instance: IntegrationInstance }> {
  const res = await apiFetch(`/api/integrations/instances/${encodeURIComponent(String(id))}`, {
    method: 'PATCH',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify(input),
  })
  return (await res.json()) as { instance: IntegrationInstance }
}

export async function deleteIntegrationInstance(id: number): Promise<void> {
  await apiFetch(`/api/integrations/instances/${encodeURIComponent(String(id))}`, { method: 'DELETE' })
}

export type AdCreateDebug = {
  mode?: string
  prompt?: string
  logoReferenceUrl?: string
  imageUrls?: string[]
  publicBaseUrl?: string
  geminiRequest?: Record<string, unknown>
}

export async function createAd(
  text: string,
  opts?: {
    debug?: boolean
    images?: File[]
    instructions?: string
    imageWidth?: number
    imageHeight?: number
  },
): Promise<{ adId: string; status: string; debug?: AdCreateDebug | null }> {
  const images = (opts?.images ?? []).slice(0, 5)
  const instructions = (opts?.instructions ?? '').trim()
  const imageWidth = typeof opts?.imageWidth === 'number' && Number.isFinite(opts.imageWidth) ? opts.imageWidth : null
  const imageHeight = typeof opts?.imageHeight === 'number' && Number.isFinite(opts.imageHeight) ? opts.imageHeight : null

  const res =
    images.length > 0
      ? await apiFetch('/api/ads', {
          method: 'POST',
          body: (() => {
            const form = new FormData()
            form.set('text', text)
            if (instructions !== '') form.set('instructions', instructions)
            if (imageWidth !== null) form.set('image_width', String(imageWidth))
            if (imageHeight !== null) form.set('image_height', String(imageHeight))
            if (opts?.debug === true) form.set('debug', '1')
            for (const img of images) {
              form.append('images[]', img)
            }
            return form
          })(),
        })
      : await apiFetch('/api/ads', {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: JSON.stringify({
            text,
            instructions: instructions !== '' ? instructions : null,
            image_width: imageWidth,
            image_height: imageHeight,
            debug: opts?.debug === true,
          }),
        })
  return (await res.json()) as { adId: string; status: string; debug?: AdCreateDebug | null }
}

export async function getAd(id: string): Promise<{ ad: Ad; downloadUrl: string | null; previewUrl: string | null }> {
  const res = await apiFetch(`/api/ads/${encodeURIComponent(id)}`)
  return (await res.json()) as { ad: Ad; downloadUrl: string | null; previewUrl: string | null }
}

export async function listAds(): Promise<{ ads: Ad[] }> {
  const res = await apiFetch('/api/ads')
  return (await res.json()) as { ads: Ad[] }
}

export async function deleteAd(id: string): Promise<void> {
  await apiFetch(`/api/ads/${encodeURIComponent(id)}`, { method: 'DELETE' })
}

export async function getAdIntegrations(id: string): Promise<{ selected_instance_ids: number[] }> {
  const res = await apiFetch(`/api/ads/${encodeURIComponent(id)}/integrations`)
  return (await res.json()) as { selected_instance_ids: number[] }
}

export async function updateAdIntegrations(id: string, instanceIds: number[]): Promise<{ ok: true; ad: Ad }> {
  const res = await apiFetch(`/api/ads/${encodeURIComponent(id)}/integrations`, {
    method: 'PUT',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify({ instance_ids: instanceIds }),
  })
  return (await res.json()) as { ok: true; ad: Ad }
}
