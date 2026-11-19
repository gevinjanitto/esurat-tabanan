import Constants from "expo-constants";
import * as Device from "expo-device";
import * as Notifications from "expo-notifications";
import { Platform } from "react-native";

import { getAccess } from "./api";
import { BACKEND_URL } from "./config";

export type PushState = "unsupported" | "granted" | "undetermined" | "denied" | "blocked";

const isExpoGo = Constants.executionEnvironment === "storeClient";

export async function getPushState(): Promise<PushState> {
  if (Platform.OS === "web" || !Device.isDevice) return "unsupported";
  const p = await Notifications.getPermissionsAsync();
  if (p.status === "granted") return "granted";
  if (p.status === "undetermined") return "undetermined";
  return p.canAskAgain ? "denied" : "blocked";
}

// Daftarkan token perangkat ke relay push (dipanggil setiap aplikasi dibuka karena token bisa berganti).
export async function registerForPush(askPermission: boolean): Promise<PushState> {
  if (Platform.OS === "web" || !Device.isDevice) return "unsupported";
  try {
    let state = await getPushState();
    if (state !== "granted" && askPermission && state !== "blocked") {
      await Notifications.requestPermissionsAsync();
      state = await getPushState();
    }
    if (state !== "granted" || isExpoGo) return state;
    const token = await Notifications.getDevicePushTokenAsync();
    await fetch(`${BACKEND_URL}/api/register-push`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${getAccess()}` },
      body: JSON.stringify({ platform: Platform.OS, device_token: token.data }),
    });
    return state;
  } catch (e) {
    console.warn("registerForPush", e);
    return getPushState();
  }
}

// URL notifikasi dari server web -> rute aplikasi.
export function routeFromUrl(url?: string | null): string | null {
  if (!url) return null;
  const m = String(url).match(/\/(surat-masuk|surat-keluar|disposisi|agenda)\/(\d+)/);
  return m ? `/${m[1]}/${m[2]}` : "/";
}
