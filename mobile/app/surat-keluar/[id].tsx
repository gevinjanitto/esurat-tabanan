import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useLocalSearchParams } from "expo-router";
import { useState } from "react";
import { RefreshControl, ScrollView, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { api } from "@/src/api";
import { ActionSheet, SheetConfig } from "@/src/components/action-sheet";
import { Card, PdfCard, StickyBar, useDetailStyles } from "@/src/components/detail";
import { Icon } from "@/src/components/icon";
import { useToast } from "@/src/components/toast";
import { Badge, Button, DetailHeader, ErrorState, InfoRow, Section, SkeletonList } from "@/src/components/ui";
import { fmtDate, fmtDateTime } from "@/src/format";
import { spacing, useTheme } from "@/src/theme";

const PERAN: Record<string, string> = { verifikator: "Verifikator", penandatangan: "Penandatangan" };

export default function SuratKeluarDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const insets = useSafeAreaInsets();
  const s = useDetailStyles();
  const { colors } = useTheme();
  const toast = useToast();
  const qc = useQueryClient();
  const [sheet, setSheet] = useState<SheetConfig | null>(null);
  const q = useQuery({ queryKey: ["surat-keluar-detail", id], queryFn: () => api<any>(`/surat/keluar/${id}`) });
  const d = q.data?.data;

  const act = (path: string, body: any) => async () => {
    const r = await api<any>(path, { method: "POST", body: { id: Number(id), ...body } });
    toast(r?.message || "Berhasil disimpan");
    qc.invalidateQueries();
  };

  const openSetuju = () =>
    setSheet({
      title: "Setujui surat",
      description: "Surat akan diteruskan ke tahap berikutnya.",
      inputLabel: "Catatan (opsional)",
      placeholder: "Tulis catatan untuk pembuat surat",
      confirmLabel: "Setujui",
      onConfirm: (t) => act("/surat/keluar/setuju", { catatan: t || undefined })(),
    });
  const openRevisi = () =>
    setSheet({
      title: "Kembalikan untuk revisi",
      description: "Surat dikembalikan ke pembuat untuk diperbaiki.",
      inputLabel: "Catatan revisi",
      placeholder: "Jelaskan bagian yang perlu diperbaiki",
      required: true,
      danger: true,
      confirmLabel: "Kirim Revisi",
      onConfirm: (t) => act("/surat/keluar/revisi", { catatan: t })(),
    });
  const openTtd = () =>
    setSheet({
      title: "Tanda tangan elektronik",
      description: "Masukkan passphrase TTE (sama dengan password akun Anda).",
      inputLabel: "Passphrase",
      placeholder: "••••••••",
      secure: true,
      required: true,
      confirmLabel: "Tanda Tangan",
      onConfirm: (t) => act("/surat/keluar/simpan-tandatangan", { passphrase: t })(),
    });

  return (
    <View style={{ flex: 1, backgroundColor: colors.surfaceSecondary }} testID="surat-keluar-detail-screen">
      <DetailHeader title="Surat Keluar" />
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
            <Badge label={d.status_label} testID="surat-keluar-status" />
            <Text style={s.h1} testID="surat-keluar-perihal">{d.perihal}</Text>
            {d.giliran_saya ? (
              <View style={{ flexDirection: "row", gap: 6, alignItems: "center", marginTop: spacing.sm }} testID="surat-keluar-my-turn">
                <Icon name="hand-left-outline" size={16} color={colors.warning} />
                <Text style={[s.muted, { color: colors.onBrandSecondary }]}>Giliran Anda sebagai {PERAN[d.giliran_saya] ?? d.giliran_saya}</Text>
              </View>
            ) : null}
            <PdfCard url={d.file_url} />
            <Section title="Informasi surat">
              <Card>
                <InfoRow label="Nomor surat" value={d.nomor_surat ?? "Belum terbit"} testID="surat-keluar-nomor" />
                <InfoRow label="Tanggal surat" value={fmtDate(d.tanggal_surat)} />
                <InfoRow label="Tujuan" value={d.tujuan} />
                <InfoRow label="Pembuat" value={d.pembuat} />
                <InfoRow label="Sifat" value={d.sifat} />
                <InfoRow label="Revisi ke" value={d.revisi ?? 0} />
              </Card>
            </Section>
            {d.ringkasan ? (
              <Section title="Ringkasan">
                <Card>
                  <Text style={[s.body, { paddingVertical: spacing.md }]}>{d.ringkasan}</Text>
                </Card>
              </Section>
            ) : null}
            <Section title="Alur verifikasi & tanda tangan">
              <Card testID="surat-keluar-alur">
                <View style={{ paddingVertical: spacing.md }}>
                  {d.alur?.length ? (
                    d.alur.map((a: any, i: number) => {
                      const done = Number(a.status) === 1;
                      const rejected = Number(a.status) === 2;
                      const bg = done ? colors.success : rejected ? colors.error : colors.surfaceTertiary;
                      return (
                        <View key={i} style={s.step}>
                          <View style={s.stepRail}>
                            <View style={[s.stepDot, { backgroundColor: bg }]}>
                              <Icon name={done ? "checkmark" : rejected ? "close" : "ellipsis-horizontal"} size={14} color={done || rejected ? colors.onSuccess : colors.muted} />
                            </View>
                            {i < d.alur.length - 1 ? <View style={s.stepLine} /> : null}
                          </View>
                          <View style={{ flex: 1, paddingBottom: spacing.lg }}>
                            <Text style={s.stepName}>{a.nama ?? "-"}</Text>
                            <Text style={s.stepMeta}>{PERAN[a.peran] ?? a.peran} · urutan {a.urut}{a.acted_at ? ` · ${fmtDateTime(a.acted_at)}` : ""}</Text>
                            {a.catatan ? <Text style={s.note}>“{a.catatan}”</Text> : null}
                          </View>
                        </View>
                      );
                    })
                  ) : (
                    <Text style={s.muted}>Alur belum ditentukan.</Text>
                  )}
                </View>
              </Card>
            </Section>
            {d.riwayat?.length ? (
              <Section title="Riwayat">
                <Card>
                  {d.riwayat.map((r: any, i: number) => (
                    <View key={i} style={s.linkRow}>
                      <Icon name="time-outline" size={16} color={colors.muted} />
                      <View style={{ flex: 1 }}>
                        <Text style={s.stepName}>{r.aksi}</Text>
                        <Text style={s.stepMeta}>{r.oleh ?? "-"} · {fmtDateTime(r.waktu)}</Text>
                        {r.catatan ? <Text style={s.note}>{r.catatan}</Text> : null}
                      </View>
                    </View>
                  ))}
                </Card>
              </Section>
            ) : null}
          </ScrollView>
          {d.giliran_saya ? (
            <StickyBar bottom={insets.bottom}>
              <Button label="Revisi" variant="secondary" icon="return-up-back" style={{ flex: 1 }} onPress={openRevisi} testID="surat-keluar-revisi-button" />
              {d.giliran_saya === "penandatangan" ? (
                <Button label="Tanda Tangan" icon="create-outline" style={{ flex: 1.4 }} onPress={openTtd} testID="surat-keluar-ttd-button" />
              ) : (
                <Button label="Setujui" icon="checkmark-circle-outline" style={{ flex: 1.4 }} onPress={openSetuju} testID="surat-keluar-setuju-button" />
              )}
            </StickyBar>
          ) : (
            <View style={{ height: insets.bottom }} />
          )}
        </>
      )}
      <ActionSheet config={sheet} onClose={() => setSheet(null)} />
    </View>
  );
}
