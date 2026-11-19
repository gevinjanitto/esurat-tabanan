import { useEffect, useState } from "react";
import { Platform, Pressable, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { Icon } from "@/src/components/icon";
import { Button } from "@/src/components/ui";
import { font, makeStyles, radius, shadow, spacing, useTheme } from "@/src/theme";

// Panduan pasang PWA (tanpa APK). Muncul saat dibuka dari tombol "Instal aplikasi E-Surat" di halaman login web (?install=...).
const web = Platform.OS === "web" && typeof window !== "undefined";
let deferred: any = null;
const subs = new Set<() => void>();
if (web) {
  window.addEventListener("beforeinstallprompt", (e: any) => {
    e.preventDefault();
    deferred = e;
    subs.forEach((f) => f());
  });
}
const wanted = web && /[?&]install=/.test(window.location.search);
const isIOS = web && (/iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1));
const standalone = () => web && (window.matchMedia?.("(display-mode: standalone)").matches || (navigator as any).standalone === true);

export function InstallPwa() {
  const insets = useSafeAreaInsets();
  const s = useStyles();
  const { colors } = useTheme();
  const [open, setOpen] = useState(wanted && !standalone());
  const [canPrompt, setCanPrompt] = useState(!!deferred);

  useEffect(() => {
    if (!web) return;
    const f = () => setCanPrompt(!!deferred);
    const done = () => setOpen(false);
    subs.add(f);
    window.addEventListener("appinstalled", done);
    return () => {
      subs.delete(f);
      window.removeEventListener("appinstalled", done);
    };
  }, []);

  if (!open) return null;

  const install = async () => {
    deferred.prompt();
    await deferred.userChoice.catch(() => null);
    deferred = null;
    setCanPrompt(false);
    setOpen(false);
  };

  const steps = isIOS
    ? ["Ketuk tombol Bagikan (kotak dengan panah) di Safari", "Pilih \"Tambah ke Layar Utama\"", "Ketuk \"Tambah\" — ikon E-Surat muncul di layar utama"]
    : ["Buka menu ⋮ di pojok kanan atas browser", "Pilih \"Instal aplikasi\" / \"Tambahkan ke layar utama\"", "Ketuk \"Instal\" — ikon E-Surat muncul di layar utama"];

  return (
    <View style={[s.wrap, { bottom: insets.bottom + spacing.lg }]} testID="pwa-install-card">
      <View style={s.head}>
        <View style={s.badge}>
          <Icon name="download-outline" size={20} color={colors.brandPrimary} />
        </View>
        <View style={{ flex: 1 }}>
          <Text style={s.title}>Pasang E-Surat di HP</Text>
          <Text style={s.sub}>Tanpa APK · {isIOS ? "iPhone / iPad" : "Android"}</Text>
        </View>
        <Pressable onPress={() => setOpen(false)} style={s.close} testID="pwa-install-close">
          <Icon name="close" size={20} color={colors.muted} />
        </Pressable>
      </View>
      {canPrompt && !isIOS ? (
        <Button label="Pasang Sekarang" icon="download-outline" onPress={install} style={{ marginTop: spacing.md }} testID="pwa-install-button" />
      ) : (
        <View style={{ marginTop: spacing.md, gap: spacing.sm }} testID="pwa-install-steps">
          {steps.map((t, i) => (
            <View key={t} style={s.step}>
              <Text style={s.num}>{i + 1}</Text>
              <Text style={s.stepText}>{t}</Text>
            </View>
          ))}
        </View>
      )}
    </View>
  );
}

const useStyles = makeStyles((c) => ({
  wrap: { position: "absolute", left: spacing.lg, right: spacing.lg, backgroundColor: c.surface, borderRadius: radius.lg, padding: spacing.lg, borderWidth: 1, borderColor: c.border, ...shadow },
  head: { flexDirection: "row", alignItems: "center", gap: spacing.md },
  badge: { width: 40, height: 40, borderRadius: radius.md, backgroundColor: c.brandTertiary, alignItems: "center", justifyContent: "center" },
  title: { fontFamily: font.bold, fontSize: 15, color: c.onSurface },
  sub: { fontFamily: font.regular, fontSize: 12, color: c.muted, marginTop: 1 },
  close: { width: 44, height: 44, alignItems: "center", justifyContent: "center" },
  step: { flexDirection: "row", alignItems: "center", gap: spacing.sm },
  num: { width: 22, height: 22, borderRadius: 11, backgroundColor: c.surfaceInverse, color: c.onSurfaceInverse, textAlign: "center", lineHeight: 22, fontFamily: font.bold, fontSize: 11, overflow: "hidden" },
  stepText: { flex: 1, fontFamily: font.semibold, fontSize: 13, color: c.onSurface },
}));
