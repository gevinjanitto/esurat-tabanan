import Constants from "expo-constants";
import * as WebBrowser from "expo-web-browser";
import { useEffect, useState } from "react";
import { Linking, Pressable, ScrollView, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { useAuth } from "@/src/auth";
import { ActionSheet, SheetConfig } from "@/src/components/action-sheet";
import { Card } from "@/src/components/detail";
import { Icon } from "@/src/components/icon";
import { Button, DetailHeader, InfoRow, Section } from "@/src/components/ui";
import { ESURAT_URL } from "@/src/config";
import { initials } from "@/src/format";
import { getPushState, PushState, registerForPush } from "@/src/push";
import { font, makeStyles, spacing, useTheme } from "@/src/theme";

const PUSH_LABEL: Record<PushState, string> = {
  granted: "Aktif",
  undetermined: "Belum diaktifkan",
  denied: "Nonaktif",
  blocked: "Diblokir di Pengaturan",
  unsupported: "Hanya di aplikasi HP",
};

export default function Profil() {
  const { user, signOut } = useAuth();
  const insets = useSafeAreaInsets();
  const s = useStyles();
  const { colors } = useTheme();
  const [push, setPush] = useState<PushState | null>(null);
  const [sheet, setSheet] = useState<SheetConfig | null>(null);

  useEffect(() => {
    getPushState().then(setPush);
  }, []);

  const pushAction = async () => {
    if (push === "blocked") return Linking.openSettings();
    setPush(await registerForPush(true));
  };

  return (
    <View style={{ flex: 1, backgroundColor: colors.surfaceSecondary }} testID="profil-screen">
      <DetailHeader title="Profil" />
      <ScrollView contentContainerStyle={{ padding: spacing.lg, paddingBottom: insets.bottom + spacing.xl }}>
        <View style={s.head}>
          <View style={s.avatar}>
            <Text style={s.avatarText}>{initials(user?.nama)}</Text>
          </View>
          <Text style={s.name} testID="profil-nama">{user?.nama}</Text>
          <Text style={s.role}>{user?.role_label ?? user?.role}</Text>
        </View>
        <Section title="Akun">
          <Card>
            <InfoRow label="Username" value={user?.username} testID="profil-username" />
            <InfoRow label="NRK / NIP" value={user?.nrk} />
            <InfoRow label="Jabatan" value={user?.jabatan} />
            <InfoRow label="Satuan kerja" value={user?.satker} />
          </Card>
        </Section>
        <Section title="Pengaturan">
          <Card>
            <Pressable style={s.row} onPress={push === "granted" || push === "unsupported" ? undefined : pushAction} testID="profil-notif-row">
              <Icon name="notifications-outline" size={20} color={colors.brandPrimary} />
              <View style={{ flex: 1 }}>
                <Text style={s.rowTitle}>Notifikasi</Text>
                <Text style={s.rowSub} testID="profil-notif-status">{push ? PUSH_LABEL[push] : "…"}</Text>
              </View>
              {push && push !== "granted" && push !== "unsupported" ? (
                <Text style={s.rowAction}>{push === "blocked" ? "Buka Pengaturan" : "Aktifkan"}</Text>
              ) : null}
            </Pressable>
            <Pressable style={s.row} onPress={() => WebBrowser.openBrowserAsync(ESURAT_URL)} testID="profil-open-web">
              <Icon name="globe-outline" size={20} color={colors.brandPrimary} />
              <View style={{ flex: 1 }}>
                <Text style={s.rowTitle}>Buka versi web</Text>
                <Text style={s.rowSub}>Fitur lengkap administrasi & master data</Text>
              </View>
              <Icon name="open-outline" size={18} color={colors.muted} />
            </Pressable>
            <Pressable style={[s.row, { borderBottomWidth: 0 }]} onPress={() => WebBrowser.openBrowserAsync(`${ESURAT_URL}/privacy`)} testID="profil-privacy">
              <Icon name="shield-outline" size={20} color={colors.brandPrimary} />
              <Text style={[s.rowTitle, { flex: 1 }]}>Kebijakan Privasi</Text>
              <Icon name="chevron-forward" size={18} color={colors.muted} />
            </Pressable>
          </Card>
        </Section>
        <Text style={s.info}>Anda tetap masuk di aplikasi ini sampai menekan Keluar.</Text>
        <Button
          label="Keluar"
          variant="danger"
          icon="log-out-outline"
          style={{ marginTop: spacing.lg }}
          testID="profil-logout-button"
          onPress={() =>
            setSheet({
              title: "Keluar dari akun?",
              description: "Anda perlu masuk kembali untuk membuka surat dan menerima notifikasi.",
              confirmLabel: "Keluar",
              danger: true,
              onConfirm: async () => {
                await signOut();
              },
            })
          }
        />
        <Text style={s.version}>E-Surat Tabanan v{Constants.expoConfig?.version ?? "1.0.0"}</Text>
      </ScrollView>
      <ActionSheet config={sheet} onClose={() => setSheet(null)} />
    </View>
  );
}

const useStyles = makeStyles((c) => ({
  head: { alignItems: "center", paddingVertical: spacing.lg },
  avatar: { width: 80, height: 80, borderRadius: 40, backgroundColor: c.brandPrimary, alignItems: "center", justifyContent: "center" },
  avatarText: { fontFamily: font.heavy, fontSize: 28, color: c.onBrandPrimary },
  name: { fontFamily: font.heavy, fontSize: 20, color: c.onSurface, marginTop: spacing.md, textAlign: "center" },
  role: { fontFamily: font.semibold, fontSize: 13, color: c.brandPrimary, marginTop: 2 },
  row: { flexDirection: "row", alignItems: "center", gap: spacing.md, paddingVertical: spacing.md, borderBottomWidth: 1, borderBottomColor: c.divider, minHeight: 56 },
  rowTitle: { fontFamily: font.semibold, fontSize: 15, color: c.onSurface },
  rowSub: { fontFamily: font.regular, fontSize: 12, color: c.muted },
  rowAction: { fontFamily: font.bold, fontSize: 13, color: c.brandPrimary },
  info: { fontFamily: font.regular, fontSize: 12, color: c.muted, textAlign: "center", marginTop: spacing.xl },
  version: { fontFamily: font.regular, fontSize: 11, color: c.muted, textAlign: "center", marginTop: spacing.lg },
}));
