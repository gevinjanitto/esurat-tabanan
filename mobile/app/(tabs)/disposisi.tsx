import { useState } from "react";
import { Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { PagedList } from "@/src/components/paged-list";
import { DisposisiRow } from "@/src/components/rows";
import { Segmented } from "@/src/components/ui";
import { usesNativeTabs } from "@/src/navigation";
import { font, makeStyles, spacing } from "@/src/theme";

export default function DisposisiTab() {
  const [mode, setMode] = useState<"masuk" | "keluar">("masuk");
  const insets = useSafeAreaInsets();
  const s = useStyles();
  return (
    <View style={s.root}>
      <View style={[s.header, { paddingTop: insets.top + spacing.md }]}>
        <Text style={s.title}>Disposisi</Text>
        <Segmented
          testID="disposisi-mode"
          value={mode}
          onChange={setMode}
          options={[
            { key: "masuk", label: "Untuk Saya" },
            { key: "keluar", label: "Dari Saya" },
          ]}
        />
      </View>
      <PagedList
        key={mode}
        testID={`disposisi-${mode}-list`}
        queryKey={["disposisi", mode]}
        path="/disposisi"
        query={{ mode }}
        renderItem={(it) => <DisposisiRow item={it} mode={mode} />}
        emptyTitle="Tidak ada disposisi"
        emptyIcon="git-branch-outline"
        bottomPad={usesNativeTabs ? insets.bottom : 0}
      />
    </View>
  );
}

const useStyles = makeStyles((c) => ({
  root: { flex: 1, backgroundColor: c.surfaceSecondary },
  header: { backgroundColor: c.surface, paddingHorizontal: spacing.lg, paddingBottom: spacing.md, gap: spacing.md, borderBottomWidth: 1, borderBottomColor: c.border },
  title: { fontFamily: font.heavy, fontSize: 28, color: c.onSurface },
}));
