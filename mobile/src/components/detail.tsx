import * as WebBrowser from "expo-web-browser";
import { ReactNode, useState } from "react";
import { ActivityIndicator, Pressable, Text, View } from "react-native";

import { api, withToken } from "@/src/api";
import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";
import { Icon } from "./icon";
import { useToast } from "./toast";

// Kartu buka PDF: token akses diperbarui dulu lalu PDF dibuka di browser dalam aplikasi.
export function PdfCard({ url, testID = "open-pdf-button" }: { url?: string | null; testID?: string }) {
  const s = useStyles();
  const { colors } = useTheme();
  const toast = useToast();
  const [busy, setBusy] = useState(false);
  if (!url) {
    return (
      <View style={[s.pdf, { opacity: 0.6 }]} testID="pdf-unavailable">
        <Icon name="document-outline" size={26} color={colors.muted} />
        <Text style={s.pdfSub}>Belum ada berkas PDF</Text>
      </View>
    );
  }
  return (
    <Pressable
      style={({ pressed }) => [s.pdf, pressed && { opacity: 0.85 }]}
      testID={testID}
      onPress={async () => {
        setBusy(true);
        try {
          await api("/me");
          await WebBrowser.openBrowserAsync(withToken(url));
        } catch (e: any) {
          toast(e?.message ?? "Gagal membuka PDF", "error");
        } finally {
          setBusy(false);
        }
      }}
    >
      <View style={s.pdfIcon}>
        <Icon name="document-text" size={24} color={colors.onBrandPrimary} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={s.pdfTitle}>Lihat berkas surat</Text>
        <Text style={s.pdfSub}>Buka dokumen PDF</Text>
      </View>
      {busy ? <ActivityIndicator color={colors.brandPrimary} /> : <Icon name="open-outline" size={20} color={colors.brandPrimary} />}
    </Pressable>
  );
}

export function Card({ children, testID }: { children: ReactNode; testID?: string }) {
  const s = useStyles();
  return <View style={s.card} testID={testID}>{children}</View>;
}

export function StickyBar({ children, bottom }: { children: ReactNode; bottom: number }) {
  const s = useStyles();
  return <View style={[s.sticky, { paddingBottom: bottom + spacing.md }]}>{children}</View>;
}

export const useDetailStyles = () => useStyles();

const useStyles = makeStyles((c) => ({
  pdf: { flexDirection: "row", alignItems: "center", gap: spacing.md, backgroundColor: c.brandTertiary, borderRadius: radius.md, padding: spacing.lg, marginTop: spacing.lg },
  pdfIcon: { width: 48, height: 48, borderRadius: radius.md, backgroundColor: c.brandPrimary, alignItems: "center", justifyContent: "center" },
  pdfTitle: { fontFamily: font.bold, fontSize: 15, color: c.onBrandTertiary },
  pdfSub: { fontFamily: font.regular, fontSize: 12, color: c.muted },
  card: { backgroundColor: c.surface, borderRadius: radius.md, paddingHorizontal: spacing.lg, borderWidth: 1, borderColor: c.border },
  sticky: { flexDirection: "row", gap: spacing.md, paddingHorizontal: spacing.lg, paddingTop: spacing.md, backgroundColor: c.surface, borderTopWidth: 1, borderTopColor: c.border },
  h1: { fontFamily: font.heavy, fontSize: 22, color: c.onSurface, lineHeight: 30, marginTop: spacing.sm },
  body: { fontFamily: font.regular, fontSize: 14, color: c.onSurfaceSecondary, lineHeight: 21 },
  muted: { fontFamily: font.regular, fontSize: 13, color: c.muted },
  step: { flexDirection: "row", gap: spacing.md },
  stepRail: { alignItems: "center", width: 24 },
  stepDot: { width: 24, height: 24, borderRadius: 12, alignItems: "center", justifyContent: "center" },
  stepLine: { flex: 1, width: 2, backgroundColor: c.border, marginVertical: 2 },
  stepName: { fontFamily: font.bold, fontSize: 14, color: c.onSurface },
  stepMeta: { fontFamily: font.regular, fontSize: 12, color: c.muted, marginTop: 2 },
  note: { fontFamily: font.regular, fontSize: 13, color: c.onSurfaceTertiary, marginTop: spacing.xs, fontStyle: "italic" },
  linkRow: { flexDirection: "row", alignItems: "center", gap: spacing.md, paddingVertical: spacing.md, borderBottomWidth: 1, borderBottomColor: c.divider, minHeight: 56 },
}));
