const ADMIN_API = process.env.NEXT_PUBLIC_API_URL || (process.env.NODE_ENV === 'production' ? 'https://atelier404.store/api/v1' : 'http://localhost:8000/api/v1');

export interface Collection {
  id: number;
  title: string;
  slug: string;
  description?: string | null;
  image_url?: string | null;
  is_active?: boolean;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: any;
}

function normalizeArray<T>(res: any): T[] {
  if (!res) return [];
  if (Array.isArray(res)) return res;
  if (res.data && Array.isArray(res.data)) return res.data;
  return [];
}

function normalizeObject<T>(res: any): T {
  if (!res) throw new Error("Empty response");
  if (res.data && typeof res.data === 'object' && !Array.isArray(res.data)) return res.data;
  return res;
}

function normalizePaginated<T>(res: any): PaginatedResponse<T> {
  if (!res) return { data: [], meta: null };
  const data = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : []);
  const meta = res.meta || null;
  return { data, meta };
}

function getToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem('admin_token');
}

function getAdminApiUrl(): string {
  if (typeof window !== 'undefined') {
    return '/api/v1';
  }
  return process.env.NEXT_PUBLIC_API_URL || (process.env.NODE_ENV === 'production' ? 'https://atelier404.store/api/v1' : 'http://127.0.0.1:8000/api/v1');
}

async function adminFetch(path: string, options: RequestInit = {}): Promise<any> {
  const token = getToken();
  const baseUrl = getAdminApiUrl();
  const res = await fetch(`${baseUrl}/admin${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
  });

  let data;
  const text = await res.text();
  try {
    data = JSON.parse(text);
  } catch (err: any) {
    console.error('API Parse Error. Response text was:', text);
    throw new Error('Invalid JSON response from server');
  }

  if (!res.ok) throw new Error(data.message || 'API Error');
  return data;
}

// ── Auth ─────────────────────────────────────────────────────────────────────
export async function adminLogin(email: string, password: string) {
  const res = await fetch(`${ADMIN_API}/admin/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, password }),
  });
  let data;
  const text = await res.text();
  try {
    data = JSON.parse(text);
  } catch (err: any) {
    console.error('Login Parse Error. Response text was:', text);
    throw new Error('Invalid JSON response from server');
  }

  if (!res.ok || !data.success) throw new Error(data.message || 'Login failed');
  return data.data;
}

export async function adminLogout() {
  await adminFetch('/auth/logout', { method: 'POST' });
  localStorage.removeItem('admin_token');
}

export async function adminMe() {
  return adminFetch('/auth/me');
}

// ── Dashboard ─────────────────────────────────────────────────────────────────
export async function getDashboard() {
  const res = await adminFetch('/dashboard');
  return normalizeObject<any>(res);
}

// ── Products ─────────────────────────────────────────────────────────────────
export async function getProducts(params?: Record<string, string>): Promise<PaginatedResponse<any>> {
  const q = params ? '?' + new URLSearchParams(params).toString() : '';
  const res = await adminFetch(`/products${q}`);
  return normalizePaginated<any>(res);
}

export async function getProduct(id: number | string) {
  const res = await adminFetch(`/products/${id}`);
  return normalizeObject<any>(res);
}

export async function createProduct(data: Record<string, any>) {
  const res = await adminFetch('/products', { method: 'POST', body: JSON.stringify(data) });
  return normalizeObject<any>(res);
}

export async function updateProduct(id: number | string, data: Record<string, any>) {
  const res = await adminFetch(`/products/${id}`, { method: 'PUT', body: JSON.stringify(data) });
  return normalizeObject<any>(res);
}

export async function deleteProduct(id: number | string) {
  return adminFetch(`/products/${id}`, { method: 'DELETE' });
}

export async function adjustInventory(id: number | string, quantity: number, reason?: string) {
  const res = await adminFetch(`/products/${id}/inventory`, {
    method: 'PATCH',
    body: JSON.stringify({ quantity, reason }),
  });
  return normalizeObject<any>(res);
}

// ── Media ─────────────────────────────────────────────────────────────────────
export interface MediaAsset {
  id: number;
  type: 'image' | 'video' | '3d';
  url: string;
  filename: string;
  mime_type: string;
  size_bytes: number;
  metadata?: Record<string, unknown> | null;
  created_at?: string;
}

export async function getMedia(): Promise<MediaAsset[]> {
  const res = await adminFetch('/media');
  return normalizeArray<MediaAsset>(res);
}

export async function uploadMedia(file: File): Promise<{ id: number; url: string; filename: string }> {
  const token = getToken();
  const form = new FormData();
  form.append('file', file);
  const baseUrl = getAdminApiUrl();
  const res = await fetch(`${baseUrl}/admin/media/upload`, {
    method: 'POST',
    headers: { Accept: 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}) },
    body: form,
  });

  let data;
  const text = await res.text();
  try {
    data = JSON.parse(text);
  } catch (err: any) {
    console.error('Upload Parse Error. Response text was:', text);
    throw new Error('Invalid JSON response from server');
  }

  if (!res.ok || !data.success) throw new Error(data.message || 'Upload failed');
  return data.data;
}

