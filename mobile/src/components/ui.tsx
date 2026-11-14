import { router } from "expo-router";
import { ReactNode } from "react";
import { ActivityIndicator, Pressable, Text, View, ViewStyle } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";
import { Icon, IconName } from "./icon";

export function DetailHeader({ title, testID, right }: { title: string; testID?: string; right?: ReactNode }) {
  const insets = useSafeAreaInsets();
  const styles = useStyles();
  const { colors } = useTheme();
  return (
    <View style={[styles.detailHeader, { paddingTop: insets.top + spacing.sm }]} testID={testID}>
      <Pressable
        onPress={() => (router.canGoBack() ? router.back() : router.replace("/"))}
        style={({ pressed }) => [styles.backBtn, pressed && { opacity: 0.6 }]}
        hitSlop={8}
        testID="header-back-button"
      >
        <Icon name="chevron-back" size={24} color={colors.brandPrimary} />
      </Pressable>
      <Text style={styles.detailTitle} numberOfLines={1}>{title}</Text>
      <View style={styles.backBtn}>{right}</View>
    </View>
  );
}

type Tone = "success" | "warning" | "error" | "info" | "brand" | "neutral";

export function toneFor(label?: string | null): Tone {
  const s = (label ?? "").toLowerCase();
  if (/selesai|disetujui|ditandatangani|terkirim ke|arsip/.test(s)) return "success";
  if (/revisi|tolak|batal|kedaluwarsa/.test(s)) return "error";
  if (/verifikasi|tanda ?tangan|menunggu|proses|draft|konsep/.test(s)) return "warning";
  if (/dibaca|diteruskan|tindak/.test(s)) return "info";
  if (/baru|terkirim/.test(s)) return "brand";
  return "neutral";
}

export function Badge({ label, testID }: { label?: string | null; testID?: string }) {
  const { colors } = useTheme();
  const styles = useStyles();
  if (!label) return null;
  const tone = toneFor(label);
  const map = {
    success: [colors.successSoft, colors.success],
    warning: [colors.warningSoft, colors.onBrandSecondary],
    error: [colors.errorSoft, colors.error],
    info: [colors.infoSoft, colors.info],
    brand: [colors.brandTertiary, colors.onBrandTertiary],
    neutral: [colors.surfaceSecondary, colors.onSurfaceTertiary],
  } as const;
  const [bg, fg] = map[tone];
  return (
    <View style={[styles.badge, { backgroundColor: bg }]} testID={testID}>
      <Text style={[styles.badgeText, { color: fg }]} numberOfLines={1}>{label}</Text>
    </View>
  );
}

export function Segmented<K extends string>({
  options,
  value,
  onChange,
  testID,
}: {
  options: { key: K; label: string }[];
  value: K;
  onChange: (k: K) => void;
  testID?: string;
}) {
  const styles = useStyles();
  return (
    <View style={styles.segment} testID={testID}>
      {options.map((o) => {
        const active = o.key === value;
        return (
          <Pressable
            key={o.key}
            onPress={() => onChange(o.key)}
            style={[styles.segmentItem, active && styles.segmentActive]}
            testID={`${testID}-${o.key}`}
          >
            <Text style={[styles.segmentText, active && styles.segmentTextActive]}>{o.label}</Text>
          </Pressable>
        );
      })}
    </View>
  );
}

export function Button({
  label,
  onPress,
  loading,
  variant = "primary",
  icon,
  testID,
  disabled,
  style,
}: {
  label: string;
  onPress: () => void;
  loading?: boolean;
  variant?: "primary" | "secondary" | "danger" | "ghost";
  icon?: IconName;
  testID?: string;
  disabled?: boolean;
  style?: ViewStyle;
}) {
  const styles = useStyles();
  const { colors } = useTheme();
  const v = {
    primary: [colors.brandPrimary, colors.onBrandPrimary, colors.brandPrimary],
    secondary: [colors.surface, colors.brandPrimary, colors.borderStrong],
    danger: [colors.errorSoft, colors.error, colors.errorSoft],
    ghost: [colors.brandTertiary, colors.onBrandTertiary, colors.brandTertiary],
  }[variant];
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled || loading}
      testID={testID}
      style={({ pressed }) => [
        styles.btn,
        { backgroundColor: v[0], borderColor: v[2], opacity: disabled ? 0.5 : pressed ? 0.85 : 1, transform: [{ scale: pressed ? 0.98 : 1 }] },
        style,
      ]}
    >
      {loading ? (
        <ActivityIndicator color={v[1]} />
      ) : (
        <>
          {icon && <Icon name={icon} size={18} color={v[1]} />}
          <Text style={[styles.btnText, { color: v[1] }]}>{label}</Text>
        </>
      )}
    </Pressable>
  );
}

export function InfoRow({ label, value, testID }: { label: string; value?: string | number | null; testID?: string }) {
  const styles = useStyles();
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue} testID={testID} selectable>{value === null || value === undefined || value === "" ? "-" : String(value)}</Text>
    </View>
  );
}

