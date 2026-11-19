import { useInfiniteQuery } from "@tanstack/react-query";
import { ReactElement, ReactNode } from "react";
import { ActivityIndicator, FlatList, RefreshControl, View } from "react-native";

import { api } from "@/src/api";
import { spacing, useTheme } from "@/src/theme";
import { EmptyState, ErrorState, SkeletonList } from "./ui";
import { IconName } from "./icon";

type Page = { data: any[]; meta: { page: number; last_page: number; total: number } };

export function PagedList({
  queryKey,
  path,
  query,
  renderItem,
  emptyTitle,
  emptyIcon,
  bottomPad = 0,
  header,
  testID,
}: {
  queryKey: unknown[];
  path: string;
  query?: Record<string, any>;
  renderItem: (item: any) => ReactElement;
  emptyTitle: string;
  emptyIcon?: IconName;
  bottomPad?: number;
  header?: ReactNode;
  testID?: string;
}) {
  const { colors } = useTheme();
  const q = useInfiniteQuery({
    queryKey,
    queryFn: ({ pageParam }) => api<Page>(path, { query: { ...query, page: pageParam } }),
    initialPageParam: 1,
    getNextPageParam: (last) => (last.meta && last.meta.page < last.meta.last_page ? last.meta.page + 1 : undefined),
  });
  const items = q.data?.pages.flatMap((p) => p.data) ?? [];

  if (q.isLoading) return <SkeletonList />;
  if (q.isError) return <ErrorState message={(q.error as Error).message} onRetry={() => q.refetch()} />;

  return (
    <FlatList
      testID={testID}
      data={items}
      keyExtractor={(it) => String(it.id)}
      renderItem={({ item }) => renderItem(item)}
      ListHeaderComponent={header ? <>{header}</> : null}
      contentContainerStyle={{ padding: spacing.lg, paddingBottom: bottomPad + spacing.xl, gap: spacing.md }}
      onEndReached={() => q.hasNextPage && !q.isFetchingNextPage && q.fetchNextPage()}
      onEndReachedThreshold={0.4}
      refreshControl={<RefreshControl refreshing={q.isRefetching && !q.isFetchingNextPage} onRefresh={() => q.refetch()} tintColor={colors.brandPrimary} colors={[colors.brandPrimary]} />}
      ListEmptyComponent={<EmptyState icon={emptyIcon} title={emptyTitle} testID={`${testID}-empty`} />}
      ListFooterComponent={q.isFetchingNextPage ? <View style={{ padding: spacing.lg }}><ActivityIndicator color={colors.brandPrimary} /></View> : null}
      keyboardShouldPersistTaps="handled"
    />
  );
}