export async function deleteMedia(filename: string) {
  return adminFetch(`/media/${filename}`, { method: 'DELETE' });
}

export async function updateMediaFocalPoint(filename: string, x: number, y: number): Promise<MediaAsset> {
  const res = await adminFetch(`/media/${filename}/focal-point`, {
    method: 'PATCH',
    body: JSON.stringify({ x, y }),
  });
  return normalizeObject<MediaAsset>(res);
}

export async function updateMediaCropMode(filename: string, cropMode: 'cover' | 'contain'): Promise<MediaAsset> {
  const res = await adminFetch(`/media/${filename}/crop-mode`, {
    method: 'PATCH',
    body: JSON.stringify({ cropMode }),
  });
  return normalizeObject<MediaAsset>(res);
}

// ── Orders ────────────────────────────────────────────────────────────────────
export async function getOrders(params?: Record<string, string>): Promise<PaginatedResponse<any>> {
  const q = params ? '?' + new URLSearchParams(params).toString() : '';
  const res = await adminFetch(`/orders${q}`);
  return normalizePaginated<any>(res);
}

export async function getOrder(id: number | string) {
  const res = await adminFetch(`/orders/${id}`);
  return normalizeObject<any>(res);
}

export async function updateOrderStatus(id: number | string, data: Record<string, any>) {
  const res = await adminFetch(`/orders/${id}/status`, { method: 'PATCH', body: JSON.stringify(data) });
  return normalizeObject<any>(res);
}

export async function getOrderStats() {
  const res = await adminFetch('/orders/stats');
  return normalizeObject<any>(res);
}

// ── Customers ─────────────────────────────────────────────────────────────────
export async function getCustomers(params?: Record<string, string>): Promise<PaginatedResponse<any>> {
  const q = params ? '?' + new URLSearchParams(params).toString() : '';
  const res = await adminFetch(`/customers${q}`);
  return normalizePaginated<any>(res);
}

export async function getCustomer(id: number | string) {
  const res = await adminFetch(`/customers/${id}`);
  return normalizeObject<any>(res);
}

export async function banCustomer(id: number | string, reason?: string) {
  const res = await adminFetch(`/customers/${id}/ban`, { method: 'PATCH', body: JSON.stringify({ reason }) });
  return normalizeObject<any>(res);
}

export async function unbanCustomer(id: number | string) {
  const res = await adminFetch(`/customers/${id}/unban`, { method: 'PATCH' });
  return normalizeObject<any>(res);
}

// ── Products Media ────────────────────────────────────────────────────────────
export async function attachProductMedia(productId: number | string, assetId: number | string, group: string = 'gallery') {
  return adminFetch(`/products/${productId}/media`, { method: 'POST', body: JSON.stringify({ media_asset_id: assetId, group }) });
}

export async function detachProductMedia(productId: number | string, assetId: number | string) {
  return adminFetch(`/products/${productId}/media/${assetId}`, { method: 'DELETE' });
}

export async function reorderProductMedia(productId: number | string, orderedIds: (number | string)[]) {
  return adminFetch(`/products/${productId}/media/reorder`, { method: 'PATCH', body: JSON.stringify({ ordered_ids: orderedIds }) });
}

// ── Admin Collections ─────────────────────────────────────────────────────────
export async function getCollectionsAdmin(): Promise<Collection[]> {
  const res = await adminFetch('/collections');
  return normalizeArray<Collection>(res);
}

export async function getCollectionAdmin(id: number | string): Promise<Collection> {
  const res = await adminFetch(`/collections/${id}`);
  return normalizeObject<Collection>(res);
}

export async function createCollectionAdmin(data: Record<string, any>): Promise<Collection> {
  const res = await adminFetch('/collections', { method: 'POST', body: JSON.stringify(data) });
  return normalizeObject<Collection>(res);
}

export async function updateCollectionAdmin(id: number | string, data: Record<string, any>): Promise<Collection> {
  const res = await adminFetch(`/collections/${id}`, { method: 'PUT', body: JSON.stringify(data) });
  return normalizeObject<Collection>(res);
}

export async function deleteCollectionAdmin(id: number | string) {
  return adminFetch(`/collections/${id}`, { method: 'DELETE' });
}

// ── CMS Pages & Sections ──────────────────────────────────────────────────────
export async function getPages(): Promise<any[]> {
  const res = await adminFetch('/pages');
  return normalizeArray<any>(res);
}

export async function getPage(id: number | string) {
  const res = await adminFetch(`/pages/${id}`);
  return normalizeObject<any>(res);
}

export async function createPage(data: Record<string, any>) {
  const res = await adminFetch('/pages', { method: 'POST', body: JSON.stringify(data) });
  return normalizeObject<any>(res);
}

export async function updatePage(id: number | string, data: Record<string, any>) {
  const res = await adminFetch(`/pages/${id}`, { method: 'PUT', body: JSON.stringify(data) });
  return normalizeObject<any>(res);
}

export async function deletePage(id: number | string) {
  return adminFetch(`/pages/${id}`, { method: 'DELETE' });
}

