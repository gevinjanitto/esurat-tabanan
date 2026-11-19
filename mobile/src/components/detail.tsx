import { router } from "expo-router";
import { ReactNode } from "react";
import { ActivityIndicator, Pressable, Text, View } from "react-native";

import { font, makeStyles, radius, shadow, spacing, useTheme } from "@/src/theme";
import { Icon } from "./icon";
import { AnnoRow, PdfStudio, usePdfSrc } from "./pdf-studio";

// Pratinjau PDF langsung tampil di halaman detail (tanpa tap tambahan).
export function InlinePdf({
  url,
  type,
  id,
  rows,
  canEdit,
  noteCount,
}: {
  url?: string | null;
  type: "masuk" | "keluar";
  id: string | number;
  rows?: AnnoRow[];
  canEdit?: boolean;
  noteCount?: number;
}) {
  const s = useStyles();
  const { colors } = useTheme();
  const src = usePdfSrc(url);
  const open = () => router.push({ pathname: "/studio", params: { type, id: String(id) } });

  if (!url) {
    return (
      <View style={[s.pdfEmpty]} testID="pdf-unavailable">
        <View style={s.emptyIcon}>
          <Icon name="document-outline" size={24} color={colors.muted} />
        </View>
        <Text style={s.pdfSub}>Belum ada berkas PDF</Text>
      </View>
    );
  }

  return (
    <View style={s.pdfWrap} testID="inline-pdf-preview">
      <View style={s.pdfHead}>
        <View style={s.pdfIcon}>
          <Icon name="document-text" size={18} color={colors.onBrandPrimary} />
        </View>
        <View style={{ flex: 1 }}>
          <Text style={s.pdfTitle}>Berkas surat</Text>
          <Text style={s.pdfSub}>{noteCount ? `${noteCount} catatan revisi` : "Geser untuk membaca"}</Text>
        </View>
        <Pressable onPress={open} style={({ pressed }) => [s.roundBtn, pressed && { opacity: 0.7 }]} testID="inline-pdf-fullscreen-button" hitSlop={6}>
          <Icon name="expand-outline" size={18} color={colors.onSurface} />
        </Pressable>
      </View>
      <View style={s.viewer}>
        {src.data ? (
          <PdfStudio src={src.data} rows={rows} compact style={{ flex: 1 }} testID="inline-pdf-studio" />
        ) : src.isError ? (
          <View style={s.center}>
            <Text style={s.pdfSub}>{(src.error as Error).message}</Text>
          </View>
        ) : (
          <View style={s.center}>
            <ActivityIndicator color={colors.brandPrimary} />
          </View>
        )}
      </View>
      {canEdit ? (
        <Pressable onPress={open} style={({ pressed }) => [s.editBtn, pressed && { transform: [{ scale: 0.98 }] }]} testID="inline-pdf-annotate-button">
          <Icon name="brush-outline" size={18} color={colors.onSurfaceInverse} />
          <Text style={s.editText}>Coret & Edit Surat</Text>
          <View style={s.editArrow}>
            <Icon name="arrow-forward" size={16} color={colors.onBrandPrimary} />
          </View>
        </Pressable>
      ) : null}
    </View>
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
  pdfWrap: { backgroundColor: c.surface, borderRadius: radius.lg, padding: spacing.md, marginTop: spacing.lg, ...shadow },
  pdfHead: { flexDirection: "row", alignItems: "center", gap: spacing.md, paddingHorizontal: spacing.xs, paddingBottom: spacing.md },
  pdfIcon: { width: 40, height: 40, borderRadius: radius.md, backgroundColor: c.brandPrimary, alignItems: "center", justifyContent: "center" },
  pdfTitle: { fontFamily: font.bold, fontSize: 15, color: c.onSurface },
  pdfSub: { fontFamily: font.regular, fontSize: 12, color: c.muted },
  roundBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: c.surfaceSecondary, alignItems: "center", justifyContent: "center" },
  viewer: { height: 460, borderRadius: radius.md + 4, overflow: "hidden", backgroundColor: c.surfaceTertiary },
  center: { flex: 1, alignItems: "center", justifyContent: "center", padding: spacing.lg },
  pdfEmpty: { alignItems: "center", gap: spacing.sm, backgroundColor: c.surface, borderRadius: radius.lg, padding: spacing.xl, marginTop: spacing.lg },
  emptyIcon: { width: 48, height: 48, borderRadius: 24, backgroundColor: c.surfaceSecondary, alignItems: "center", justifyContent: "center" },
  editBtn: { flexDirection: "row", alignItems: "center", gap: spacing.sm, backgroundColor: c.surfaceInverse, borderRadius: radius.pill, paddingLeft: spacing.xl, paddingRight: spacing.xs, height: 56, marginTop: spacing.md },
  editText: { flex: 1, fontFamily: font.bold, fontSize: 15, color: c.onSurfaceInverse },
  editArrow: { width: 48, height: 48, borderRadius: 24, backgroundColor: c.brandPrimary, alignItems: "center", justifyContent: "center" },
  card: { backgroundColor: c.surface, borderRadius: radius.lg, paddingHorizontal: spacing.xl, paddingVertical: spacing.xs, ...shadow },
  sticky: { flexDirection: "row", gap: spacing.md, paddingHorizontal: spacing.lg, paddingTop: spacing.md, backgroundColor: c.surface, borderTopLeftRadius: radius.lg, borderTopRightRadius: radius.lg, ...shadow },
  h1: { fontFamily: font.heavy, fontSize: 24, color: c.onSurface, lineHeight: 32, marginTop: spacing.sm },
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
