import { useQueries, useQuery } from "@tanstack/react-query";
import { Image } from "expo-image";
import { router } from "expo-router";
import { Pressable, RefreshControl, ScrollView, Text, View } from "react-native";
import { useEffect } from "react";
import Animated, { Easing, FadeInDown, useAnimatedStyle, useSharedValue, withRepeat, withTiming } from "react-native-reanimated";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { api } from "@/src/api";
import { useAuth } from "@/src/auth";
import { Icon, IconName } from "@/src/components/icon";
import { NotifCard } from "@/src/components/notif-card";
import { AgendaRow } from "@/src/components/rows";
import { EmptyState, Section } from "@/src/components/ui";
import { initials } from "@/src/format";
import { usesNativeTabs } from "@/src/navigation";
import { font, makeStyles, radius, shadow, spacing, useTheme } from "@/src/theme";

export default function Beranda() {
  const { user } = useAuth();
  const insets = useSafeAreaInsets();
  const bottomChrome = usesNativeTabs ? insets.bottom : 0;
  const s = useStyles();
  const { colors } = useTheme();
  const float = useSharedValue(0);
  useEffect(() => {
    float.value = withRepeat(withTiming(1, { duration: 2800, easing: Easing.inOut(Easing.sin) }), -1, true);
  }, [float]);
  const womanStyle = useAnimatedStyle(() => ({ transform: [{ translateY: -8 * float.value }] }));

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
  const total = (q: any) => q.data?.meta?.total ?? (q.isLoading ? "…" : "-");
  const count = (q: any) => q.data?.data?.length ?? (q.isLoading ? "…" : 0);

  const refreshing = stats.some((q) => q.isRefetching) || agenda.isRefetching;
  const refresh = () => {
    stats.forEach((q) => q.refetch());
    agenda.refetch();
  };

  const heroStats: { label: string; value: any; to: string; id: string }[] = [
    { id: "masuk", label: "Surat Masuk", value: total(masuk), to: "/surat?mode=masuk" },
    { id: "keluar", label: "Surat Keluar", value: total(keluar), to: "/surat?mode=keluar" },
    { id: "disposisi", label: "Disposisi", value: total(dispo), to: "/disposisi" },
  ];
  const queues: { id: string; label: string; value: any; icon: IconName }[] = [
    { id: "verifikasi", label: "Verifikasi\nsurat keluar", value: count(verif), icon: "shield-checkmark-outline" },
    { id: "tandatangan", label: "Tanda tangan\nsurat", value: count(ttd), icon: "create-outline" },
  ];

  return (
    <View style={s.root}>
      <View style={[s.header, { paddingTop: insets.top + spacing.md }]}>
        <View style={s.brandPill}>
          <Image source={require("../../assets/images/logo.png")} style={{ width: 22, height: 22 }} contentFit="contain" />
          <Text style={s.brandText}>E-Surat Tabanan</Text>
        </View>
        <View style={{ flex: 1 }} />
        <Pressable onPress={() => router.push("/surat")} style={({ pressed }) => [s.circle, pressed && s.pressed]} testID="home-search-button">
          <Icon name="search" size={20} color={colors.onSurface} />
        </Pressable>
        <Pressable onPress={() => router.push("/profil")} style={({ pressed }) => [s.avatar, pressed && s.pressed]} testID="home-profile-button">
          <Text style={s.avatarText}>{initials(user?.nama)}</Text>
        </Pressable>
      </View>

      <ScrollView
        contentContainerStyle={{ paddingHorizontal: spacing.lg, paddingBottom: bottomChrome + spacing.xl }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} tintColor={colors.brandPrimary} colors={[colors.brandPrimary]} />}
        testID="home-scroll"
      >
        <Animated.View entering={FadeInDown.duration(400)} style={s.greet} testID="home-greeting-card">
          <View style={[s.greetOrb, { right: -30, top: -40 }]} />
          <View style={{ flex: 1, paddingRight: 120 }}>
            <Text style={s.hello}>Om Swastyastu,</Text>
            <Text style={s.name} numberOfLines={2} testID="home-user-name">{user?.nama}</Text>
            <Text style={s.role} numberOfLines={2}>{[user?.jabatan, user?.satker].filter(Boolean).join(" · ") || user?.role_label}</Text>
          </View>
          <Animated.View style={[s.woman, womanStyle]} pointerEvents="none">
            <Image source={require("../../assets/images/login/woman.webp")} style={{ width: "100%", height: "100%" }} contentFit="contain" testID="home-hero-woman" />
          </Animated.View>
        </Animated.View>

        <Animated.View entering={FadeInDown.delay(80).duration(400)} style={s.hero} testID="home-hero-card">
          <View style={s.heroTop}>
            <View style={s.heroIcon}>
              <Icon name="mail-open-outline" size={22} color={colors.brandPrimary} />
            </View>
            <Text style={s.heroTitle}>Ringkasan{"\n"}Persuratan</Text>
          </View>
          <View style={s.heroBar} />
          <View style={s.heroStats}>
            {heroStats.map((c) => (
              <Pressable key={c.id} style={({ pressed }) => [{ flex: 1 }, pressed && { opacity: 0.7 }]} onPress={() => router.push(c.to as any)} testID={`home-stat-${c.id}`}>
                <Text style={s.statValue} testID={`home-stat-${c.id}-value`}>{String(c.value)}</Text>
                <Text style={s.statLabel} numberOfLines={1}>{c.label}</Text>
              </Pressable>
            ))}
          </View>
          <Pressable onPress={() => router.push("/surat")} style={({ pressed }) => [s.heroFab, pressed && s.pressed]} testID="home-hero-open-surat">
            <Icon name="arrow-up" size={22} color={colors.onSurfaceInverse} style={{ transform: [{ rotate: "45deg" }] }} />
          </Pressable>
        </Animated.View>

        <NotifCard />

        <Section title="Perlu Tindakan">
          <View style={s.grid}>
            {queues.map((q, i) => {
              const hot = Number(q.value) > 0;
              return (
                <Animated.View key={q.id} entering={FadeInDown.delay(160 + i * 60).duration(400)} style={{ flex: 1 }}>
                  <Pressable
                    style={({ pressed }) => [s.tile, hot && s.tileHot, pressed && s.pressed]}
                    onPress={() => router.push(`/antrean?peran=${q.id}`)}
                    testID={`home-queue-${q.id}`}
                  >
                    <View style={s.tileTop}>
                      <View style={[s.tileIcon, hot && { backgroundColor: colors.surface }]}>
                        <Icon name={q.icon} size={18} color={colors.brandPrimary} />
                      </View>
                      <View style={[s.tileArrow, hot && { backgroundColor: colors.surfaceInverse }]}>
                        <Icon name="arrow-up" size={16} color={hot ? colors.onSurfaceInverse : colors.onSurface} style={{ transform: [{ rotate: "45deg" }] }} />
                      </View>
                    </View>
                    <Text style={[s.tileCount, hot && { color: colors.onBrandPrimary }]} testID={`home-queue-${q.id}-count`}>{String(q.value)}</Text>
                    <Text style={[s.tileLabel, hot && { color: colors.onBrandPrimary }]}>{q.label}</Text>
                  </Pressable>
                </Animated.View>
              );
            })}
          </View>
        </Section>

        <Section
          title="Agenda Mendatang"
          right={
            <Pressable onPress={() => router.push("/agenda")} hitSlop={8} style={s.seeAll} testID="home-agenda-see-all">
              <Text style={s.link}>Lihat semua</Text>
              <Icon name="arrow-forward" size={14} color={colors.onSurface} />
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
  header: { flexDirection: "row", alignItems: "center", gap: spacing.sm, paddingHorizontal: spacing.lg, paddingBottom: spacing.md, backgroundColor: c.surfaceSecondary },
  brandPill: { flexDirection: "row", alignItems: "center", gap: spacing.sm, backgroundColor: c.surface, borderRadius: radius.pill, paddingLeft: spacing.sm, paddingRight: spacing.lg, height: 44, ...shadow },
  brandText: { fontFamily: font.bold, fontSize: 13, color: c.onSurface },
  circle: { width: 44, height: 44, borderRadius: 22, backgroundColor: c.surface, alignItems: "center", justifyContent: "center", ...shadow },
  avatar: { width: 44, height: 44, borderRadius: 22, backgroundColor: c.brandPrimary, alignItems: "center", justifyContent: "center" },
  avatarText: { fontFamily: font.bold, color: c.onBrandPrimary, fontSize: 15 },
  pressed: { opacity: 0.88, transform: [{ scale: 0.97 }] },
  greet: { flexDirection: "row", alignItems: "center", backgroundColor: c.brandTertiary, borderRadius: radius.lg, padding: spacing.xl, marginTop: spacing.sm, minHeight: 150, overflow: "hidden" },
  greetOrb: { position: "absolute", width: 180, height: 180, borderRadius: 90, backgroundColor: "rgba(230,57,70,0.12)" },
  woman: { position: "absolute", right: -6, bottom: -4, width: 150, height: 150 },
  hello: { fontFamily: font.semibold, fontSize: 14, color: c.brandPrimary },
  name: { fontFamily: font.heavy, fontSize: 24, lineHeight: 30, color: c.onSurface, marginTop: 2 },
  role: { fontFamily: font.regular, fontSize: 13, color: c.muted, marginTop: spacing.xs },
  hero: { backgroundColor: c.brandPrimary, borderRadius: radius.lg, padding: spacing.xl, marginTop: spacing.xl, overflow: "hidden" },
  heroTop: { flexDirection: "row", alignItems: "center", gap: spacing.md },
  heroIcon: { width: 48, height: 48, borderRadius: 24, backgroundColor: c.surface, alignItems: "center", justifyContent: "center" },
  heroTitle: { fontFamily: font.bold, fontSize: 17, lineHeight: 22, color: c.onBrandPrimary },
  heroBar: { height: 4, borderRadius: 2, backgroundColor: "rgba(255,255,255,0.28)", marginVertical: spacing.xl, width: "62%" },
  heroStats: { flexDirection: "row", gap: spacing.md, paddingRight: 64 },
  statValue: { fontFamily: font.heavy, fontSize: 26, color: c.onBrandPrimary },
  statLabel: { fontFamily: font.semibold, fontSize: 11, color: c.onBrandPrimary, opacity: 0.85 },
  heroFab: { position: "absolute", right: spacing.lg, bottom: spacing.lg, width: 56, height: 56, borderRadius: 28, backgroundColor: c.surfaceInverse, alignItems: "center", justifyContent: "center" },
  grid: { flexDirection: "row", gap: spacing.md },
  tile: { backgroundColor: c.surface, borderRadius: radius.lg, padding: spacing.lg, minHeight: 168, ...shadow },
  tileHot: { backgroundColor: c.brandPrimary },
  tileTop: { flexDirection: "row", justifyContent: "space-between", alignItems: "center" },
  tileIcon: { width: 40, height: 40, borderRadius: 20, backgroundColor: c.brandTertiary, alignItems: "center", justifyContent: "center" },
  tileArrow: { width: 40, height: 40, borderRadius: 20, backgroundColor: c.surfaceSecondary, alignItems: "center", justifyContent: "center" },
  tileCount: { fontFamily: font.heavy, fontSize: 34, color: c.onSurface, marginTop: spacing.lg },
  tileLabel: { fontFamily: font.semibold, fontSize: 13, lineHeight: 18, color: c.onSurfaceTertiary },
  seeAll: { flexDirection: "row", alignItems: "center", gap: 4, minHeight: 32 },
  link: { fontFamily: font.semibold, fontSize: 13, color: c.onSurface },
  skel: { height: 96, borderRadius: radius.lg, backgroundColor: c.surfaceTertiary },
}));
