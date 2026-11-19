import { useQuery, useQueryClient } from "@tanstack/react-query";
import { BlurView } from "expo-blur";
import { router, useLocalSearchParams } from "expo-router";
import { useCallback, useEffect, useRef, useState } from "react";
import { Platform, Pressable, ScrollView, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { api, ApiError } from "@/src/api";
import { useAuth } from "@/src/auth";
import { ActionSheet, SheetConfig } from "@/src/components/action-sheet";
import { Icon, IconName } from "@/src/components/icon";
import { Anno, PdfStudio, StudioHandle, StudioMsg, useAnnotations, usePdfSrc } from "@/src/components/pdf-studio";
import { useToast } from "@/src/components/toast";
import { Button, ErrorState } from "@/src/components/ui";
import { font, makeStyles, radius, shadow, spacing, useTheme } from "@/src/theme";

const TOOLS: { key: string; label: string; icon: IconName }[] = [
  { key: "hand", label: "Geser", icon: "hand-left-outline" },
  { key: "pen", label: "Pena", icon: "pencil" },
  { key: "highlighter", label: "Stabilo", icon: "color-wand-outline" },
  { key: "eraser", label: "Hapus", icon: "backspace-outline" },
  { key: "note", label: "Catatan", icon: "chatbubble-ellipses-outline" },
  { key: "paraf", label: "Paraf", icon: "ribbon-outline" },
];
// Warna sama dengan toolbar web (merah, biru, hijau toska, hitam): data tersimpan identik.
// Mode tanda tangan: hanya geser & tempel TTD (gambar paraf/TTD dari Profil, sama dengan website).
const TTD_TOOLS: { key: string; label: string; icon: IconName }[] = [
  { key: "hand", label: "Geser", icon: "hand-left-outline" },
  { key: "paraf", label: "Tempel TTD", icon: "create-outline" },
];
const PEN_COLORS = ["#e11d48", "#2563eb", "#0f766e", "#111827"];
const SIZES = [2, 3, 5, 8];

export default function Studio() {
  const { type, id } = useLocalSearchParams<{ type: "masuk" | "keluar"; id: string }>();
  const keluar = type === "keluar";
  const { user } = useAuth();
  const insets = useSafeAreaInsets();
  const s = useStyles();
  const { colors } = useTheme();
  const toast = useToast();
  const qc = useQueryClient();
  const studio = useRef<StudioHandle>(null);

  const detail = useQuery({
    queryKey: [keluar ? "surat-keluar-detail" : "surat-masuk-detail", id],
    queryFn: () => api<any>(`/surat/${keluar ? "keluar" : "masuk"}/${id}`),
  });
  const d = detail.data?.data;
  const src = usePdfSrc(d?.file_url);
  const anno = useAnnotations(keluar ? id : null);
  const annoMissing = anno.isError && (anno.error as ApiError)?.status === 404;

  const peran: string | null = keluar ? d?.giliran_saya ?? null : null;
  const editable = !!peran && !anno.isLoading && !annoMissing;
  const isSigner = peran === "penandatangan";
  // Penandatangan: mode "ttd" = tempel TTD lalu passphrase; mode "revisi" = coretan untuk dikembalikan.
  const [mode, setMode] = useState<"ttd" | "revisi">("ttd");
  const ttdMode = isSigner && mode === "ttd";
  const canParaf = peran === "verifikator" || ttdMode;
  const [ttdCount, setTtdCount] = useState(0);

  const [tool, setTool] = useState("hand");
  const [color, setColor] = useState(PEN_COLORS[0]);
  const [size, setSize] = useState(3);
  const [canUndo, setCanUndo] = useState(false);
  const [status, setStatus] = useState<string>("");
  const [sheet, setSheet] = useState<SheetConfig | null>(null);
  const data = useRef<Anno | null>(null);
  const dirty = useRef(false);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    if (editable) {
      const t = ttdMode ? "paraf" : "pen";
      setTool(t);
      studio.current?.send({ type: "tool", tool: t });
    }
  }, [editable, ttdMode]);

  useEffect(() => {
    const own = anno.data?.find((r) => r.mine);
    setTtdCount(own?.data?.parafs?.length ?? 0);
  }, [anno.data]);

  const save = useCallback(async (): Promise<boolean> => {
    if (timer.current) clearTimeout(timer.current);
    if (!dirty.current || !data.current) return true;
    setStatus("Menyimpan…");
    try {
      await api(`/surat/keluar/${id}/anotasi`, { method: "POST", body: data.current });
      dirty.current = false;
      setStatus(`Tersimpan ${new Date().toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" })}`);
      qc.invalidateQueries({ queryKey: ["anotasi", String(id)] });
      return true;
    } catch (e: any) {
      setStatus("Gagal menyimpan");
      toast(e?.message ?? "Gagal menyimpan coretan", "error");
      return false;
    }
  }, [id, qc, toast]);

  // Simpan otomatis saat keluar dari layar.
  useEffect(() => () => {
    if (dirty.current) save();
  }, [save]);

  const pick = (t: string) => {
    if (t === "paraf" && !canParaf) return toast(isSigner ? "Kembali ke mode TTD untuk menempel tanda tangan" : "Paraf hanya untuk verifikator", "info");
    setTool(t);
    studio.current?.send({ type: "tool", tool: t });
  };
  const pickColor = (c: string) => {
    setColor(c);
    studio.current?.send({ type: "color", color: c });
    if (tool !== "pen" && tool !== "highlighter") pick("pen");
  };
  const pickSize = (n: number) => {
    setSize(n);
    studio.current?.send({ type: "size", size: n });
  };

  const onMessage = (m: StudioMsg) => {
    if (m.type === "changed") {
      data.current = m.data;
      setTtdCount(m.data.parafs?.length ?? 0);
      dirty.current = true;
      setCanUndo(m.canUndo);
      setStatus("Belum disimpan");
      if (timer.current) clearTimeout(timer.current);
      timer.current = setTimeout(save, 2500);
    } else if (m.type === "noteNew") {
      setSheet({
        title: "Catatan revisi",
        description: `Halaman ${m.page} · tulis catatan untuk bagian ini`,
        inputLabel: "Catatan",
        placeholder: "mis. Perbaiki penulisan nama jabatan…",
        required: true,
        confirmLabel: "Simpan Catatan",
        onConfirm: async (t) => {
          studio.current?.send({ type: "addNote", page: m.page, x: m.x, y: m.y, text: t.trim().slice(0, 1000) });
          pick("hand");
        },
      });
    } else if (m.type === "noteTap") {
      if (m.own) {
        setSheet({
          title: "Ubah catatan",
          description: "Kosongkan isian lalu simpan untuk menghapus catatan ini.",
          inputLabel: "Catatan",
          initial: m.text,
          confirmLabel: "Simpan",
          onConfirm: async (t) => {
            studio.current?.send(t.trim() ? { type: "editNote", index: m.index, text: t.trim().slice(0, 1000) } : { type: "deleteNote", index: m.index });
          },
        });
      } else {
        setSheet({ title: m.author || "Catatan", description: `Hal. ${m.page}\n\n${m.text}`, confirmLabel: "Tutup", hideCancel: true, onConfirm: async () => {} });
      }
    } else if (m.type === "toast") {
      toast(m.message, "info");
    }
  };

  const decide = (action: "setuju" | "revisi" | "tandatangan") => {
    const cfg: Record<string, SheetConfig> = {
      setuju: { title: "Setujui surat", description: "Coretan Anda disimpan, lalu surat diteruskan ke tahap berikutnya.", inputLabel: "Catatan (opsional)", placeholder: "Tulis catatan untuk pembuat surat", confirmLabel: "Setujui", onConfirm: async () => {} },
      revisi: { title: "Kembalikan untuk revisi", description: "Pembuat surat akan melihat coretan & catatan Anda.", inputLabel: "Catatan revisi", placeholder: "Jelaskan bagian yang perlu diperbaiki", required: true, danger: true, confirmLabel: "Kirim Revisi", onConfirm: async () => {} },
      tandatangan: { title: "Tanda tangan elektronik", description: `TTD Anda (${ttdCount}) dibubuhkan di posisi yang ditempel. Masukkan passphrase TTE (sama dengan password akun Anda).`, inputLabel: "Passphrase", placeholder: "••••••••", secure: true, required: true, confirmLabel: "Tanda Tangan", onConfirm: async () => {} },
    };
    const path = { setuju: "/surat/keluar/setuju", revisi: "/surat/keluar/revisi", tandatangan: "/surat/keluar/simpan-tandatangan" }[action];
    setSheet({
      ...cfg[action],
      onConfirm: async (t) => {
        if (!(await save())) throw new Error("Coretan gagal disimpan. Coba lagi.");
        const body = action === "tandatangan" ? { passphrase: t } : { catatan: t || undefined };
        const r = await api<any>(path, { method: "POST", body: { id: Number(id), ...body } });
        toast(r?.message || "Berhasil disimpan");
        qc.invalidateQueries();
        router.back();
      },
    });
  };

  const back = async () => {
    await save();
    router.canGoBack() ? router.back() : router.replace("/");
  };

  const Glass = Platform.OS === "ios" ? BlurView : View;

  return (
    <View style={s.root} testID="studio-screen">
      <View style={[s.header, { paddingTop: insets.top + spacing.sm }]}>
        <Pressable onPress={back} style={({ pressed }) => [s.circle, pressed && { opacity: 0.6 }]} testID="studio-back-button">
          <Icon name="chevron-back" size={22} color={colors.onSurface} />
        </Pressable>
        <View style={{ flex: 1 }}>
          <Text style={s.title} numberOfLines={1}>{editable ? (ttdMode ? "Tanda Tangan" : isSigner ? "Revisi Surat" : "Verifikasi") : "Pratinjau Surat"}</Text>
          <Text style={s.sub} numberOfLines={1} testID="studio-save-status">
            {status ||
              (!editable
                ? d?.perihal ?? "Memuat…"
                : ttdMode
                  ? "Ketuk dokumen untuk menempel TTD, lalu masukkan passphrase"
                  : isSigner
                    ? "Coret & beri catatan untuk revisi"
                    : "Coret, beri catatan, lalu putuskan")}
          </Text>
        </View>
        {editable ? (
          <Pressable
            onPress={async () => {
              dirty.current = true;
              if (await save()) toast("Coretan tersimpan");
            }}
            style={({ pressed }) => [s.saveBtn, pressed && { opacity: 0.8 }]}
            testID="studio-save-button"
          >
            <Icon name="save-outline" size={16} color={colors.onSurfaceInverse} />
            <Text style={s.saveText}>Simpan</Text>
          </Pressable>
        ) : null}
      </View>

      {annoMissing && peran ? (
        <View style={s.banner} testID="studio-annotation-unsupported">
          <Icon name="information-circle" size={18} color={colors.info} />
          <Text style={s.bannerText}>Server E-Surat belum diperbarui untuk coretan dari HP. Mode baca aktif.</Text>
        </View>
      ) : null}

      {detail.isError ? (
        <ErrorState message={(detail.error as Error).message} onRetry={() => detail.refetch()} />
      ) : src.data && (!keluar || !anno.isLoading) ? (
        <PdfStudio
          ref={studio}
          src={src.data}
          rows={anno.data}
          editable={editable}
          canParaf={canParaf}
          userName={user?.nama}
          tool={editable ? "pen" : "hand"}
          onMessage={onMessage}
          style={{ flex: 1 }}
          testID="studio-pdf"
        />
      ) : (
        <View style={{ flex: 1 }} />
      )}

      {editable ? (
        <View style={[s.dock, { paddingBottom: insets.bottom + spacing.sm }]} pointerEvents="box-none">
          <Glass intensity={60} tint="light" style={s.toolbar} testID="studio-toolbar">
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={s.toolRow}>
              {(ttdMode ? TTD_TOOLS : TOOLS.filter((t) => t.key !== "paraf" || canParaf)).map((t) => {
                const on = tool === t.key;
                return (
                  <Pressable key={t.key} onPress={() => pick(t.key)} style={[s.tool, on && s.toolOn]} testID={`studio-tool-${t.key}`}>
                    <Icon name={t.icon} size={18} color={on ? colors.onSurfaceInverse : colors.onSurface} />
                    <Text style={[s.toolText, on && { color: colors.onSurfaceInverse }]}>{t.label}</Text>
                  </Pressable>
                );
              })}
            </ScrollView>
            <View style={s.optRow}>
              {ttdMode ? (
                <Text style={s.ttdInfo} numberOfLines={2} testID="studio-ttd-count">
                  {ttdCount ? `${ttdCount} TTD ditempel · geser / tarik sudut untuk ubah ukuran` : "Belum ada TTD ditempel"}
                </Text>
              ) : null}
              {(ttdMode ? [] : PEN_COLORS).map((c, i) => (
                <Pressable key={c} onPress={() => pickColor(c)} style={[s.swatchRing, color === c && { borderColor: c }]} testID={`studio-color-${i}`}>
                  <View style={[s.swatch, { backgroundColor: c }]} />
                </Pressable>
              ))}
              {ttdMode ? null : <View style={s.sep} />}
              {(ttdMode ? [] : SIZES).map((n) => (
                <Pressable key={n} onPress={() => pickSize(n)} style={[s.sizeBtn, size === n && s.sizeOn]} testID={`studio-size-${n}`}>
                  <View style={{ width: n + 3, height: n + 3, borderRadius: 8, backgroundColor: size === n ? colors.onSurfaceInverse : colors.onSurface }} />
                </Pressable>
              ))}
              <View style={{ flex: 1 }} />
              <Pressable onPress={() => studio.current?.send({ type: "undo" })} disabled={!canUndo} style={[s.iconBtn, !canUndo && { opacity: 0.35 }]} testID="studio-undo-button">
                <Icon name="arrow-undo" size={18} color={colors.onSurface} />
              </Pressable>
              <Pressable
                onPress={() =>
                  setSheet({
                    title: "Hapus semua milik Anda?",
                    description: "Semua coretan, catatan, dan paraf Anda pada dokumen ini akan dihapus. Bisa diurungkan.",
                    confirmLabel: "Hapus Semua",
                    danger: true,
                    onConfirm: async () => studio.current?.send({ type: "clear" }),
                  })
                }
                style={s.iconBtn}
                testID="studio-clear-button"
              >
                <Icon name="trash-outline" size={18} color={colors.error} />
              </Pressable>
            </View>
          </Glass>
          <View style={s.decide}>
            {isSigner && mode === "revisi" ? (
              <>
                <Button label="Batal" variant="secondary" icon="close" style={{ flex: 1 }} onPress={() => setMode("ttd")} testID="studio-revisi-cancel-button" />
                <Button label="Kirim Revisi" variant="danger" icon="return-up-back" style={{ flex: 1.4 }} onPress={() => decide("revisi")} testID="studio-revisi-submit-button" />
              </>
            ) : (
              <Button label="Revisi" variant="danger" icon="return-up-back" style={{ flex: 1 }} onPress={() => (isSigner ? setMode("revisi") : decide("revisi"))} testID="studio-revisi-button" />
            )}
            {isSigner ? (
              mode === "ttd" ? (
                <Button
                  label="Tanda Tangan"
                  icon="create-outline"
                  style={{ flex: 1.4 }}
                  onPress={() => (ttdCount ? decide("tandatangan") : toast("Ketuk dokumen untuk menempel TTD Anda dulu", "info"))}
                  testID="studio-ttd-button"
                />
              ) : null
            ) : (
              <Button label="Setujui" icon="checkmark-circle-outline" style={{ flex: 1.4 }} onPress={() => decide("setuju")} testID="studio-setuju-button" />
            )}
          </View>
        </View>
      ) : (
        <View style={{ height: insets.bottom }} />
      )}
      <ActionSheet config={sheet} onClose={() => setSheet(null)} />
    </View>
  );
}

