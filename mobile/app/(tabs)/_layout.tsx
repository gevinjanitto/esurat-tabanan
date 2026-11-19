import { Tabs } from "expo-router";
import { NativeTabs } from "expo-router/native-tabs";
import { FloatingTabBar } from "@/src/components/floating-tab-bar";
import { IconName } from "@/src/components/icon";
import { useAuth } from "@/src/auth";
import { usesNativeTabs } from "@/src/navigation";
import { useTheme } from "@/src/theme";

const TABS: { name: string; label: string; icon: IconName; iconActive: IconName; sf: string }[] = [
  { name: "index", label: "Beranda", icon: "home-outline", iconActive: "home", sf: "house.fill" },
  { name: "surat", label: "Surat", icon: "mail-outline", iconActive: "mail", sf: "envelope.fill" },
  { name: "disposisi", label: "Disposisi", icon: "git-branch-outline", iconActive: "git-branch", sf: "arrow.triangle.branch" },
  { name: "agenda", label: "Agenda", icon: "calendar-outline", iconActive: "calendar", sf: "calendar" },
];

export default function TabsLayout() {
  const { colors } = useTheme();
  const { user } = useAuth();
  if (!user) return null;

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
    <Tabs screenOptions={{ headerShown: false }} tabBar={(props: any) => <FloatingTabBar {...props} tabs={TABS} />}>
      {TABS.map((t) => (
        <Tabs.Screen key={t.name} name={t.name} options={{ title: t.label }} />
      ))}
    </Tabs>
  );
}
