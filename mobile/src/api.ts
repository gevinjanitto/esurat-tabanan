import { storage } from "@/src/utils/storage";
import { ESURAT_URL } from "./config";

// Token aplikasi disimpan di Keychain/Keystore: aplikasi tetap masuk sampai pengguna logout.
const ACCESS_KEY = "esurat_access";
const REFRESH_KEY = "esurat_refresh";

let access: string | null = null;
let refresh: string | null = null;
let onExpired: (() => void) | null = null;
let refreshing: Promise<boolean> | null = null;

export class ApiError extends Error {
  status: number;
  constructor(message: string, status: number) {
    super(message);
    this.status = status;
  }
}

export function setOnExpired(fn: () => void) {
  onExpired = fn;
}

export function getAccess() {
  return access;
}

export async function loadTokens(): Promise<boolean> {
  access = await storage.secureGet(ACCESS_KEY, null);
  refresh = await storage.secureGet(REFRESH_KEY, null);
  return !!(access || refresh);
}

export async function saveTokens(a: string, r: string) {
  access = a;
  refresh = r;
  await storage.secureSet(ACCESS_KEY, a);
  await storage.secureSet(REFRESH_KEY, r);
}

export async function clearTokens() {
  access = null;
  refresh = null;
  await storage.secureRemove(ACCESS_KEY);
  await storage.secureRemove(REFRESH_KEY);
}

// true = token baru; false = refresh token ditolak server (harus login ulang).
// Gangguan jaringan melempar error agar sesi TIDAK dihapus saat offline.
async function doRefresh(): Promise<boolean> {
  if (!refresh) return false;
  const res = await fetch(`${ESURAT_URL}/api/m/refresh-access`, {
    method: "POST",
    headers: { Accept: "application/json", "Content-Type": "application/json" },
    body: JSON.stringify({ refresh_token: refresh }),
  });
  if (res.status === 401) return false;
  if (!res.ok) throw new ApiError("Server sedang bermasalah, coba lagi.", res.status);
  const j = await res.json();
  await saveTokens(j.access_token, j.refresh_token);
  return true;
}

type Opts = { method?: "GET" | "POST"; body?: any; query?: Record<string, any> };

export async function api<T = any>(path: string, opts: Opts = {}, retry = true): Promise<T> {
  const qs = opts.query
    ? Object.entries(opts.query)
        .filter(([, v]) => v !== undefined && v !== null && v !== "")
        .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`)
        .join("&")
    : "";
  let res: Response;
  try {
    res = await fetch(`${ESURAT_URL}/api/m${path}${qs ? `?${qs}` : ""}`, {
      method: opts.method ?? "GET",
      headers: {
        Accept: "application/json",
        ...(opts.body ? { "Content-Type": "application/json" } : {}),
        ...(access ? { Authorization: `Bearer ${access}` } : {}),
      },
      body: opts.body ? JSON.stringify(opts.body) : undefined,
    });
  } catch {
    throw new ApiError("Tidak dapat terhubung. Periksa koneksi internet Anda.", 0);
  }
  if (res.status === 401 && retry && path !== "/auth") {
    refreshing ??= doRefresh().finally(() => {
      refreshing = null;
    });
    const ok = await refreshing;
    if (ok) return api<T>(path, opts, false);
    await clearTokens();
    onExpired?.();
    throw new ApiError("Sesi berakhir, silakan masuk kembali.", 401);
  }
  let j: any = null;
  try {
    j = await res.json();
  } catch {}
  if (!res.ok || j?.status === false) {
    const fallback =
      res.status === 403 ? "Anda tidak memiliki akses." : res.status === 404 ? "Data tidak ditemukan." : `Terjadi kesalahan (${res.status}).`;
    throw new ApiError(j?.message || fallback, res.status);
  }
  return j as T;
}

export function withToken(url: string) {
  return `${url}${url.includes("?") ? "&" : "?"}token=${encodeURIComponent(access ?? "")}`;
}
