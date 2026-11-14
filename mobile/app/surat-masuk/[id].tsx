import { useQuery } from "@tanstack/react-query";
import { router, useLocalSearchParams } from "expo-router";
import { Pressable, RefreshControl, ScrollView, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { api } from "@/src/api";
import { useAuth } from "@/src/auth";
import { Card, PdfCard, StickyBar, useDetailStyles } from "@/src/components/detail";
import { Icon } from "@/src/components/icon";
import { Badge, Button, DetailHeader, ErrorState, InfoRow, Section, SkeletonList } from "@/src/components/ui";
import { fmtDate, fmtDateTime } from "@/src/format";
import { spacing, useTheme } from "@/src/theme";

export default function SuratMasukDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { user } = useAuth();
  const insets = useSafeAreaInsets();
  const s = useDetailStyles();
  const { colors } = useTheme();
  const q = useQuery({ queryKey: ["surat-masuk-detail", id], queryFn: () => api<any>(`/surat/masuk/${id}`) });
  const d = q.data?.data;
  const canDispo = user?.role !== "admin";

  return (
    <View style={{ flex: 1, backgroundColor: colors.surfaceSecondary }} testID="surat-masuk-detail-screen">
      <DetailHeader title="Surat Masuk" />
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
            <Badge label={d.status_label} testID="surat-masuk-status" />
            <Text style={s.h1} testID="surat-masuk-perihal">{d.perihal}</Text>
            <PdfCard url={d.file_url} />
            <Section title="Informasi surat">
              <Card>
                <InfoRow label="Nomor surat" value={d.nomor_surat} testID="surat-masuk-nomor" />
                <InfoRow label="Nomor agenda" value={d.nomor_agenda} />
                <InfoRow label="Pengirim" value={d.pengirim} />
                <InfoRow label="Tanggal surat" value={fmtDate(d.tanggal_surat)} />
                <InfoRow label="Tanggal terima" value={fmtDate(d.tanggal_terima)} />
                <InfoRow label="Sifat" value={d.sifat} />
              </Card>
            </Section>
            {d.ringkasan ? (
              <Section title="Ringkasan">
                <Card>
                  <Text style={[s.body, { paddingVertical: spacing.md }]}>{d.ringkasan}</Text>
                </Card>
              </Section>
            ) : null}
            <Section title={`Disposisi (${d.disposisi?.length ?? 0})`}>
              <Card testID="surat-masuk-disposisi-list">
                {d.disposisi?.length ? (
                  d.disposisi.map((x: any) => (
                    <Pressable key={x.id} style={s.linkRow} onPress={() => router.push(`/disposisi/${x.id}`)} testID={`surat-masuk-disposisi-${x.id}`}>
                      <Icon name="arrow-redo-outline" size={18} color={colors.brandPrimary} />
                      <View style={{ flex: 1 }}>
                        <Text style={s.stepName} numberOfLines={1}>{x.dari} → {x.ke}</Text>
                        <Text style={s.stepMeta} numberOfLines={2}>{x.instruksi}</Text>
                      </View>
                      <Badge label={x.status_label} />
                    </Pressable>
                  ))
                ) : (
                  <Text style={[s.muted, { paddingVertical: spacing.lg }]}>Belum ada disposisi.</Text>
                )}
              </Card>
            </Section>
            {d.agenda?.length ? (
              <Section title="Agenda terkait">
                <Card>
                  {d.agenda.map((a: any) => (
                    <Pressable key={a.id} style={s.linkRow} onPress={() => router.push(`/agenda/${a.id}`)} testID={`surat-masuk-agenda-${a.id}`}>
                      <Icon name="calendar-outline" size={18} color={colors.brandPrimary} />
                      <View style={{ flex: 1 }}>
                        <Text style={s.stepName}>{a.judul}</Text>
                        <Text style={s.stepMeta}>{fmtDateTime(a.mulai)}{a.tempat ? ` · ${a.tempat}` : ""}</Text>
                      </View>
                    </Pressable>
                  ))}
                </Card>
              </Section>
            ) : null}
          </ScrollView>
          {canDispo ? (
            <StickyBar bottom={insets.bottom}>
              <Button
                label="Buat Disposisi"
                icon="git-branch-outline"
                style={{ flex: 1 }}
                testID="surat-masuk-create-disposisi-button"
                onPress={() => router.push({ pathname: "/disposisi-baru", params: { surat_masuk_id: String(d.id), perihal: d.perihal } })}
              />
            </StickyBar>
          ) : (
            <View style={{ height: insets.bottom }} />
          )}
        </>
      )}
    </View>
  );
}
