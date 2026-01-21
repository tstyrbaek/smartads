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
  updatedAt?: string
}

export type MeResponse = {
  user: { id: number; name: string; email: string }
  companies: { id: number; name: string }[]
}

export type User = {
  id: number
  name: string
  email: string
}

export type Ad = {
  id: string
  text: string
  status: 'creating' | 'generating' | 'success' | 'failed'
  nanobananaTaskId?: string | null
  resultImageUrl?: string | null
  localFilePath?: string | null
  error?: string | null
  updatedAt?: string | null
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
  if (token) headers.set('authorization', `Bearer ${token}`)
  if (companyId) headers.set('X-Company-Id', companyId)

  const res = await fetch(`${API_BASE}${path}`, { ...init, headers })
  if (!res.ok) {
    const text = await res.text()
    throw new Error(text || `HTTP ${res.status}`)
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

export async function saveBrand(form: FormData): Promise<Brand> {
  const res = await apiFetch('/api/brand', { method: 'POST', body: form })
  return (await res.json()) as Brand
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
  opts?: { debug?: boolean; images?: File[] },
): Promise<{ adId: string; status: string; debug?: AdCreateDebug | null }> {
  const images = (opts?.images ?? []).slice(0, 3)

  const res =
    images.length > 0
      ? await apiFetch('/api/ads', {
          method: 'POST',
          body: (() => {
            const form = new FormData()
            form.set('text', text)
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
          body: JSON.stringify({ text, debug: opts?.debug === true }),
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
