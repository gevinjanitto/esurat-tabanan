import * as Haptics from "expo-haptics";
import { Platform, Pressable, Text, View } from "react-native";
import Animated, { LinearTransition } from "react-native-reanimated";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { font, makeStyles, radius, spacing, useTheme } from "@/src/theme";
import { Icon, IconName } from "./icon";

export type TabDef = { name: string; label: string; icon: IconName; iconActive: IconName };

// Bar navigasi mengambang: kapsul gelap, tab aktif jadi pil merah berlabel.
// Props dari expo-router 58: emitter + navigateToTab (bukan `navigation`).
type BarProps = { state: { index: number; routes: { key: string; name: string }[] }; emitter: { emit: (e: any) => { defaultPrevented: boolean } }; navigateToTab: (key: string) => void };

export function FloatingTabBar({ state, emitter, navigateToTab, tabs }: BarProps & { tabs: TabDef[] }) {
  const insets = useSafeAreaInsets();
  const s = useStyles();
  const { colors } = useTheme();
  return (
    <View style={[s.wrap, { paddingBottom: Math.max(insets.bottom, spacing.md) }]} testID="floating-tab-bar">
      <View style={s.bar}>
        {state.routes.map((route, i) => {
          const t = tabs.find((x) => x.name === route.name);
          if (!t) return null;
          const focused = state.index === i;
          return (
            <Animated.View key={route.key} layout={LinearTransition.springify().damping(18)} style={focused ? { flexGrow: 1 } : undefined}>
              <Pressable
                testID={`tab-${t.name}`}
                accessibilityRole="tab"
                accessibilityLabel={t.label}
                accessibilityState={{ selected: focused }}
                onPress={() => {
                  const e = emitter.emit({ type: "tabPress", target: route.key, canPreventDefault: true });
                  if (!focused && !e.defaultPrevented) {
                    if (Platform.OS !== "web") Haptics.selectionAsync();
                    navigateToTab(route.key);
                  }
                }}
                style={({ pressed }) => [s.item, focused && s.itemOn, pressed && { opacity: 0.8 }]}
              >
                <Icon name={focused ? t.iconActive : t.icon} size={22} color={focused ? colors.onBrandPrimary : "rgba(255,255,255,0.62)"} />
                {focused ? <Text style={s.label} numberOfLines={1}>{t.label}</Text> : null}
              </Pressable>
            </Animated.View>
          );
        })}
      </View>
    </View>
  );
}

const useStyles = makeStyles((c) => ({
  wrap: { backgroundColor: c.surfaceSecondary, paddingHorizontal: spacing.lg, paddingTop: spacing.sm },
  bar: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: spacing.xs,
    backgroundColor: c.surfaceInverse,
    borderRadius: radius.pill,
    padding: 6,
    height: 64,
    shadowColor: c.surfaceInverse,
    shadowOpacity: 0.25,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 8 },
    elevation: 10,
  },
  item: { height: 52, minWidth: 52, borderRadius: radius.pill, alignItems: "center", justifyContent: "center", flexDirection: "row", gap: spacing.sm, paddingHorizontal: spacing.md },
  itemOn: { backgroundColor: c.brandPrimary, paddingHorizontal: spacing.lg },
  label: { fontFamily: font.bold, fontSize: 13, color: c.onBrandPrimary },
}));
