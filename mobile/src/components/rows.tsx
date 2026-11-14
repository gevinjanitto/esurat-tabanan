import { router } from "expo-router";
import { Pressable, Text, View } from "react-native";

import { dayNum, fmtDate, fmtTime, monthShort } from "@/src/format";
import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";
import { Icon } from "./icon";
import { Badge } from "./ui";

export function SuratMasukRow({ item }: { item: any }) {
  const s = useStyles();
  return (
    <Pressable style={({ pressed }) => [s.card, pressed && s.pressed]} onPress={() => router.push(`/surat-masuk/${item.id}`)} testID={`surat-masuk-row-${item.id}`}>
      <View style={s.top}>
        <Text style={s.meta} numberOfLines={1}>{item.pengirim ?? "-"}</Text>
        <Text style={s.date}>{fmtDate(item.tanggal_terima ?? item.tanggal_surat)}</Text>
      </View>
      <Text style={s.title} numberOfLines={2}>{item.perihal}</Text>
      <View style={s.bottom}>
        <Text style={s.sub} numberOfLines={1}>{item.nomor_surat ?? "Tanpa nomor"}</Text>
        <Badge label={item.status_label} />
      </View>
    </Pressable>
  );
}

export function SuratKeluarRow({ item }: { item: any }) {
  const s = useStyles();
  return (
    <Pressable style={({ pressed }) => [s.card, pressed && s.pressed]} onPress={() => router.push(`/surat-keluar/${item.id}`)} testID={`surat-keluar-row-${item.id}`}>
      <View style={s.top}>
        <Text style={s.meta} numberOfLines={1}>{item.tujuan ? `Kepada: ${item.tujuan}` : item.pembuat ?? "-"}</Text>
        <Text style={s.date}>{fmtDate(item.tanggal_surat ?? item.updated_at)}</Text>
      </View>
      <Text style={s.title} numberOfLines={2}>{item.perihal}</Text>
      <View style={s.bottom}>
        <Text style={s.sub} numberOfLines={1}>{item.nomor_surat ?? "Nomor belum terbit"}{item.revisi ? ` · Rev ${item.revisi}` : ""}</Text>
        <Badge label={item.status_label} />
      </View>
    </Pressable>
  );
}

export function DisposisiRow({ item, mode }: { item: any; mode: "masuk" | "keluar" }) {
  const s = useStyles();
  const { colors } = useTheme();
  return (
    <Pressable style={({ pressed }) => [s.card, pressed && s.pressed]} onPress={() => router.push(`/disposisi/${item.id}`)} testID={`disposisi-row-${item.id}`}>
      <View style={s.top}>
        <Text style={s.meta} numberOfLines={1}>{mode === "masuk" ? `Dari: ${item.dari ?? "-"}` : `Kepada: ${item.ke ?? "-"}`}</Text>
        <Text style={s.date}>{fmtDate(item.created_at)}</Text>
      </View>
      <Text style={s.title} numberOfLines={2}>{item.perihal ?? "-"}</Text>
      {item.instruksi ? (
        <View style={s.instr}>
          <Icon name="chatbox-ellipses-outline" size={14} color={colors.brandPrimary} />
          <Text style={s.instrText} numberOfLines={2}>{item.instruksi}</Text>
        </View>
      ) : null}
      <View style={s.bottom}>
        <Text style={s.sub} numberOfLines={1}>{item.batas_waktu ? `Batas: ${fmtDate(item.batas_waktu)}` : item.nomor_surat ?? ""}</Text>
        <Badge label={item.status_label} />
      </View>
    </Pressable>
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
  card: { backgroundColor: c.surface, borderRadius: radius.md, padding: spacing.lg, borderWidth: 1, borderColor: c.border, minHeight: 56 },
  pressed: { backgroundColor: c.surfaceSecondary, transform: [{ scale: 0.99 }] },
  top: { flexDirection: "row", justifyContent: "space-between", gap: spacing.sm },
  meta: { flex: 1, fontFamily: font.semibold, fontSize: 12, color: c.brandPrimary },
  date: { fontFamily: font.regular, fontSize: 12, color: c.muted },
  title: { fontFamily: font.bold, fontSize: 15, color: c.onSurface, marginTop: spacing.xs, lineHeight: 21 },
  bottom: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", gap: spacing.sm, marginTop: spacing.md },
  sub: { flex: 1, fontFamily: font.regular, fontSize: 12, color: c.muted },
  instr: { flexDirection: "row", gap: 6, alignItems: "center", marginTop: spacing.sm },
  instrText: { flex: 1, fontFamily: font.regular, fontSize: 13, color: c.onSurfaceTertiary },
  agenda: { flexDirection: "row", gap: spacing.lg, alignItems: "center" },
  dateBlock: { width: 56, height: 64, borderRadius: radius.md, backgroundColor: c.brandTertiary, alignItems: "center", justifyContent: "center" },
  dayNum: { fontFamily: font.heavy, fontSize: 22, color: c.brandPrimary },
  month: { fontFamily: font.semibold, fontSize: 12, color: c.brandPrimary, textTransform: "uppercase" },
}));
