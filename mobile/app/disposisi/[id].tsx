import { useQuery } from "@tanstack/react-query";
import { router, useLocalSearchParams } from "expo-router";
import { RefreshControl, ScrollView, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { api } from "@/src/api";
import { useAuth } from "@/src/auth";
import { Card, StickyBar, useDetailStyles } from "@/src/components/detail";
import { Badge, Button, DetailHeader, ErrorState, InfoRow, Section, SkeletonList } from "@/src/components/ui";
import { fmtDate, fmtDateTime, initials } from "@/src/format";
import { font, radius, spacing, useTheme } from "@/src/theme";

export default function DisposisiDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { user } = useAuth();
  const insets = useSafeAreaInsets();
  const s = useDetailStyles();
  const { colors } = useTheme();
  const q = useQuery({ queryKey: ["disposisi-detail", id], queryFn: () => api<any>(`/disposisi/${id}`) });
  const d = q.data?.data;

  return (
    <View style={{ flex: 1, backgroundColor: colors.surfaceSecondary }} testID="disposisi-detail-screen">
      <DetailHeader title="Disposisi" />
      {q.isLoading ? (
        <SkeletonList count={4} />
      ) : q.isError ? (
        <ErrorState message={(q.error as Error).message} onRetry={() => q.refetch()} />
      ) : (
        <>
          <ScrollView
            contentContainerStyle={{ padding: spacing.lg, paddingBottom: spacing.xl }}
            refreshControl={<RefreshControl refreshing={q.isRefetching} onRefresh={() => q.refetch()} tintColor={colors.brandPrimary} />}
          >
            <Badge label={d.status_label} testID="disposisi-status" />
            <Text style={s.h1} testID="disposisi-perihal">{d.perihal ?? "-"}</Text>
            <View style={{ backgroundColor: colors.brandTertiary, borderRadius: radius.md, padding: spacing.lg, marginTop: spacing.lg }}>
              <Text style={{ fontFamily: font.semibold, fontSize: 12, color: colors.onBrandTertiary }}>Instruksi</Text>
              <Text style={{ fontFamily: font.bold, fontSize: 16, color: colors.onSurface, marginTop: 4 }} testID="disposisi-instruksi">{d.instruksi}</Text>
              {d.catatan ? <Text style={[s.body, { marginTop: spacing.sm }]}>{d.catatan}</Text> : null}
            </View>
            <Section title="Detail">
              <Card>
                <InfoRow label="Dari" value={d.dari} />
                <InfoRow label="Kepada" value={d.ke} />
                <InfoRow label="Nomor surat" value={d.nomor_surat} />
                <InfoRow label="Batas waktu" value={d.batas_waktu ? fmtDate(d.batas_waktu) : "-"} />
                <InfoRow label="Tanggal disposisi" value={fmtDateTime(d.created_at)} />
                {d.tindak_lanjut ? <InfoRow label="Tindak lanjut" value={d.tindak_lanjut} /> : null}
              </Card>
            </Section>
            <Section title={`Tanggapan (${d.tanggapan?.length ?? 0})`}>
              <View style={{ gap: spacing.md }} testID="disposisi-tanggapan-list">
                {d.tanggapan?.length ? (
                  d.tanggapan.map((t: any, i: number) => (
                    <View key={i} style={{ flexDirection: "row", gap: spacing.md }}>
                      <View style={{ width: 36, height: 36, borderRadius: 18, backgroundColor: colors.brandPrimary, alignItems: "center", justifyContent: "center" }}>
                        <Text style={{ fontFamily: font.bold, color: colors.onBrandPrimary, fontSize: 12 }}>{initials(t.oleh)}</Text>
                      </View>
                      <View style={{ flex: 1, backgroundColor: colors.surface, borderRadius: radius.md, borderTopLeftRadius: 4, padding: spacing.md, borderWidth: 1, borderColor: colors.border }}>
                        <Text style={s.stepName}>{t.oleh}</Text>
                        <Text style={[s.body, { marginTop: 2 }]}>{t.isi}</Text>
                        <Text style={[s.stepMeta, { marginTop: spacing.xs }]}>{fmtDateTime(t.waktu)}</Text>
                      </View>
                    </View>
                  ))
                ) : (
                  <Text style={s.muted}>Belum ada tanggapan.</Text>
                )}
              </View>
            </Section>
          </ScrollView>
          <StickyBar bottom={insets.bottom}>
            <Button label="Lihat Surat" variant="secondary" icon="mail-open-outline" style={{ flex: 1 }} onPress={() => router.push(`/surat-masuk/${d.surat_masuk_id}`)} testID="disposisi-open-surat-button" />
            {user?.role !== "admin" ? (
              <Button
                label="Disposisikan"
                icon="arrow-redo-outline"
                style={{ flex: 1 }}
                testID="disposisi-forward-button"
                onPress={() => router.push({ pathname: "/disposisi-baru", params: { surat_masuk_id: String(d.surat_masuk_id), perihal: d.perihal ?? "" } })}
              />
            ) : null}
          </StickyBar>
        </>
      )}
    </View>
  );
}
