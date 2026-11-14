import { useQuery } from "@tanstack/react-query";
import * as WebBrowser from "expo-web-browser";
import { router, useLocalSearchParams } from "expo-router";
import { ScrollView, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { api } from "@/src/api";
import { Card, useDetailStyles } from "@/src/components/detail";
import { Button, DetailHeader, ErrorState, InfoRow, Section, SkeletonList } from "@/src/components/ui";
import { fmtDateTime } from "@/src/format";
import { spacing, useTheme } from "@/src/theme";

const METODE: Record<string, string> = { offline: "Tatap muka", online: "Daring", hybrid: "Hybrid" };

export default function AgendaDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const insets = useSafeAreaInsets();
  const s = useDetailStyles();
  const { colors } = useTheme();
  const q = useQuery({ queryKey: ["agenda-detail", id], queryFn: () => api<any>(`/agenda/${id}`) });
  const d = q.data?.data;

  return (
    <View style={{ flex: 1, backgroundColor: colors.surfaceSecondary }} testID="agenda-detail-screen">
      <DetailHeader title="Agenda" />
      {q.isLoading ? (
        <SkeletonList count={3} />
      ) : q.isError ? (
        <ErrorState message={(q.error as Error).message} onRetry={() => q.refetch()} />
      ) : (
        <ScrollView contentContainerStyle={{ padding: spacing.lg, paddingBottom: insets.bottom + spacing.xl }}>
          <Text style={s.h1} testID="agenda-judul">{d.judul}</Text>
          <Section title="Waktu & tempat">
            <Card>
              <InfoRow label="Mulai" value={fmtDateTime(d.mulai)} testID="agenda-mulai" />
              <InfoRow label="Selesai" value={d.selesai ? fmtDateTime(d.selesai) : "-"} />
              <InfoRow label="Metode" value={METODE[d.metode] ?? d.metode} />
              <InfoRow label="Tempat" value={d.tempat} />
            </Card>
          </Section>
          {d.link_meeting ? (
            <Button label="Buka tautan rapat" icon="videocam-outline" style={{ marginTop: spacing.lg }} onPress={() => WebBrowser.openBrowserAsync(d.link_meeting)} testID="agenda-open-link-button" />
          ) : null}
          {d.deskripsi ? (
            <Section title="Deskripsi">
              <Card>
                <Text style={[s.body, { paddingVertical: spacing.md }]}>{d.deskripsi}</Text>
              </Card>
            </Section>
          ) : null}
          {d.surat_masuk_id ? (
            <Section title="Surat asal">
              <Button label={d.perihal_surat ?? "Lihat surat"} variant="secondary" icon="mail-open-outline" onPress={() => router.push(`/surat-masuk/${d.surat_masuk_id}`)} testID="agenda-open-surat-button" />
            </Section>
          ) : null}
        </ScrollView>
      )}
    </View>
  );
}
