import { useQuery } from "@tanstack/react-query";
import { useLocalSearchParams } from "expo-router";
import { FlatList, RefreshControl, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { api } from "@/src/api";
import { SuratKeluarRow } from "@/src/components/rows";
import { DetailHeader, EmptyState, ErrorState, SkeletonList } from "@/src/components/ui";
import { spacing, useTheme } from "@/src/theme";

export default function Antrean() {
  const { peran } = useLocalSearchParams<{ peran: string }>();
  const isTtd = peran === "tandatangan";
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const q = useQuery({ queryKey: ["antrean", isTtd ? "tandatangan" : "verifikasi"], queryFn: () => api<any>(`/surat/keluar/${isTtd ? "tandatangan" : "verifikasi"}`) });

  return (
    <View style={{ flex: 1, backgroundColor: colors.surfaceSecondary }} testID="antrean-screen">
      <DetailHeader title={isTtd ? "Antrean Tanda Tangan" : "Antrean Verifikasi"} />
      {q.isLoading ? (
        <SkeletonList />
      ) : q.isError ? (
        <ErrorState message={(q.error as Error).message} onRetry={() => q.refetch()} />
      ) : (
        <FlatList
          testID="antrean-list"
          data={q.data?.data ?? []}
          keyExtractor={(it) => String(it.id)}
          renderItem={({ item }) => <SuratKeluarRow item={item} />}
          contentContainerStyle={{ padding: spacing.lg, gap: spacing.md, paddingBottom: insets.bottom + spacing.xl }}
          refreshControl={<RefreshControl refreshing={q.isRefetching} onRefresh={() => q.refetch()} tintColor={colors.brandPrimary} />}
          ListEmptyComponent={<EmptyState icon="checkmark-done-outline" title="Semua beres" desc="Tidak ada surat yang menunggu tindakan Anda." testID="antrean-empty" />}
        />
      )}
    </View>
  );
}
