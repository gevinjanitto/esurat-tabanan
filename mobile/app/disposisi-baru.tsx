import { useQuery, useQueryClient } from "@tanstack/react-query";
import { router, useLocalSearchParams } from "expo-router";
import { useMemo, useState } from "react";
import { Pressable, Text, TextInput, View } from "react-native";
import { KeyboardAwareScrollView, KeyboardStickyView } from "react-native-keyboard-controller";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { api } from "@/src/api";
import { StickyBar } from "@/src/components/detail";
import { Icon } from "@/src/components/icon";
import { useToast } from "@/src/components/toast";
import { Button, DetailHeader, ErrorState, SkeletonList } from "@/src/components/ui";
import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";

export default function DisposisiBaru() {
  const { surat_masuk_id, perihal } = useLocalSearchParams<{ surat_masuk_id: string; perihal?: string }>();
  const insets = useSafeAreaInsets();
  const s = useStyles();
  const { colors } = useTheme();
  const toast = useToast();
  const qc = useQueryClient();
  const [selected, setSelected] = useState<number[]>([]);
  const [search, setSearch] = useState("");
  const [instruksi, setInstruksi] = useState("");
  const [catatan, setCatatan] = useState("");
  const [batas, setBatas] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const q = useQuery({ queryKey: ["disposisi-tujuan"], queryFn: () => api<any>("/disposisi/tujuan") });

  const users: any[] = useMemo(() => {
    const all: any[] = q.data?.data ?? [];
    const t = search.trim().toLowerCase();
    return t ? all.filter((u) => [u.nama, u.jabatan, u.satker].filter(Boolean).join(" ").toLowerCase().includes(t)) : all;
  }, [q.data, search]);

  const toggle = (id: number) => setSelected((v) => (v.includes(id) ? v.filter((x) => x !== id) : [...v, id]));

  const submit = async () => {
    if (!selected.length) return setError("Pilih minimal satu penerima disposisi.");
    if (!instruksi.trim()) return setError("Instruksi disposisi wajib diisi.");
    if (batas && !/^\d{4}-\d{2}-\d{2}$/.test(batas)) return setError("Format batas waktu: TTTT-BB-HH, contoh 2026-07-01.");
    setError(null);
    setLoading(true);
    try {
      const r = await api<any>("/disposisi/add", {
        method: "POST",
        body: { surat_masuk_id: Number(surat_masuk_id), ke_user_id: selected, instruksi: instruksi.trim(), catatan: catatan.trim() || undefined, batas_waktu: batas || undefined },
      });
      toast(r?.message || "Disposisi terkirim");
      qc.invalidateQueries();
      router.back();
    } catch (e: any) {
      setError(e?.message ?? "Gagal mengirim disposisi.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={{ flex: 1, backgroundColor: colors.surfaceSecondary }} testID="disposisi-baru-screen">
      <DetailHeader title="Buat Disposisi" />
      {q.isLoading ? (
        <SkeletonList />
      ) : q.isError ? (
        <ErrorState message={(q.error as Error).message} onRetry={() => q.refetch()} />
      ) : (
        <>
          <KeyboardAwareScrollView bottomOffset={96} contentContainerStyle={{ padding: spacing.lg, paddingBottom: spacing.xl }} keyboardShouldPersistTaps="handled">
            {perihal ? <Text style={s.perihal} numberOfLines={2}>{perihal}</Text> : null}
            <Text style={s.label}>Instruksi *</Text>
            <TextInput value={instruksi} onChangeText={setInstruksi} placeholder="Contoh: Tindak lanjuti, Hadiri, Pelajari" placeholderTextColor={colors.muted} style={s.input} testID="disposisi-instruksi-input" maxLength={255} />
            <Text style={s.label}>Catatan</Text>
            <TextInput value={catatan} onChangeText={setCatatan} placeholder="Catatan tambahan (opsional)" placeholderTextColor={colors.muted} style={[s.input, { minHeight: 88, textAlignVertical: "top", paddingTop: spacing.md }]} multiline testID="disposisi-catatan-input" />
            <Text style={s.label}>Batas waktu</Text>
            <TextInput value={batas} onChangeText={setBatas} placeholder="TTTT-BB-HH (opsional)" placeholderTextColor={colors.muted} style={s.input} testID="disposisi-batas-input" keyboardType="numbers-and-punctuation" maxLength={10} />

            <Text style={[s.label, { marginTop: spacing.xl }]}>Penerima ({selected.length} dipilih) *</Text>
            <View style={s.search}>
              <Icon name="search" size={18} color={colors.muted} />
              <TextInput value={search} onChangeText={setSearch} placeholder="Cari nama, jabatan, satker" placeholderTextColor={colors.muted} style={s.searchInput} testID="disposisi-penerima-search" />
            </View>
            <View style={s.list} testID="disposisi-penerima-list">
              {users.map((u) => {
                const on = selected.includes(u.id);
                return (
                  <Pressable key={u.id} style={[s.user, on && s.userOn]} onPress={() => toggle(u.id)} testID={`disposisi-penerima-${u.id}`}>
                    <Icon name={on ? "checkbox" : "square-outline"} size={22} color={on ? colors.brandPrimary : colors.borderStrong} />
                    <View style={{ flex: 1 }}>
                      <Text style={s.userName}>{u.nama}</Text>
                      <Text style={s.userMeta} numberOfLines={1}>{[u.jabatan, u.satker].filter(Boolean).join(" · ") || "-"}</Text>
                    </View>
                  </Pressable>
                );
              })}
              {!users.length ? <Text style={[s.userMeta, { padding: spacing.lg }]}>Tidak ada penerima yang cocok.</Text> : null}
            </View>
            {error ? <Text style={s.error} testID="disposisi-form-error">{error}</Text> : null}
          </KeyboardAwareScrollView>
          <KeyboardStickyView>
            <StickyBar bottom={insets.bottom}>
              <Button label={`Kirim Disposisi${selected.length ? ` (${selected.length})` : ""}`} icon="send" style={{ flex: 1 }} onPress={submit} loading={loading} testID="disposisi-submit-button" />
            </StickyBar>
          </KeyboardStickyView>
        </>
      )}
    </View>
  );
}

const useStyles = makeStyles((c) => ({
  perihal: { fontFamily: font.bold, fontSize: 16, color: c.onSurface, marginBottom: spacing.sm },
  label: { fontFamily: font.semibold, fontSize: 13, color: c.onSurfaceTertiary, marginTop: spacing.lg, marginBottom: spacing.sm },
  input: { backgroundColor: c.surface, borderRadius: radius.md, borderWidth: 1, borderColor: c.border, paddingHorizontal: spacing.lg, minHeight: 50, fontFamily: font.regular, fontSize: 15, color: c.onSurface },
  search: { flexDirection: "row", alignItems: "center", gap: spacing.sm, backgroundColor: c.surface, borderRadius: radius.md, borderWidth: 1, borderColor: c.border, paddingHorizontal: spacing.md, height: 44 },
  searchInput: { flex: 1, fontFamily: font.regular, fontSize: 15, color: c.onSurface, height: "100%" },
  list: { backgroundColor: c.surface, borderRadius: radius.md, borderWidth: 1, borderColor: c.border, marginTop: spacing.md, overflow: "hidden" },
  user: { flexDirection: "row", alignItems: "center", gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md, borderBottomWidth: 1, borderBottomColor: c.divider, minHeight: 56 },
  userOn: { backgroundColor: c.brandTertiary },
  userName: { fontFamily: font.semibold, fontSize: 14, color: c.onSurface },
  userMeta: { fontFamily: font.regular, fontSize: 12, color: c.muted },
  error: { color: c.error, fontFamily: font.semibold, fontSize: 13, marginTop: spacing.lg },
}));
