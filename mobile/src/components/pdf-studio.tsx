import { useQuery } from "@tanstack/react-query";
import { forwardRef, useEffect, useImperativeHandle, useRef, useState } from "react";
import { ActivityIndicator, StyleProp, Text, View, ViewStyle } from "react-native";

import { api, withToken } from "@/src/api";
import { BACKEND_URL, ESURAT_URL } from "@/src/config";
import { STUDIO_HTML } from "@/src/pdf-studio-html";
import { font, spacing, useTheme } from "@/src/theme";
import { FrameHandle, StudioFrame } from "./studio-frame";

export type Anno = { strokes: any[]; notes: any[]; parafs: any[] };
export type AnnoRow = { user_id: number; author: string; mine: boolean; data: Anno; updated_at?: string };
export type StudioMsg =
  | { type: "boot" | "firstPage" }
  | { type: "ready"; pages: number }
  | { type: "changed"; data: Anno; canUndo: boolean }
  | { type: "noteNew"; page: number; x: number; y: number }
  | { type: "noteTap"; own: boolean; index: number; author: string; text: string; page: number }
  | { type: "toast"; message: string }
  | { type: "error"; message: string };

export const emptyAnno = (): Anno => ({ strokes: [], notes: [], parafs: [] });

// Alamat PDF lewat proxy backend, token diperbarui dulu agar tidak kedaluwarsa saat dirender.
export function usePdfSrc(fileUrl?: string | null) {
  return useQuery({
    queryKey: ["pdf-src", fileUrl],
    enabled: !!fileUrl,
    staleTime: 60_000,
    queryFn: async () => {
      await api("/me");
      // PWA satu domain dengan server E-Surat: PDF dimuat langsung tanpa proxy.
      return BACKEND_URL ? `${BACKEND_URL}/api/pdf?u=${encodeURIComponent(withToken(fileUrl!))}` : withToken(fileUrl!);
    },
  });
}

// Anotasi semua pengguna untuk revisi aktif (endpoint baru /api/m/.../anotasi).
export function useAnnotations(suratKeluarId?: string | number | null) {
  return useQuery({
    queryKey: ["anotasi", String(suratKeluarId)],
    enabled: !!suratKeluarId,
    retry: false,
    queryFn: () => api<AnnoRow[]>(`/surat/keluar/${suratKeluarId}/anotasi`),
  });
}

export const parafUrl = () => withToken(`${ESURAT_URL}/api/m/paraf`);

type Props = {
  src: string;
  editable?: boolean;
  canParaf?: boolean;
  userName?: string;
  rows?: AnnoRow[];
  compact?: boolean;
  tool?: string;
  onMessage?: (m: StudioMsg) => void;
  style?: StyleProp<ViewStyle>;
  testID?: string;
};

export type StudioHandle = { send: (cmd: any) => void };

export const PdfStudio = forwardRef<StudioHandle, Props>(function PdfStudio(props, ref) {
  const { colors } = useTheme();
  const frame = useRef<FrameHandle>(null);
  const latest = useRef(props);
  latest.current = props;
  const booted = useRef(false);
  const [loaded, setLoaded] = useState(false);
  const [failed, setFailed] = useState(false);

  useImperativeHandle(ref, () => ({ send: (cmd) => frame.current?.post(cmd) }));

  const split = (rows?: AnnoRow[], editable?: boolean) => {
    const list = rows ?? [];
    const own = list.find((r) => r.mine && editable);
    return {
      mine: own?.data ?? emptyAnno(),
      others: list.filter((r) => r !== own).map((r) => ({ author: r.author, data: r.data })),
    };
  };

  const init = () => {
    const p = latest.current;
    frame.current?.post({
      type: "init",
      src: p.src,
      editable: !!p.editable,
      canParaf: !!p.canParaf,
      parafUrl: p.canParaf ? parafUrl() : "",
      userName: p.userName ?? "Saya",
      compact: !!p.compact,
      tool: p.tool ?? "hand",
      ...split(p.rows, p.editable),
    });
  };

  // Anotasi dimuat setelah PDF: kirim ulang saat data datang.
  useEffect(() => {
    if (booted.current) frame.current?.post({ type: "annotations", ...split(props.rows, props.editable) });
  }, [props.rows, props.editable]);

  useEffect(() => {
    if (booted.current) frame.current?.post({ type: "config", editable: !!props.editable, canParaf: !!props.canParaf, parafUrl: props.canParaf ? parafUrl() : "" });
  }, [props.editable, props.canParaf]);

  const handle = (raw: string) => {
    let m: StudioMsg;
    try {
      m = JSON.parse(raw);
    } catch {
      return;
    }
    if (m.type === "boot") {
      booted.current = true;
      init();
    }
    if (m.type === "firstPage" || m.type === "ready") setLoaded(true);
    if (m.type === "error") setFailed(true);
    latest.current.onMessage?.(m);
  };

  return (
    <View style={[{ backgroundColor: colors.surfaceTertiary, overflow: "hidden" }, props.style]} testID={props.testID}>
      <StudioFrame ref={frame} html={STUDIO_HTML} onMessage={handle} style={{ flex: 1 }} testID={props.testID ? `${props.testID}-frame` : undefined} />
      {!loaded && !failed ? (
        <View pointerEvents="none" style={{ position: "absolute", top: spacing.lg, alignSelf: "center", flexDirection: "row", alignItems: "center", gap: spacing.sm, backgroundColor: colors.surface, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm, borderRadius: 999 }}>
          <ActivityIndicator size="small" color={colors.brandPrimary} />
          <Text style={{ fontFamily: font.semibold, fontSize: 12, color: colors.muted }}>Memuat dokumen…</Text>
        </View>
      ) : null}
    </View>
  );
});
