import { useQueries, useQuery } from "@tanstack/react-query";
import { router } from "expo-router";
import { Pressable, RefreshControl, ScrollView, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { api } from "@/src/api";
import { useAuth } from "@/src/auth";
import { Icon, IconName } from "@/src/components/icon";
import { NotifCard } from "@/src/components/notif-card";
import { AgendaRow } from "@/src/components/rows";
import { EmptyState, Section } from "@/src/components/ui";
import { initials } from "@/src/format";
import { usesNativeTabs } from "@/src/navigation";
import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";

export default function Beranda() {
  const { user } = useAuth();
  const insets = useSafeAreaInsets();
  const bottomChrome = usesNativeTabs ? insets.bottom : 0;
  const s = useStyles();
  const { colors } = useTheme();

  const stats = useQueries({
    queries: [
      { queryKey: ["stat", "masuk"], queryFn: () => api("/surat/masuk") },
      { queryKey: ["stat", "keluar"], queryFn: () => api("/surat/keluar") },
      { queryKey: ["stat", "disposisi"], queryFn: () => api("/disposisi") },
      { queryKey: ["antrean", "verifikasi"], queryFn: () => api("/surat/keluar/verifikasi") },
      { queryKey: ["antrean", "tandatangan"], queryFn: () => api("/surat/keluar/tandatangan") },
    ],
  });
  const agenda = useQuery({ queryKey: ["agenda-akan-datang"], queryFn: () => api("/agenda-akan-datang") });
  const [masuk, keluar, dispo, verif, ttd] = stats;
  const total = (q: any) => (q.data?.meta?.total ?? (q.isLoading ? "…" : "-"));
  const count = (q: any) => (q.data?.data?.length ?? (q.isLoading ? "…" : 0));

  const refreshing = stats.some((q) => q.isRefetching) || agenda.isRefetching;
  const refresh = () => {
    stats.forEach((q) => q.refetch());
    agenda.refetch();
  };

  const statCards: { label: string; value: any; icon: IconName; to: string; id: string }[] = [
    { id: "masuk", label: "Surat Masuk", value: total(masuk), icon: "mail-unread-outline", to: "/surat?mode=masuk" },
    { id: "keluar", label: "Surat Keluar", value: total(keluar), icon: "paper-plane-outline", to: "/surat?mode=keluar" },
    { id: "disposisi", label: "Disposisi", value: total(dispo), icon: "git-branch-outline", to: "/disposisi" },
  ];

  return (
    <View style={s.root}>
      <View style={[s.header, { paddingTop: insets.top + spacing.md }]}>
        <View style={{ flex: 1 }}>
          <Text style={s.hello}>Om Swastyastu,</Text>
          <Text style={s.name} numberOfLines={1} testID="home-user-name">{user?.nama}</Text>
          <Text style={s.role} numberOfLines={1}>{[user?.jabatan, user?.satker].filter(Boolean).join(" · ") || user?.role_label}</Text>
        </View>
        <Pressable onPress={() => router.push("/profil")} style={s.avatar} testID="home-profile-button">
          <Text style={s.avatarText}>{initials(user?.nama)}</Text>
        </Pressable>
      </View>

      <ScrollView
        contentContainerStyle={{ padding: spacing.lg, paddingBottom: bottomChrome + spacing.xl }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} tintColor={colors.brandPrimary} colors={[colors.brandPrimary]} />}
        testID="home-scroll"
      >
        <View style={s.statsRow}>
          {statCards.map((c) => (
            <Pressable key={c.id} style={({ pressed }) => [s.stat, pressed && s.pressed]} onPress={() => router.push(c.to as any)} testID={`home-stat-${c.id}`}>
              <View style={s.statIcon}>
                <Icon name={c.icon} size={18} color={colors.brandPrimary} />
              </View>
              <Text style={s.statValue} testID={`home-stat-${c.id}-value`}>{String(c.value)}</Text>
              <Text style={s.statLabel} numberOfLines={1}>{c.label}</Text>
            </Pressable>
          ))}
        </View>

        <NotifCard />

        <Section title="Perlu tindakan Anda">
          <View style={{ gap: spacing.md }}>
            {[
              { id: "verifikasi", label: "Verifikasi surat keluar", value: count(verif), icon: "shield-checkmark-outline" as IconName },
              { id: "tandatangan", label: "Tanda tangan surat", value: count(ttd), icon: "create-outline" as IconName },
            ].map((q) => (
              <Pressable key={q.id} style={({ pressed }) => [s.queue, pressed && s.pressed]} onPress={() => router.push(`/antrean?peran=${q.id}`)} testID={`home-queue-${q.id}`}>
                <View style={s.queueIcon}>
                  <Icon name={q.icon} size={20} color={colors.onBrandPrimary} />
                </View>
                <Text style={s.queueLabel}>{q.label}</Text>
                <View style={[s.count, Number(q.value) > 0 && { backgroundColor: colors.brandPrimary }]}>
                  <Text style={[s.countText, Number(q.value) > 0 && { color: colors.onBrandPrimary }]} testID={`home-queue-${q.id}-count`}>{String(q.value)}</Text>
                </View>
                <Icon name="chevron-forward" size={18} color={colors.muted} />
              </Pressable>
            ))}
          </View>
        </Section>

        <Section
          title="Agenda mendatang"
          right={
            <Pressable onPress={() => router.push("/agenda")} hitSlop={8} testID="home-agenda-see-all">
              <Text style={s.link}>Lihat semua</Text>
            </Pressable>
          }
        >
          <View style={{ gap: spacing.md }}>
            {agenda.isLoading ? (
              <View style={s.skel} />
            ) : (agenda.data as any)?.data?.length ? (
              (agenda.data as any).data.map((a: any) => <AgendaRow key={a.id} item={a} />)
            ) : (
              <EmptyState icon="calendar-clear-outline" title="Belum ada agenda" desc="Agenda dari disposisi atau undangan akan tampil di sini." testID="home-agenda-empty" />
            )}
          </View>
        </Section>
      </ScrollView>
    </View>
  );
}