export async function createPageSection(pageId: number | string, data: Record<string, any>) {
  const res = await adminFetch(`/pages/${pageId}/sections`, { method: 'POST', body: JSON.stringify(data) });
  return normalizeObject<any>(res);
}

export async function updatePageSection(pageId: number | string, sectionId: number | string, data: Record<string, any>) {
  const res = await adminFetch(`/pages/${pageId}/sections/${sectionId}`, { method: 'PUT', body: JSON.stringify(data) });
  return normalizeObject<any>(res);
}

export async function deletePageSection(pageId: number | string, sectionId: number | string) {
  return adminFetch(`/pages/${pageId}/sections/${sectionId}`, { method: 'DELETE' });
}

// ── Store Settings ─────────────────────────────────────────────────────────────
export async function getSettings(): Promise<Record<string, string>> {
  const res = await adminFetch('/settings');
  return normalizeObject<Record<string, string>>(res);
}

export async function updateSettings(data: Record<string, string>) {
  const res = await adminFetch('/settings', { method: 'PUT', body: JSON.stringify(data) });
  const result = normalizeObject<Record<string, string>>(res);
  if (typeof window !== 'undefined') {
    try {
      localStorage.setItem('cached_store_settings', JSON.stringify(result));
      window.dispatchEvent(new CustomEvent('store_settings_updated', { detail: result }));
    } catch {}
  }
  return result;
}

// ── Notifications ─────────────────────────────────────────────────────────────
export async function getNotifications(params?: Record<string, string>): Promise<PaginatedResponse<any>> {
  const q = params ? '?' + new URLSearchParams(params).toString() : '';
  const res = await adminFetch(`/notifications${q}`);
  return normalizePaginated<any>(res);
}

export async function getUnreadNotificationCount(): Promise<number> {
  const res = await adminFetch('/notifications/unread-count');
  return res?.data?.count ?? res?.count ?? 0;
}

export async function markNotificationRead(id: number | string) {
  return adminFetch(`/notifications/${id}/read`, { method: 'PATCH' });
}

export async function markAllNotificationsRead() {
  return adminFetch('/notifications/read-all', { method: 'POST' });
}

// ── Calendar ──────────────────────────────────────────────────────────────────
export async function getCalendarData(month: number, year: number) {
  const res = await adminFetch(`/dashboard/calendar?month=${month}&year=${year}`);
  return normalizeObject<any>(res);
}

// ── Reviews ───────────────────────────────────────────────────────────────────
export async function getReviews(params?: Record<string, string>): Promise<PaginatedResponse<any>> {
  const q = params ? '?' + new URLSearchParams(params).toString() : '';
  const res = await adminFetch(`/reviews${q}`);
  return normalizePaginated<any>(res);
}

export async function approveReview(id: number | string) {
  return adminFetch(`/reviews/${id}/approve`, { method: 'PATCH' });
}

export async function rejectReview(id: number | string) {
  return adminFetch(`/reviews/${id}/reject`, { method: 'PATCH' });
}

export async function deleteReview(id: number | string) {
  return adminFetch(`/reviews/${id}`, { method: 'DELETE' });
}

// ── Coupons ───────────────────────────────────────────────────────────────────
export async function getCoupons(params?: Record<string, string>): Promise<PaginatedResponse<any>> {
  const q = params ? '?' + new URLSearchParams(params).toString() : '';
  const res = await adminFetch(`/coupons${q}`);
  return normalizePaginated<any>(res);
}

export async function getCoupon(id: number | string) {
  const res = await adminFetch(`/coupons/${id}`);
  return normalizeObject<any>(res);
}

export async function createCoupon(data: Record<string, any>) {
  const res = await adminFetch('/coupons', { method: 'POST', body: JSON.stringify(data) });
  return normalizeObject<any>(res);
}

export async function updateCoupon(id: number | string, data: Record<string, any>) {
  const res = await adminFetch(`/coupons/${id}`, { method: 'PUT', body: JSON.stringify(data) });
  return normalizeObject<any>(res);
}

export async function deleteCoupon(id: number | string) {
  return adminFetch(`/coupons/${id}`, { method: 'DELETE' });
}

// ── Activity Log ──────────────────────────────────────────────────────────────
export async function getActivityLog(params?: Record<string, string>): Promise<PaginatedResponse<any>> {
  const q = params ? '?' + new URLSearchParams(params).toString() : '';
  const res = await adminFetch(`/activity${q}`);
  return normalizePaginated<any>(res);
}

// ── Customer Export ───────────────────────────────────────────────────────────
export async function exportCustomersCSV(): Promise<void> {
  const token = getToken();
  const baseUrl = getAdminApiUrl();
  const res = await fetch(`${baseUrl}/admin/customers/export`, {
    headers: { Accept: 'text/csv', ...(token ? { Authorization: `Bearer ${token}` } : {}) },
  });
  if (!res.ok) throw new Error('Export failed');
  const blob = await res.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `customers_${new Date().toISOString().split('T')[0]}.csv`;
  a.click();
  window.URL.revokeObjectURL(url);
}