export function Section({ title, children, right }: { title: string; children: ReactNode; right?: ReactNode }) {
  const styles = useStyles();
  return (
    <View style={styles.section}>
      <View style={styles.sectionHead}>
        <Text style={styles.sectionTitle}>{title}</Text>
        {right}
      </View>
      {children}
    </View>
  );
}

export function EmptyState({ icon = "file-tray-outline", title, desc, testID }: { icon?: IconName; title: string; desc?: string; testID?: string }) {
  const styles = useStyles();
  const { colors } = useTheme();
  return (
    <View style={styles.state} testID={testID}>
      <View style={styles.stateIcon}>
        <Icon name={icon} size={30} color={colors.brandPrimary} />
      </View>
      <Text style={styles.stateTitle}>{title}</Text>
      {desc ? <Text style={styles.stateDesc}>{desc}</Text> : null}
    </View>
  );
}

export function ErrorState({ message, onRetry, testID = "error-state" }: { message?: string; onRetry: () => void; testID?: string }) {
  const styles = useStyles();
  const { colors } = useTheme();
  return (
    <View style={styles.state} testID={testID}>
      <View style={[styles.stateIcon, { backgroundColor: colors.errorSoft }]}>
        <Icon name="cloud-offline-outline" size={30} color={colors.error} />
      </View>
      <Text style={styles.stateTitle}>Gagal memuat data</Text>
      <Text style={styles.stateDesc}>{message}</Text>
      <Button label="Coba lagi" icon="refresh" variant="secondary" onPress={onRetry} testID={`${testID}-retry`} style={{ marginTop: spacing.lg, alignSelf: "center", paddingHorizontal: spacing.xl }} />
    </View>
  );
}

export function SkeletonList({ count = 5 }: { count?: number }) {
  const styles = useStyles();
  return (
    <View style={{ padding: spacing.lg, gap: spacing.md }} testID="skeleton-list">
      {Array.from({ length: count }).map((_, i) => (
        <View key={i} style={styles.skel}>
          <View style={[styles.skelLine, { width: "70%" }]} />
          <View style={[styles.skelLine, { width: "45%", marginTop: spacing.sm }]} />
        </View>
      ))}
    </View>
  );
}

export const useUiStyles = () => useStyles();

const useStyles = makeStyles((c) => ({
  detailHeader: {
    backgroundColor: c.surface,
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: spacing.sm,
    paddingBottom: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: c.border,
  },
  backBtn: { width: 44, height: 44, alignItems: "center", justifyContent: "center" },
  detailTitle: { flex: 1, textAlign: "center", fontFamily: font.bold, fontSize: 16, color: c.onSurface },
  badge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: radius.pill, alignSelf: "flex-start", flexShrink: 0, maxWidth: 180 },
  badgeText: { fontFamily: font.semibold, fontSize: 11 },
  segment: { flexDirection: "row", backgroundColor: c.surfaceSecondary, borderRadius: radius.md, padding: 4, height: 44 },
  segmentItem: { flex: 1, alignItems: "center", justifyContent: "center", borderRadius: 9 },
  segmentActive: {
    backgroundColor: c.surface,
    shadowColor: c.surfaceInverse,
    shadowOpacity: 0.08,
    shadowRadius: 4,
    shadowOffset: { width: 0, height: 1 },
    elevation: 2,
  },
  segmentText: { fontFamily: font.semibold, fontSize: 14, color: c.muted },
  segmentTextActive: { color: c.brandPrimary },
  btn: {
    minHeight: 50,
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
  },
  btnText: { fontFamily: font.bold, fontSize: 15 },
  infoRow: { paddingVertical: spacing.md, borderBottomWidth: 1, borderBottomColor: c.divider },
  infoLabel: { fontFamily: font.regular, fontSize: 12, color: c.muted },
  infoValue: { fontFamily: font.semibold, fontSize: 15, color: c.onSurface, marginTop: 2 },
  section: { marginTop: spacing.xl },
  sectionHead: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: spacing.md },
  sectionTitle: { fontFamily: font.bold, fontSize: 16, color: c.onSurface },
  state: { alignItems: "center", paddingVertical: spacing["3xl"], paddingHorizontal: spacing.xl },
  stateIcon: { width: 64, height: 64, borderRadius: 32, backgroundColor: c.brandTertiary, alignItems: "center", justifyContent: "center" },
  stateTitle: { fontFamily: font.bold, fontSize: 16, color: c.onSurface, marginTop: spacing.lg, textAlign: "center" },
  stateDesc: { fontFamily: font.regular, fontSize: 14, color: c.muted, marginTop: spacing.xs, textAlign: "center" },
  skel: { backgroundColor: c.surface, borderRadius: radius.md, padding: spacing.lg, borderWidth: 1, borderColor: c.border },
  skelLine: { height: 12, borderRadius: 6, backgroundColor: c.surfaceTertiary },
}));
