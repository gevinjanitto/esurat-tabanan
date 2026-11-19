import { router } from "expo-router";
import { Pressable, Text, View } from "react-native";

import { dayNum, fmtDate, fmtTime, monthShort } from "@/src/format";
import { font, makeStyles, radius, shadow, spacing, useTheme } from "@/src/theme";
import { Icon, IconName } from "./icon";
import { Badge } from "./ui";

function RowShell({ icon, to, testID, children }: { icon: IconName; to: string; testID: string; children: React.ReactNode }) {
  const s = useStyles();
  const { colors } = useTheme();
  return (
    <Pressable style={({ pressed }) => [s.card, pressed && s.pressed]} onPress={() => router.push(to as any)} testID={testID}>
      <View style={s.head}>
        <View style={s.iconBox}>
          <Icon name={icon} size={20} color={colors.brandPrimary} />
        </View>
        <View style={{ flex: 1 }}>{children}</View>
        <View style={s.arrow}>
          <Icon name="arrow-up-outline" size={16} color={colors.onSurface} style={{ transform: [{ rotate: "45deg" }] }} />
        </View>
      </View>
    </Pressable>
  );
}

export function SuratMasukRow({ item }: { item: any }) {
  const s = useStyles();
  return (
    <RowShell icon="mail-unread-outline" to={`/surat-masuk/${item.id}`} testID={`surat-masuk-row-${item.id}`}>
      <Text style={s.meta} numberOfLines={1}>{item.pengirim ?? "-"}</Text>
      <Text style={s.title} numberOfLines={2}>{item.perihal}</Text>
      <View style={s.bottom}>
        <Badge label={item.status_label} />
        <Text style={s.date}>{fmtDate(item.tanggal_terima ?? item.tanggal_surat)}</Text>
      </View>
    </RowShell>
  );
}

export function SuratKeluarRow({ item }: { item: any }) {
  const s = useStyles();
  return (
    <RowShell icon="paper-plane-outline" to={`/surat-keluar/${item.id}`} testID={`surat-keluar-row-${item.id}`}>
      <Text style={s.meta} numberOfLines={1}>{item.tujuan ? `Kepada: ${item.tujuan}` : item.pembuat ?? "-"}</Text>
      <Text style={s.title} numberOfLines={2}>{item.perihal}</Text>
      <Text style={s.sub} numberOfLines={1}>{item.nomor_surat ?? "Nomor belum terbit"}{item.revisi ? ` · Rev ${item.revisi}` : ""}</Text>
      <View style={s.bottom}>
        <Badge label={item.status_label} />
        <Text style={s.date}>{fmtDate(item.tanggal_surat ?? item.updated_at)}</Text>
      </View>
    </RowShell>
  );
}

export function DisposisiRow({ item, mode }: { item: any; mode: "masuk" | "keluar" }) {
  const s = useStyles();
  return (
    <RowShell icon="git-branch-outline" to={`/disposisi/${item.id}`} testID={`disposisi-row-${item.id}`}>
      <Text style={s.meta} numberOfLines={1}>{mode === "masuk" ? `Dari: ${item.dari ?? "-"}` : `Kepada: ${item.ke ?? "-"}`}</Text>
      <Text style={s.title} numberOfLines={2}>{item.perihal ?? "-"}</Text>
      {item.instruksi ? <Text style={s.instrText} numberOfLines={2}>“{item.instruksi}”</Text> : null}
      <View style={s.bottom}>
        <Badge label={item.status_label} />
        <Text style={s.date}>{item.batas_waktu ? `Batas ${fmtDate(item.batas_waktu)}` : fmtDate(item.created_at)}</Text>
      </View>
    </RowShell>
  );
}

export function AgendaRow({ item }: { item: any }) {
  const s = useStyles();
  const { colors } = useTheme();
  return (
    <Pressable style={({ pressed }) => [s.card, s.agenda, pressed && s.pressed]} onPress={() => router.push(`/agenda/${item.id}`)} testID={`agenda-row-${item.id}`}>
      <View style={s.dateBlock}>
        <Text style={s.dayNum}>{dayNum(item.mulai)}</Text>
        <Text style={s.month}>{monthShort(item.mulai)}</Text>
      </View>
      <View style={{ flex: 1 }}>
        <Text style={s.title} numberOfLines={2}>{item.judul}</Text>
        <View style={s.instr}>
          <Icon name="time-outline" size={14} color={colors.muted} />
          <Text style={s.sub}>{fmtTime(item.mulai)}{item.selesai ? ` – ${fmtTime(item.selesai)}` : ""}</Text>
        </View>
        {item.tempat || item.link_meeting ? (
          <View style={s.instr}>
            <Icon name={item.tempat ? "location-outline" : "videocam-outline"} size={14} color={colors.muted} />
            <Text style={s.sub} numberOfLines={1}>{item.tempat ?? "Rapat daring"}</Text>
          </View>
        ) : null}
      </View>
    </Pressable>
  );
}

const useStyles = makeStyles((c) => ({
  card: { backgroundColor: c.surface, borderRadius: radius.lg, padding: spacing.lg, minHeight: 56, ...shadow },
  pressed: { transform: [{ scale: 0.98 }], opacity: 0.92 },
  head: { flexDirection: "row", gap: spacing.md, alignItems: "flex-start" },
  iconBox: { width: 44, height: 44, borderRadius: radius.md, backgroundColor: c.brandTertiary, alignItems: "center", justifyContent: "center" },
  arrow: { width: 36, height: 36, borderRadius: 18, backgroundColor: c.surfaceSecondary, alignItems: "center", justifyContent: "center" },
  meta: { fontFamily: font.semibold, fontSize: 12, color: c.brandPrimary },
  date: { fontFamily: font.regular, fontSize: 12, color: c.muted },
  title: { fontFamily: font.bold, fontSize: 15, color: c.onSurface, marginTop: 2, lineHeight: 21 },
  bottom: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", gap: spacing.sm, marginTop: spacing.md },
  sub: { flex: 1, fontFamily: font.regular, fontSize: 12, color: c.muted, marginTop: 2 },
  instr: { flexDirection: "row", gap: 6, alignItems: "center", marginTop: spacing.sm },
  instrText: { fontFamily: font.regular, fontSize: 13, color: c.onSurfaceTertiary, marginTop: spacing.xs, fontStyle: "italic" },
  agenda: { flexDirection: "row", gap: spacing.lg, alignItems: "center" },
  dateBlock: { width: 60, height: 68, borderRadius: radius.md + 4, backgroundColor: c.brandPrimary, alignItems: "center", justifyContent: "center" },
  dayNum: { fontFamily: font.heavy, fontSize: 24, color: c.onBrandPrimary },
  month: { fontFamily: font.semibold, fontSize: 12, color: c.onBrandPrimary, textTransform: "uppercase" },
}));
