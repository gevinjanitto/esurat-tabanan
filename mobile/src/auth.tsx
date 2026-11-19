import { createContext, ReactNode, useCallback, useContext, useEffect, useState } from "react";
import { Platform } from "react-native";

import { api, ApiError, clearTokens, loadTokens, saveTokens, setOnExpired } from "./api";
import { registerForPush } from "./push";
import { queryClient } from "./query-client";
import { storage } from "@/src/utils/storage";

export type User = {
  id: number;
  nama: string;
  username: string;
  nrk?: string | null;
  role: string;
  role_label?: string;
  disposisi_awal?: boolean;
  satker?: string | null;
  jabatan?: string | null;
};

type AuthCtx = {
  user: User | null;
  ready: boolean;
  signIn: (username: string, password: string, tipe: "pegawai" | "admin") => Promise<void>;
  signOut: () => Promise<void>;
};

const Ctx = createContext<AuthCtx>(null as unknown as AuthCtx);
const USER_KEY = "esurat_user";

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    setOnExpired(() => {
      setUser(null);
      storage.removeItem(USER_KEY);
      queryClient.clear();
    });
    (async () => {
      if (await loadTokens()) {
        try {
          const r = await api<{ user: User }>("/me");
          setUser(r.user);
          storage.setItem(USER_KEY, JSON.stringify(r.user));
        } catch (e) {
          // Offline / server gangguan: tetap masuk memakai data tersimpan.
          if (!(e instanceof ApiError) || e.status !== 401) {
            const cached = await storage.getItem(USER_KEY, null);
            if (typeof cached === "string") setUser(JSON.parse(cached));
          }
        }
      }
      setReady(true);
    })();
  }, []);

  // Daftar ulang token push setiap kali aplikasi dibuka (tanpa memunculkan izin).
  useEffect(() => {
    if (user && Platform.OS !== "web") registerForPush(false);
  }, [user]);

  const signIn = useCallback(async (username: string, password: string, tipe: "pegawai" | "admin") => {
    const r = await api<any>("/auth", {
      method: "POST",
      body: { username: username.trim(), password, tipe, device: `${Platform.OS} app` },
    });
    await saveTokens(r.access_token, r.refresh_token);
    storage.setItem(USER_KEY, JSON.stringify(r.user));
    setUser(r.user);
  }, []);

  const signOut = useCallback(async () => {
    try {
      await api("/logout", { method: "POST" }, false);
    } catch {}
    await clearTokens();
    await storage.removeItem(USER_KEY);
    queryClient.clear();
    setUser(null);
  }, []);

  return <Ctx.Provider value={{ user, ready, signIn, signOut }}>{children}</Ctx.Provider>;
}

export const useAuth = () => useContext(Ctx);
