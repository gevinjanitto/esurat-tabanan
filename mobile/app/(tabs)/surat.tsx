import { useLocalSearchParams } from "expo-router";
import { useEffect, useState } from "react";
import { Pressable, Text, TextInput, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { Icon } from "@/src/components/icon";
import { PagedList } from "@/src/components/paged-list";
import { SuratKeluarRow, SuratMasukRow } from "@/src/components/rows";
import { Segmented } from "@/src/components/ui";
import { usesNativeTabs } from "@/src/navigation";
import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";

export default function Surat() {
  const params = useLocalSearchParams<{ mode?: string }>();
  const [mode, setMode] = useState<"masuk" | "keluar">(params.mode === "keluar" ? "keluar" : "masuk");
  const [text, setText] = useState("");
  const [q, setQ] = useState("");
  const insets = useSafeAreaInsets();
  const s = useStyles();
  const { colors } = useTheme();

  useEffect(() => {
    if (params.mode === "masuk" || params.mode === "keluar") setMode(params.mode);
  }, [params.mode]);

  useEffect(() => {
    const t = setTimeout(() => setQ(text.trim()), 400);
    return () => clearTimeout(t);
  }, [text]);

  return (
    <View style={s.root}>
      <View style={[s.header, { paddingTop: insets.top + spacing.md }]}>
        <Text style={s.overline}>Masuk & keluar</Text>
        <Text style={s.title}>Surat</Text>
        <View style={s.search}>
          <Icon name="search" size={18} color={colors.muted} />
          <TextInput
            value={text}
            onChangeText={setText}
            placeholder="Cari perihal, nomor, pengirim"
            placeholderTextColor={colors.muted}
            style={s.searchInput}
            returnKeyType="search"
            testID="surat-search-input"
          />
          {text ? (
            <Pressable onPress={() => setText("")} hitSlop={8} testID="surat-search-clear">
              <Icon name="close-circle" size={18} color={colors.muted} />
            </Pressable>
          ) : null}
        </View>
        <Segmented
          testID="surat-mode"
          value={mode}
          onChange={setMode}
          options={[
            { key: "masuk", label: "Surat Masuk" },
            { key: "keluar", label: "Surat Keluar" },
          ]}
        />
      </View>
      {mode === "masuk" ? (
        <PagedList
          key="masuk"
          testID="surat-masuk-list"
          queryKey={["surat-masuk", q]}
          path="/surat/masuk"
          query={{ q }}
          renderItem={(it) => <SuratMasukRow item={it} />}
          emptyTitle="Tidak ada surat masuk ditemukan"
          emptyIcon="mail-open-outline"
          bottomPad={usesNativeTabs ? insets.bottom : 0}
        />
      ) : (
        <PagedList
          key="keluar"
          testID="surat-keluar-list"
          queryKey={["surat-keluar", q]}
          path="/surat/keluar"
          query={{ q }}
          renderItem={(it) => <SuratKeluarRow item={it} />}
          emptyTitle="Tidak ada surat keluar ditemukan"
          emptyIcon="paper-plane-outline"
          bottomPad={usesNativeTabs ? insets.bottom : 0}
        />
      )}
    </View>
  );
}

const useStyles = makeStyles((c) => ({
  root: { flex: 1, backgroundColor: c.surfaceSecondary },
  header: { backgroundColor: c.surfaceSecondary, paddingHorizontal: spacing.lg, paddingBottom: spacing.md, gap: spacing.md },
  overline: { fontFamily: font.semibold, fontSize: 13, color: c.brandPrimary },
  title: { fontFamily: font.heavy, fontSize: 32, color: c.onSurface, lineHeight: 38, marginTop: -spacing.sm },
  search: { flexDirection: "row", alignItems: "center", gap: spacing.sm, backgroundColor: c.surface, borderRadius: radius.pill, paddingHorizontal: spacing.lg, height: 52, borderWidth: 1, borderColor: c.border },
  searchInput: { flex: 1, fontFamily: font.regular, fontSize: 15, color: c.onSurface, height: "100%" },
}));
