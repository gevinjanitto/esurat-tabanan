import { useState } from "react";
import { Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { PagedList } from "@/src/components/paged-list";
import { AgendaRow } from "@/src/components/rows";
import { Segmented } from "@/src/components/ui";
import { usesNativeTabs } from "@/src/navigation";
import { font, makeStyles, spacing } from "@/src/theme";

export default function AgendaTab() {
  const [waktu, setWaktu] = useState<"datang" | "lalu">("datang");
  const insets = useSafeAreaInsets();
  const s = useStyles();
  return (
    <View style={s.root}>
      <View style={[s.header, { paddingTop: insets.top + spacing.md }]}>
        <Text style={s.title}>Agenda</Text>
        <Segmented
          testID="agenda-mode"
          value={waktu}
          onChange={setWaktu}
          options={[
            { key: "datang", label: "Akan Datang" },
            { key: "lalu", label: "Lalu" },
          ]}
        />
      </View>
      <PagedList
        key={waktu}
        testID={`agenda-${waktu}-list`}
        queryKey={["agenda", waktu]}
        path="/agenda"
        query={{ waktu: waktu === "lalu" ? "lalu" : undefined }}
        renderItem={(it) => <AgendaRow item={it} />}
        emptyTitle="Tidak ada agenda"
        emptyIcon="calendar-clear-outline"
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
