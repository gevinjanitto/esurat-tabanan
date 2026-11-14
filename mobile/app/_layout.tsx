import { QueryClientProvider } from "@tanstack/react-query";
import { useFonts } from "expo-font";
import * as Notifications from "expo-notifications";
import { router, Stack, useSegments } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { useEffect, useState } from "react";
import { ActivityIndicator, Linking, LogBox, Modal, Platform, StyleSheet, Text, View } from "react-native";
import { GestureHandlerRootView } from "react-native-gesture-handler";
import { KeyboardProvider } from "react-native-keyboard-controller";

import { AuthProvider, useAuth } from "@/src/auth";
import { ErrorBoundary } from "@/src/components/error-boundary";
import { ToastProvider } from "@/src/components/toast";
import { Button } from "@/src/components/ui";
import { routeFromUrl } from "@/src/push";
import { queryClient } from "@/src/query-client";
import { colors, font, radius, spacing } from "@/src/theme";
import { storage } from "@/src/utils/storage";

LogBox.ignoreAllLogs(true);

// Notifikasi saat aplikasi terbuka (modul scope)
if (Platform.OS !== "web") {
  Notifications.setNotificationHandler({
    handleNotification: async () => ({
      shouldShowAlert: true,
      shouldPlaySound: true,
      shouldSetBadge: false,
      shouldShowBanner: true,
      shouldShowList: true,
    }),
  });
}

// Channel Android (modul scope)
if (Platform.OS === "android") {
  Notifications.setNotificationChannelAsync("default", {
    name: "Notifikasi E-Surat",
    importance: Notifications.AndroidImportance.MAX,
    sound: "default",
  });
}

function openFromData(data: any) {
  const url = data?.deeplink || data?.action_url || data?.url;
  const route = routeFromUrl(url);
  if (route) router.push(route as any);
}

function Root() {
  const { user, ready } = useAuth();
  const segments = useSegments();
  const [nudge, setNudge] = useState(false);

  // Penjaga sesi: tanpa login -> halaman masuk; sudah login -> beranda.
  useEffect(() => {
    if (!ready) return;
    const onLogin = segments[0] === "login";
    if (!user && !onLogin) router.replace("/login");
    else if (user && onLogin) router.replace("/");
  }, [ready, user, segments]);

  useEffect(() => {
    if (Platform.OS === "web" || !ready || !user) return;
    const tapSub = Notifications.addNotificationResponseReceivedListener((response) => {
      openFromData(response.notification.request.content.data);
    });
    Notifications.getLastNotificationResponseAsync().then((response) => {
      if (response) openFromData(response.notification.request.content.data);
    });
    (async () => {
      const { status, canAskAgain } = await Notifications.getPermissionsAsync();
      if (status !== "denied" || canAskAgain) return;
      const last = await storage.getItem("pushNudgeAt", 0);
      if (last && Date.now() - Number(last) <= 7 * 24 * 60 * 60 * 1000) return;
      setNudge(true);
    })();
    return () => tapSub.remove();
  }, [ready, user]);

  const closeNudge = async (openSettings: boolean) => {
    await storage.setItem("pushNudgeAt", Date.now());
    setNudge(false);
    if (openSettings) Linking.openSettings();
  };

  if (!ready) {
    return (
      <View style={styles.splash} testID="session-check-loading">
        <ActivityIndicator color={colors.brandPrimary} size="large" />
        <Text style={styles.splashText}>Memeriksa sesi...</Text>
      </View>
    );
  }

  return (
    <>
      <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colors.surfaceSecondary } }} />
      <Modal visible={nudge} transparent animationType="fade" onRequestClose={() => closeNudge(false)}>
        <View style={styles.nudgeBackdrop}>
          <View style={styles.nudgeCard} testID="push-nudge-dialog">
            <Text style={styles.nudgeTitle}>Notifikasi masih nonaktif</Text>
            <Text style={styles.nudgeText}>Aktifkan di Pengaturan agar tidak ketinggalan surat dan disposisi baru.</Text>
            <View style={{ flexDirection: "row", gap: spacing.md, marginTop: spacing.xl }}>
              <Button label="Nanti" variant="secondary" style={{ flex: 1 }} onPress={() => closeNudge(false)} testID="push-nudge-later" />
              <Button label="Buka Pengaturan" style={{ flex: 1.4 }} onPress={() => closeNudge(true)} testID="push-nudge-open-settings" />
            </View>
          </View>
        </View>
      </Modal>
    </>
  );
}

export default function RootLayout() {
  const [fontsLoaded] = useFonts({
    "Jakarta-400": require("../assets/fonts/PlusJakartaSans-400.ttf"),
    "Jakarta-600": require("../assets/fonts/PlusJakartaSans-600.ttf"),
    "Jakarta-700": require("../assets/fonts/PlusJakartaSans-700.ttf"),
    "Jakarta-800": require("../assets/fonts/PlusJakartaSans-800.ttf"),
  });

  return (
    <GestureHandlerRootView style={styles.root}>
      <ErrorBoundary>
        <QueryClientProvider client={queryClient}>
          <KeyboardProvider>
            <StatusBar style="dark" />
            {fontsLoaded ? (
              <AuthProvider>
                <ToastProvider>
                  <Root />
                </ToastProvider>
              </AuthProvider>
            ) : (
              <View style={styles.splash}>
                <ActivityIndicator color={colors.brandPrimary} size="large" />
              </View>
            )}
          </KeyboardProvider>
        </QueryClientProvider>
      </ErrorBoundary>
    </GestureHandlerRootView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1 },
  splash: { flex: 1, alignItems: "center", justifyContent: "center", backgroundColor: colors.surface, gap: spacing.md },
  splashText: { fontFamily: font.semibold, color: colors.muted, fontSize: 14 },
  nudgeBackdrop: { flex: 1, backgroundColor: colors.scrim, alignItems: "center", justifyContent: "center", padding: spacing.xl },
  nudgeCard: { backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.xl, width: "100%", maxWidth: 420 },
  nudgeTitle: { fontFamily: font.bold, fontSize: 18, color: colors.onSurface },
  nudgeText: { fontFamily: font.regular, fontSize: 14, color: colors.muted, marginTop: spacing.sm, lineHeight: 20 },
});