const useStyles = makeStyles((c) => ({
  root: { flex: 1, backgroundColor: c.surfaceSecondary },
  header: { backgroundColor: c.surface, flexDirection: "row", alignItems: "center", gap: spacing.md, paddingHorizontal: spacing.lg, paddingBottom: spacing.lg, borderBottomWidth: 1, borderBottomColor: c.border },
  hello: { fontFamily: font.semibold, fontSize: 13, color: c.brandPrimary },
  name: { fontFamily: font.heavy, fontSize: 22, color: c.onSurface, marginTop: 2 },
  role: { fontFamily: font.regular, fontSize: 13, color: c.muted, marginTop: 2 },
  avatar: { width: 48, height: 48, borderRadius: 24, backgroundColor: c.brandPrimary, alignItems: "center", justifyContent: "center" },
  avatarText: { fontFamily: font.bold, color: c.onBrandPrimary, fontSize: 16 },
  statsRow: { flexDirection: "row", gap: spacing.md },
  stat: { flex: 1, backgroundColor: c.surface, borderRadius: radius.md, padding: spacing.md, borderWidth: 1, borderColor: c.border },
  pressed: { opacity: 0.85, transform: [{ scale: 0.98 }] },
  statIcon: { width: 32, height: 32, borderRadius: 16, backgroundColor: c.brandTertiary, alignItems: "center", justifyContent: "center" },
  statValue: { fontFamily: font.heavy, fontSize: 24, color: c.onSurface, marginTop: spacing.sm },
  statLabel: { fontFamily: font.semibold, fontSize: 12, color: c.muted },
  queue: { flexDirection: "row", alignItems: "center", gap: spacing.md, backgroundColor: c.surface, borderRadius: radius.md, padding: spacing.md, borderWidth: 1, borderColor: c.border, minHeight: 64 },
  queueIcon: { width: 40, height: 40, borderRadius: radius.md, backgroundColor: c.brandPrimary, alignItems: "center", justifyContent: "center" },
  queueLabel: { flex: 1, fontFamily: font.semibold, fontSize: 15, color: c.onSurface },
  count: { minWidth: 32, height: 28, borderRadius: 14, paddingHorizontal: 10, backgroundColor: c.surfaceTertiary, alignItems: "center", justifyContent: "center" },
  countText: { fontFamily: font.bold, fontSize: 13, color: c.onSurfaceTertiary },
  link: { fontFamily: font.semibold, fontSize: 13, color: c.brandPrimary },
  skel: { height: 88, borderRadius: radius.md, backgroundColor: c.surfaceTertiary },
}));
