import { Platform } from "react-native";

const envUrl = process.env.EXPO_PUBLIC_ESURAT_URL as string;
// PWA yang dilayani server E-Surat sendiri (/m, dibangun tanpa EXPO_PUBLIC_ESURAT_URL) memakai domain yang sama.
export const SAME_ORIGIN = !envUrl && Platform.OS === "web" && typeof window !== "undefined";
export const ESURAT_URL = (SAME_ORIGIN ? window.location.origin : envUrl).replace(/\/$/, "");
export const BACKEND_URL = process.env.EXPO_PUBLIC_BACKEND_URL as string;