const useStyles = makeStyles((c) => ({
  root: { flex: 1, backgroundColor: c.surfaceTertiary },
  header: { flexDirection: "row", alignItems: "center", gap: spacing.md, paddingHorizontal: spacing.lg, paddingBottom: spacing.md, backgroundColor: c.surfaceSecondary },
  circle: { width: 44, height: 44, borderRadius: 22, backgroundColor: c.surface, alignItems: "center", justifyContent: "center", ...shadow },
  title: { fontFamily: font.heavy, fontSize: 18, color: c.onSurface },
  sub: { fontFamily: font.regular, fontSize: 12, color: c.muted, marginTop: 1 },
  saveBtn: { flexDirection: "row", alignItems: "center", gap: 6, height: 44, paddingHorizontal: spacing.lg, borderRadius: radius.pill, backgroundColor: c.surfaceInverse },
  saveText: { fontFamily: font.bold, fontSize: 13, color: c.onSurfaceInverse },
  banner: { flexDirection: "row", alignItems: "center", gap: spacing.sm, backgroundColor: c.infoSoft, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  bannerText: { flex: 1, fontFamily: font.semibold, fontSize: 12, color: c.info },
  dock: { position: "absolute", left: 0, right: 0, bottom: 0, paddingHorizontal: spacing.md, gap: spacing.sm },
  toolbar: { borderRadius: radius.lg, overflow: "hidden", backgroundColor: c.glass, borderWidth: 1, borderColor: c.border, paddingVertical: spacing.sm, ...shadow },
  toolRow: { gap: spacing.xs, paddingHorizontal: spacing.sm },
  tool: { flexShrink: 0, flexDirection: "row", alignItems: "center", gap: 6, height: 40, paddingHorizontal: spacing.md, borderRadius: radius.pill },
  toolOn: { backgroundColor: c.surfaceInverse },
  toolText: { fontFamily: font.semibold, fontSize: 12, color: c.onSurface },
  optRow: { flexDirection: "row", alignItems: "center", gap: 6, paddingHorizontal: spacing.md, paddingTop: spacing.sm },
  swatchRing: { width: 32, height: 32, borderRadius: 16, borderWidth: 2, borderColor: "transparent", alignItems: "center", justifyContent: "center" },
  swatch: { width: 20, height: 20, borderRadius: 10 },
  sep: { width: 1, height: 22, backgroundColor: c.border, marginHorizontal: 2 },
  sizeBtn: { width: 32, height: 32, borderRadius: 16, alignItems: "center", justifyContent: "center" },
  sizeOn: { backgroundColor: c.surfaceInverse },
  iconBtn: { width: 40, height: 40, borderRadius: 20, backgroundColor: c.surface, alignItems: "center", justifyContent: "center" },
  decide: { flexDirection: "row", gap: spacing.sm },
  ttdInfo: { flex: 1, fontFamily: font.semibold, fontSize: 12, color: c.muted },
}));
