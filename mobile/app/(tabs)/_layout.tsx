import { Tabs } from "expo-router";
import { NativeTabs } from "expo-router/native-tabs";
import { Platform } from "react-native";

import { Icon, IconName } from "@/src/components/icon";
import { usesNativeTabs } from "@/src/navigation";
import { font, useTheme } from "@/src/theme";

const TABS: { name: string; label: string; icon: IconName; iconActive: IconName; sf: string }[] = [
  { name: "index", label: "Beranda", icon: "home-outline", iconActive: "home", sf: "house.fill" },
  { name: "surat", label: "Surat", icon: "mail-outline", iconActive: "mail", sf: "envelope.fill" },
  { name: "disposisi", label: "Disposisi", icon: "git-branch-outline", iconActive: "git-branch", sf: "arrow.triangle.branch" },
  { name: "agenda", label: "Agenda", icon: "calendar-outline", iconActive: "calendar", sf: "calendar" },
];

export default function TabsLayout() {
  const { colors } = useTheme();

  if (usesNativeTabs) {
    return (
      <NativeTabs tintColor={colors.brandPrimary}>
        {TABS.map((t) => (
          <NativeTabs.Trigger key={t.name} name={t.name}>
            <NativeTabs.Trigger.Icon sf={t.sf as any} />
            <NativeTabs.Trigger.Label>{t.label}</NativeTabs.Trigger.Label>
          </NativeTabs.Trigger>
        ))}
      </NativeTabs>
    );
  }

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.brandPrimary,
        tabBarInactiveTintColor: colors.muted,
        tabBarStyle: { backgroundColor: colors.surface, borderTopColor: colors.border, ...(Platform.OS === "web" ? { height: 64 } : {}) },
        tabBarItemStyle: { alignSelf: "center" },
        tabBarLabelStyle: { fontFamily: font.semibold, fontSize: 11 },
      }}
    >
      {TABS.map((t) => (
        <Tabs.Screen
          key={t.name}
          name={t.name}
          options={{
            title: t.label,
            tabBarButtonTestID: `tab-${t.name}`,
            tabBarIcon: ({ color, focused }) => <Icon name={focused ? t.iconActive : t.icon} size={24} color={color} />,
          }}
        />
      ))}
    </Tabs>
  );
}
